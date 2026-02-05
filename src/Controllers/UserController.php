<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthService;

class UserController extends BaseController
{
    private UserRepository $repository;
    private AuditService $auditService;
    private AuthService $authService;

    public function __construct()
    {
        $this->repository = new UserRepository();
        $this->auditService = new AuditService();
        $this->authService = new AuthService();
    }

    public function index(array $query): array
    {
        $plantelId = $this->getPlantelId();
        $conditions = [];

        // Filtrar por plantel si no es admin global
        if ($plantelId !== null) {
            $conditions['plantel_id'] = $plantelId;
        }

        if (isset($query['status'])) {
            $conditions['status'] = $query['status'];
        }

        if (isset($query['role'])) {
            $conditions['role'] = $query['role'];
        }

        $pagination = $this->getPaginationParams($query);
        $result = $this->repository->paginate(
            $pagination['page'],
            $pagination['per_page'],
            $conditions,
            ['name' => 'ASC']
        );

        return $this->success($result);
    }

    public function show(int $id): array
    {
        $user = $this->repository->find($id);

        if (!$user) {
            return $this->notFound('Usuario no encontrado');
        }

        // Verificar acceso
        $currentUser = $this->getUser();
        $plantelId = $this->getPlantelId();
        
        if ($plantelId !== null && $user->plantel_id !== $plantelId && $user->getId() !== $currentUser['id']) {
            return $this->forbidden('No tiene acceso a este usuario');
        }

        return $this->success($user);
    }

    public function store(array $body): array
    {
        $errors = $this->validate($body, [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'name' => 'required|string|min:3|max:100',
            'role' => 'required|in:admin,contador,consulta',
            'plantel_id' => 'nullable|integer',
            'phone' => 'nullable|string|max:20'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Verificar email único
        if ($this->repository->findByEmail($body['email'])) {
            return $this->validationError([
                'email' => ['El email ya está registrado']
            ]);
        }

        // Solo admin puede crear otros admins
        $currentUser = $this->getUser();
        if ($body['role'] === 'admin' && $currentUser['role'] !== 'admin') {
            return $this->forbidden('No tiene permisos para crear administradores');
        }

        // Si no es admin, asignar al plantel del usuario actual
        $plantelId = $this->getPlantelId();
        if ($body['role'] !== 'admin' && $plantelId !== null) {
            $body['plantel_id'] = $plantelId;
        }

        $user = $this->repository->create([
            'plantel_id' => $body['role'] === 'admin' ? null : ($body['plantel_id'] ?? null),
            'email' => $body['email'],
            'password' => password_hash($body['password'], PASSWORD_DEFAULT),
            'name' => $body['name'],
            'role' => $body['role'],
            'phone' => $body['phone'] ?? null,
            'status' => 'active'
        ]);

        $this->auditService->logCreate('users', $user->getId(), $user->toArray());

        return $this->created($user, 'Usuario creado exitosamente');
    }

    public function update(int $id, array $body): array
    {
        $user = $this->repository->find($id);

        if (!$user) {
            return $this->notFound('Usuario no encontrado');
        }

        // Verificar acceso
        $currentUser = $this->getUser();
        $plantelId = $this->getPlantelId();
        
        if ($plantelId !== null && $user->plantel_id !== $plantelId && $user->getId() !== $currentUser['id']) {
            return $this->forbidden('No tiene acceso a este usuario');
        }

        $errors = $this->validate($body, [
            'email' => 'nullable|email',
            'name' => 'nullable|string|min:3|max:100',
            'role' => 'nullable|in:admin,contador,consulta',
            'plantel_id' => 'nullable|integer',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Verificar email único si se está cambiando
        if (isset($body['email']) && $body['email'] !== $user->email) {
            if ($this->repository->findByEmail($body['email'])) {
                return $this->validationError([
                    'email' => ['El email ya está registrado']
                ]);
            }
        }

        // Solo admin puede cambiar roles
        if (isset($body['role']) && $currentUser['role'] !== 'admin') {
            unset($body['role']);
        }

        // No permitir que el usuario se desactive a sí mismo
        if (isset($body['status']) && $body['status'] === 'inactive' && $user->getId() === $currentUser['id']) {
            return $this->error('No puede desactivar su propia cuenta');
        }

        $oldValues = $user->toArray();
        $updated = $this->repository->update($id, $body);

        $this->auditService->logUpdate('users', $id, $oldValues, $updated->toArray());

        return $this->success($updated, 'Usuario actualizado exitosamente');
    }

    public function resetPassword(int $id, array $body): array
    {
        $user = $this->repository->find($id);

        if (!$user) {
            return $this->notFound('Usuario no encontrado');
        }

        // Solo admin o el propio usuario puede resetear contraseña
        $currentUser = $this->getUser();
        if ($currentUser['role'] !== 'admin' && $user->getId() !== $currentUser['id']) {
            return $this->forbidden('No tiene permisos para esta acción');
        }

        $errors = $this->validate($body, [
            'password' => 'required|min:8'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        $this->authService->resetPassword($id, $body['password']);

        $this->auditService->logUpdate('users', $id, ['password' => '***'], ['password' => '***']);

        return $this->success(null, 'Contraseña actualizada exitosamente');
    }

    public function destroy(int $id): array
    {
        $user = $this->repository->find($id);

        if (!$user) {
            return $this->notFound('Usuario no encontrado');
        }

        // No permitir eliminar al propio usuario
        $currentUser = $this->getUser();
        if ($user->getId() === $currentUser['id']) {
            return $this->error('No puede eliminar su propia cuenta');
        }

        // Verificar acceso
        $plantelId = $this->getPlantelId();
        if ($plantelId !== null && $user->plantel_id !== $plantelId) {
            return $this->forbidden('No tiene acceso a este usuario');
        }

        $oldValues = $user->toArray();
        $this->repository->softDelete($id);
        
        $this->auditService->logDelete('users', $id, $oldValues);

        return $this->success(null, 'Usuario eliminado exitosamente');
    }
}
