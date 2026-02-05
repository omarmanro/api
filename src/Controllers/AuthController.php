<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function login(array $body): array
    {
        $errors = $this->validate($body, [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        $result = $this->authService->login($body['email'], $body['password']);

        if ($result === null) {
            return $this->unauthorized('Credenciales inválidas');
        }

        return $this->success($result, 'Inicio de sesión exitoso');
    }

    public function refresh(array $body): array
    {
        $errors = $this->validate($body, [
            'refresh_token' => 'required'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        $result = $this->authService->refreshToken($body['refresh_token']);

        if ($result === null) {
            return $this->unauthorized('Token de refresco inválido o expirado');
        }

        return $this->success($result, 'Token renovado exitosamente');
    }

    public function me(): array
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->unauthorized();
        }

        $fullUser = $this->authService->getUser($user['id']);
        
        if (!$fullUser) {
            return $this->notFound('Usuario no encontrado');
        }

        return $this->success($fullUser->toArray());
    }

    public function changePassword(array $body): array
    {
        $errors = $this->validate($body, [
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'new_password_confirmation' => 'required'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        if ($body['new_password'] !== $body['new_password_confirmation']) {
            return $this->validationError([
                'new_password_confirmation' => ['Las contraseñas no coinciden']
            ]);
        }

        $userId = $this->getUserId();
        $result = $this->authService->changePassword(
            $userId,
            $body['current_password'],
            $body['new_password']
        );

        if (!$result) {
            return $this->error('La contraseña actual es incorrecta');
        }

        return $this->success(null, 'Contraseña actualizada exitosamente');
    }

    public function logout(): array
    {
        // JWT es stateless, el cliente simplemente descarta el token
        return $this->success(null, 'Sesión cerrada exitosamente');
    }
}
