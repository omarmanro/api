<?php

declare(strict_types=1);

use App\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\PlantelScopeMiddleware;

use App\Controllers\AuthController;
use App\Controllers\PlantelController;
use App\Controllers\StudentController;
use App\Controllers\PaymentController;
use App\Controllers\ExpenseController;
use App\Controllers\ExpenseCategoryController;
use App\Controllers\ReportController;
use App\Controllers\DashboardController;
use App\Controllers\UserController;
use App\Controllers\BackupController;
use App\Controllers\SchoolCycleController;
use App\Controllers\AuditController;

return function (Router $router): void {
    
    // Middleware global CORS
    $cors = new CorsMiddleware();
    
    // Middleware de autenticación
    $auth = new AuthMiddleware();
    
    // Middleware de roles
    $adminOnly = new RoleMiddleware(['admin']);
    $adminOrContador = new RoleMiddleware(['admin', 'contador']);
    $allRoles = new RoleMiddleware(['admin', 'contador', 'consulta']);
    
    // Middleware de scope de plantel
    $plantelScope = new PlantelScopeMiddleware();

    // ========================================
    // RUTAS PÚBLICAS (sin autenticación)
    // ========================================
    
    // Health check
    $router->get('/api/health', fn() => [
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ]);

    // Autenticación
    $router->post('/api/auth/login', [AuthController::class, 'login'], [$cors]);
    $router->post('/api/auth/refresh', [AuthController::class, 'refresh'], [$cors]);

    // ========================================
    // RUTAS PROTEGIDAS (requieren autenticación)
    // ========================================

    // Auth - Usuario actual
    $router->get('/api/auth/me', [AuthController::class, 'me'], [$cors, $auth]);
    $router->post('/api/auth/logout', [AuthController::class, 'logout'], [$cors, $auth]);
    $router->post('/api/auth/change-password', [AuthController::class, 'changePassword'], [$cors, $auth]);

    // ----------------------------------------
    // DASHBOARD
    // ----------------------------------------
    $router->get('/api/dashboard', [DashboardController::class, 'index'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/dashboard/summary', [DashboardController::class, 'summary'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/dashboard/charts', [DashboardController::class, 'charts'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/dashboard/alerts', [DashboardController::class, 'alerts'], [$cors, $auth, $allRoles, $plantelScope]);

    // ----------------------------------------
    // PLANTELES
    // ----------------------------------------
    $router->get('/api/planteles', [PlantelController::class, 'index'], [$cors, $auth, $allRoles]);
    $router->get('/api/planteles/{id}', [PlantelController::class, 'show'], [$cors, $auth, $allRoles]);
    $router->post('/api/planteles', [PlantelController::class, 'store'], [$cors, $auth, $adminOnly]);
    $router->put('/api/planteles/{id}', [PlantelController::class, 'update'], [$cors, $auth, $adminOnly]);
    $router->delete('/api/planteles/{id}', [PlantelController::class, 'destroy'], [$cors, $auth, $adminOnly]);

    // ----------------------------------------
    // CICLOS ESCOLARES
    // ----------------------------------------
    $router->get('/api/cycles', [SchoolCycleController::class, 'index'], [$cors, $auth, $allRoles]);
    $router->get('/api/cycles/active', [SchoolCycleController::class, 'active'], [$cors, $auth, $allRoles]);
    $router->get('/api/cycles/{id}', [SchoolCycleController::class, 'show'], [$cors, $auth, $allRoles]);
    $router->post('/api/cycles', [SchoolCycleController::class, 'store'], [$cors, $auth, $adminOnly]);
    $router->put('/api/cycles/{id}', [SchoolCycleController::class, 'update'], [$cors, $auth, $adminOnly]);
    $router->post('/api/cycles/{id}/activate', [SchoolCycleController::class, 'activate'], [$cors, $auth, $adminOnly]);
    $router->delete('/api/cycles/{id}', [SchoolCycleController::class, 'destroy'], [$cors, $auth, $adminOnly]);

    // ----------------------------------------
    // ESTUDIANTES
    // ----------------------------------------
    $router->get('/api/students', [StudentController::class, 'index'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/students/{id}', [StudentController::class, 'show'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/students/{id}/payments', [StudentController::class, 'payments'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->post('/api/students', [StudentController::class, 'store'], [$cors, $auth, $adminOrContador, $plantelScope]);
    $router->put('/api/students/{id}', [StudentController::class, 'update'], [$cors, $auth, $adminOrContador, $plantelScope]);
    $router->delete('/api/students/{id}', [StudentController::class, 'destroy'], [$cors, $auth, $adminOnly, $plantelScope]);

    // ----------------------------------------
    // PAGOS
    // ----------------------------------------
    $router->get('/api/payments', [PaymentController::class, 'index'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/payments/by-method', [PaymentController::class, 'byMethod'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/payments/daily', [PaymentController::class, 'daily'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/payments/{id}', [PaymentController::class, 'show'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->post('/api/payments', [PaymentController::class, 'store'], [$cors, $auth, $adminOrContador, $plantelScope]);
    $router->put('/api/payments/{id}', [PaymentController::class, 'update'], [$cors, $auth, $adminOrContador, $plantelScope]);
    $router->post('/api/payments/{id}/cancel', [PaymentController::class, 'cancel'], [$cors, $auth, $adminOrContador, $plantelScope]);

    // ----------------------------------------
    // GASTOS
    // ----------------------------------------
    $router->get('/api/expenses', [ExpenseController::class, 'index'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/expenses/pending', [ExpenseController::class, 'pending'], [$cors, $auth, $adminOrContador, $plantelScope]);
    $router->get('/api/expenses/by-category', [ExpenseController::class, 'byCategory'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/expenses/{id}', [ExpenseController::class, 'show'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->post('/api/expenses', [ExpenseController::class, 'store'], [$cors, $auth, $adminOrContador, $plantelScope]);
    $router->put('/api/expenses/{id}', [ExpenseController::class, 'update'], [$cors, $auth, $adminOrContador, $plantelScope]);
    $router->post('/api/expenses/{id}/approve', [ExpenseController::class, 'approve'], [$cors, $auth, $adminOrContador, $plantelScope]);
    $router->post('/api/expenses/{id}/reject', [ExpenseController::class, 'reject'], [$cors, $auth, $adminOrContador, $plantelScope]);
    $router->delete('/api/expenses/{id}', [ExpenseController::class, 'destroy'], [$cors, $auth, $adminOrContador, $plantelScope]);

    // ----------------------------------------
    // CATEGORÍAS DE GASTOS
    // ----------------------------------------
    $router->get('/api/expense-categories', [ExpenseCategoryController::class, 'index'], [$cors, $auth, $allRoles]);
    $router->get('/api/expense-categories/{id}', [ExpenseCategoryController::class, 'show'], [$cors, $auth, $allRoles]);
    $router->post('/api/expense-categories', [ExpenseCategoryController::class, 'store'], [$cors, $auth, $adminOnly]);
    $router->put('/api/expense-categories/{id}', [ExpenseCategoryController::class, 'update'], [$cors, $auth, $adminOnly]);
    $router->delete('/api/expense-categories/{id}', [ExpenseCategoryController::class, 'destroy'], [$cors, $auth, $adminOnly]);

    // ----------------------------------------
    // REPORTES
    // ----------------------------------------
    $router->get('/api/reports/daily', [ReportController::class, 'daily'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/reports/weekly', [ReportController::class, 'weekly'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/reports/monthly', [ReportController::class, 'monthly'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/reports/custom', [ReportController::class, 'custom'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/reports/consolidated', [ReportController::class, 'consolidated'], [$cors, $auth, $adminOnly]);
    $router->get('/api/reports/income-by-type', [ReportController::class, 'incomeByType'], [$cors, $auth, $allRoles, $plantelScope]);

    // Exportar reportes a Excel
    $router->get('/api/reports/export/daily', [ReportController::class, 'exportDaily'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/reports/export/weekly', [ReportController::class, 'exportWeekly'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/reports/export/monthly', [ReportController::class, 'exportMonthly'], [$cors, $auth, $allRoles, $plantelScope]);
    $router->get('/api/reports/export/custom', [ReportController::class, 'exportCustom'], [$cors, $auth, $allRoles, $plantelScope]);
    
    // Descargar archivo exportado
    $router->get('/api/reports/download/{filename}', [ReportController::class, 'download'], [$cors, $auth, $allRoles]);

    // ----------------------------------------
    // USUARIOS
    // ----------------------------------------
    $router->get('/api/users', [UserController::class, 'index'], [$cors, $auth, $adminOrContador, $plantelScope]);
    $router->get('/api/users/{id}', [UserController::class, 'show'], [$cors, $auth, $adminOrContador, $plantelScope]);
    $router->post('/api/users', [UserController::class, 'store'], [$cors, $auth, $adminOnly]);
    $router->put('/api/users/{id}', [UserController::class, 'update'], [$cors, $auth, $adminOrContador, $plantelScope]);
    $router->post('/api/users/{id}/reset-password', [UserController::class, 'resetPassword'], [$cors, $auth, $adminOnly]);
    $router->delete('/api/users/{id}', [UserController::class, 'destroy'], [$cors, $auth, $adminOnly]);

    // ----------------------------------------
    // BACKUPS (solo admin)
    // ----------------------------------------
    $router->get('/api/backups', [BackupController::class, 'index'], [$cors, $auth, $adminOnly]);
    $router->post('/api/backups', [BackupController::class, 'create'], [$cors, $auth, $adminOnly]);
    $router->post('/api/backups/restore', [BackupController::class, 'restore'], [$cors, $auth, $adminOnly]);
    $router->get('/api/backups/logs', [BackupController::class, 'logs'], [$cors, $auth, $adminOnly]);
    $router->post('/api/backups/clean', [BackupController::class, 'clean'], [$cors, $auth, $adminOnly]);

    // ----------------------------------------
    // AUDITORÍA (solo admin)
    // ----------------------------------------
    $router->get('/api/audit', [AuditController::class, 'index'], [$cors, $auth, $adminOnly, $plantelScope]);
    $router->get('/api/audit/{table}', [AuditController::class, 'byTable'], [$cors, $auth, $adminOnly]);
    $router->get('/api/audit/{table}/{id}', [AuditController::class, 'byRecord'], [$cors, $auth, $adminOnly]);
};
