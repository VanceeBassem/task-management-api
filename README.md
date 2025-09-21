## Task Management API
A Laravel-based Task Management API with role-based access control powered by Spatie Laravel Permission.
Managers can create and assign tasks. Users can view and update only their own tasks.


## Setup
1. Clone the repo & install dependencies:
git clone https://github.com/VanceeBassem/task-management-api.git
cd task-api
composer install

2. Configure .env with your database settings from .env.example.

3. Run:
php artisan key:generate
php artisan migrate --seed
php artisan serve

## Default Users
Manager → manager@example.test / password
User → user@example.test / password
These users and roles are seeded using Spatie’s Role & Permission package.

## Authentication
. Uses Laravel Sanctum.
. Login with:
POST /api/login
{
  "email": "manager@example.test",
  "password": "password"
}
. Use the returned token with Authorization: Bearer <token>.

## Main Endpoints
. POST /api/login → User Login
. POST /api/tasks → Create task (Manager only)
. GET /api/tasks → List tasks (filter by status, due date, assignee)
. GET /api/tasks/{id} → Task details (with dependencies)
. PUT /api/tasks/{id} → Update task (title, description, assignee, due date)
. PATCH /api/tasks/{id}/status → Update task status (User allowed)
. POST /api/tasks/{id}/dependencies → Add dependencies

## Dependencies & Packages
1. Laravel Sanctum

Sanctum is used to handle API authentication.
It provides a simple token-based authentication system for SPAs, mobile apps, and APIs.
Installed with:

composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate

2. Spatie Laravel Permission

Spatie’s package is used for role-based access control (RBAC).
It allows assigning roles (manager, user) to users and restricting actions based on their role.
Installed with:

composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
