<?php

declare(strict_types=1);

namespace App\Controllers;

abstract class BaseController
{
    protected function json(mixed $data, int $statusCode = 200): array
    {
        http_response_code($statusCode);
        return ['data' => $data, 'status' => $statusCode];
    }

    protected function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): array
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    protected function error(string $message, int $statusCode = 400, mixed $errors = null): array
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    protected function created(mixed $data, string $message = 'Recurso creado exitosamente'): array
    {
        return $this->success($data, $message, 201);
    }

    protected function notFound(string $message = 'Recurso no encontrado'): array
    {
        return $this->error($message, 404);
    }

    protected function unauthorized(string $message = 'No autorizado'): array
    {
        return $this->error($message, 401);
    }

    protected function forbidden(string $message = 'Acceso denegado'): array
    {
        return $this->error($message, 403);
    }

    protected function validationError(array $errors): array
    {
        return $this->error('Error de validación', 422, $errors);
    }

    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                $error = $this->validateRule($field, $value, $rule, $params, $data);
                if ($error !== null) {
                    $errors[$field][] = $error;
                }
            }
        }

        return $errors;
    }

    private function validateRule(string $field, mixed $value, string $rule, array $params, array $data): ?string
    {
        $fieldLabel = str_replace('_', ' ', $field);

        return match ($rule) {
            'required' => $value === null || $value === '' 
                ? "El campo {$fieldLabel} es requerido" 
                : null,
            
            'string' => !is_string($value) && $value !== null 
                ? "El campo {$fieldLabel} debe ser texto" 
                : null,
            
            'numeric' => !is_numeric($value) && $value !== null 
                ? "El campo {$fieldLabel} debe ser numérico" 
                : null,
            
            'integer' => !is_int($value) && !ctype_digit((string) $value) && $value !== null 
                ? "El campo {$fieldLabel} debe ser un número entero" 
                : null,
            
            'email' => !filter_var($value, FILTER_VALIDATE_EMAIL) && $value !== null 
                ? "El campo {$fieldLabel} debe ser un email válido" 
                : null,
            
            'min' => strlen((string) $value) < (int) ($params[0] ?? 0) && $value !== null 
                ? "El campo {$fieldLabel} debe tener al menos {$params[0]} caracteres" 
                : null,
            
            'max' => strlen((string) $value) > (int) ($params[0] ?? PHP_INT_MAX) && $value !== null 
                ? "El campo {$fieldLabel} debe tener máximo {$params[0]} caracteres" 
                : null,
            
            'in' => !in_array($value, $params) && $value !== null 
                ? "El campo {$fieldLabel} debe ser uno de: " . implode(', ', $params) 
                : null,
            
            'date' => !strtotime($value) && $value !== null 
                ? "El campo {$fieldLabel} debe ser una fecha válida" 
                : null,
            
            'confirmed' => $value !== ($data[$field . '_confirmation'] ?? null) 
                ? "La confirmación de {$fieldLabel} no coincide" 
                : null,
            
            'nullable' => null,
            
            default => null
        };
    }

    protected function getPaginationParams(array $query): array
    {
        return [
            'page' => max(1, (int) ($query['page'] ?? 1)),
            'per_page' => min(100, max(1, (int) ($query['per_page'] ?? 15)))
        ];
    }

    protected function getUser(): ?array
    {
        return $GLOBALS['auth_user'] ?? null;
    }

    protected function getUserId(): ?int
    {
        return $this->getUser()['id'] ?? null;
    }

    protected function getPlantelId(): ?int
    {
        return $GLOBALS['plantel_scope'] ?? $this->getUser()['plantelId'] ?? null;
    }
}
