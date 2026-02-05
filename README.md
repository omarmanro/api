# Sistema Contable Escolar API

API REST para sistema de contabilidad escolar desarrollada en PHP 8.1+ con MySQL.

## Características

- **Autenticación JWT** con roles (admin, contador, consulta)
- **Multi-plantel** con aislamiento de datos
- **Reportes financieros** (diarios, semanales, mensuales)
- **Exportación a Excel** con PhpSpreadsheet
- **Dashboard con datos para gráficos** (compatible con Chart.js)
- **Auditoría completa** de todas las operaciones
- **Backups automáticos** con retención configurable

## Requisitos

- PHP 8.1 o superior
- MySQL 5.7+ / MariaDB 10.3+
- Composer
- Apache con mod_rewrite o Nginx

## Instalación

1. **Clonar e instalar dependencias**

```bash
cd api
composer install
```

2. **Configurar entorno**

```bash
cp .env.example .env
# Editar .env con los datos de tu base de datos
```

3. **Crear base de datos**

```bash
mysql -u root -p -e "CREATE DATABASE school_accounting CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

4. **Ejecutar migraciones**

```bash
composer migrate
```

5. **Ejecutar seeders** (datos iniciales)

```bash
composer seed
```

6. **Configurar servidor web**

Para Apache, asegúrate de que mod_rewrite esté habilitado y que el DocumentRoot apunte a `/public`.

## Credenciales por defecto

| Usuario | Email | Contraseña | Rol |
|---------|-------|------------|-----|
| Admin | admin@eeea.edu.mx | Admin123! | admin |
| Contador Central | contador.central@eeea.edu.mx | Contador123! | contador |
| Contador Norte | contador.norte@eeea.edu.mx | Contador123! | contador |
| Consulta | consulta.central@eeea.edu.mx | Consulta123! | consulta |

## Estructura del Proyecto

```
api/
├── bin/                    # Scripts CLI
│   └── backup.php          # Script de backup automático
├── config/
│   └── phinx.php           # Configuración de migraciones
├── database/
│   ├── migrations/         # Migraciones de base de datos
│   └── seeds/              # Datos iniciales
├── public/
│   ├── .htaccess           # Reglas de reescritura
│   └── index.php           # Punto de entrada
├── src/
│   ├── Controllers/        # Controladores REST
│   ├── Middleware/         # Middleware (Auth, CORS, etc.)
│   ├── Models/             # Modelos de datos
│   ├── Repositories/       # Acceso a datos
│   ├── Routes/             # Definición de rutas
│   ├── Services/           # Lógica de negocio
│   ├── Database.php        # Singleton PDO
│   └── Router.php          # Router personalizado
├── storage/
│   ├── backups/            # Backups de BD
│   └── exports/            # Archivos exportados
├── .env.example
├── composer.json
└── README.md
```

## Endpoints API

### Autenticación

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | /api/auth/login | Iniciar sesión |
| POST | /api/auth/refresh | Renovar token |
| GET | /api/auth/me | Usuario actual |
| POST | /api/auth/logout | Cerrar sesión |
| POST | /api/auth/change-password | Cambiar contraseña |

### Dashboard

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | /api/dashboard | Datos completos del dashboard |
| GET | /api/dashboard/summary | Resumen de tarjetas |
| GET | /api/dashboard/charts | Datos para gráficos |
| GET | /api/dashboard/alerts | Alertas del sistema |

### Estudiantes

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | /api/students | Listar estudiantes |
| GET | /api/students/{id} | Ver estudiante |
| GET | /api/students/{id}/payments | Pagos del estudiante |
| POST | /api/students | Crear estudiante |
| PUT | /api/students/{id} | Actualizar estudiante |
| DELETE | /api/students/{id} | Eliminar estudiante |

### Pagos

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | /api/payments | Listar pagos |
| GET | /api/payments/{id} | Ver pago |
| GET | /api/payments/by-method | Totales por método |
| GET | /api/payments/daily | Totales diarios |
| POST | /api/payments | Registrar pago |
| PUT | /api/payments/{id} | Actualizar pago |
| POST | /api/payments/{id}/cancel | Cancelar pago |

### Gastos

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | /api/expenses | Listar gastos |
| GET | /api/expenses/{id} | Ver gasto |
| GET | /api/expenses/pending | Gastos pendientes |
| GET | /api/expenses/by-category | Totales por categoría |
| POST | /api/expenses | Registrar gasto |
| PUT | /api/expenses/{id} | Actualizar gasto |
| POST | /api/expenses/{id}/approve | Aprobar gasto |
| POST | /api/expenses/{id}/reject | Rechazar gasto |
| DELETE | /api/expenses/{id} | Eliminar gasto |

### Reportes

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | /api/reports/daily | Reporte diario |
| GET | /api/reports/weekly | Reporte semanal |
| GET | /api/reports/monthly | Reporte mensual |
| GET | /api/reports/custom | Reporte personalizado |
| GET | /api/reports/consolidated | Reporte consolidado (admin) |
| GET | /api/reports/export/daily | Exportar diario a Excel |
| GET | /api/reports/export/weekly | Exportar semanal a Excel |
| GET | /api/reports/export/monthly | Exportar mensual a Excel |
| GET | /api/reports/export/custom | Exportar personalizado a Excel |

### Administración

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET/POST/PUT/DELETE | /api/planteles/* | CRUD Planteles |
| GET/POST/PUT/DELETE | /api/cycles/* | CRUD Ciclos escolares |
| GET/POST/PUT/DELETE | /api/users/* | CRUD Usuarios |
| GET/POST/PUT/DELETE | /api/expense-categories/* | CRUD Categorías |
| GET | /api/audit | Historial de auditoría |
| GET/POST | /api/backups/* | Gestión de backups |

## Backup Automático

Para programar backups diarios a las 2:00 AM:

### Linux (Cron)

```bash
crontab -e
# Agregar línea:
0 2 * * * /usr/bin/php /var/www/api/bin/backup.php >> /var/log/school-backup.log 2>&1
```

### Windows (Task Scheduler)

1. Abrir "Programador de tareas"
2. Crear tarea básica
3. Configurar disparador: Diario a las 2:00 AM
4. Acción: Iniciar programa
   - Programa: `php`
   - Argumentos: `C:\path\to\api\bin\backup.php`

## Formato de Respuestas

### Éxito

```json
{
  "data": {
    "success": true,
    "message": "Operación exitosa",
    "data": { ... }
  },
  "status": 200
}
```

### Error

```json
{
  "data": {
    "success": false,
    "message": "Error de validación",
    "errors": {
      "email": ["El campo email es requerido"]
    }
  },
  "status": 422
}
```

## Roles y Permisos

| Función | Admin | Contador | Consulta |
|---------|:-----:|:--------:|:--------:|
| Ver dashboard | ✓ | ✓ | ✓ |
| Ver reportes | ✓ | ✓ | ✓ |
| Exportar Excel | ✓ | ✓ | ✓ |
| Registrar pagos | ✓ | ✓ | ✗ |
| Registrar gastos | ✓ | ✓ | ✗ |
| Aprobar gastos | ✓ | ✓ | ✗ |
| Gestionar estudiantes | ✓ | ✓ | ✗ |
| Gestionar usuarios | ✓ | ✗ | ✗ |
| Gestionar planteles | ✓ | ✗ | ✗ |
| Gestionar backups | ✓ | ✗ | ✗ |
| Ver auditoría | ✓ | ✗ | ✗ |
| Reporte consolidado | ✓ | ✗ | ✗ |

## Licencia

Propiedad de EEEA. Uso restringido.
