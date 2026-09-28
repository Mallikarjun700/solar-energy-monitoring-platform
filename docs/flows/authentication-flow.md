# Authentication Flow

## Purpose

The authentication flow protects the platform while allowing the Angular application to use fine-grained permissions.

## Flow

```text
User
  |
  v
Angular Login
  |
  v
POST /api/v1/auth/login
  |
  v
Laravel AuthController
  |
  +--> Validate credentials
  |
  +--> Create Sanctum token
  |
  v
Angular token storage
  |
  v
authGuard
  |
  v
Protected Angular route
  |
  v
Laravel auth:sanctum
  |
  v
Ability / role authorization
  |
  v
Controller
```

## 1. Login

The user submits credentials through the Angular login feature.

The frontend calls:

`POST /api/v1/auth/login`

The backend authenticates the user and returns the authentication result.

## 2. Token storage

The frontend stores the Sanctum token using its token-storage mechanism.

The token is then included in authenticated API requests.

## 3. Route protection

The Angular application uses `authGuard` for the authenticated application shell.

Unauthenticated users cannot access the protected application routes.

## 4. Ability protection

Feature routes can add ability checks.

Current examples include:

- telemetry requires `telemetry:read`
- alerts require `alerts:read`

## 5. Role protection

Administration is protected by the Angular role guard and the `admin` role.

Frontend guards improve user experience, but backend authorization remains authoritative.

## 6. Logout

The frontend calls:

`POST /api/v1/auth/logout`

The token is removed from the client-side token storage after successful logout handling.

## Security principles

- Never put credentials in source control.
- Never log access tokens.
- Treat frontend guards as UI protection, not the security boundary.
- Enforce authorization on the Laravel API.
- Use least-privilege abilities.
- Use HTTPS in deployed environments.

## Related implementation

- `backend/routes/api.php`
- `backend/app/Http/Controllers/Api/V1/AuthController.php`
- `frontend/src/app/app.routes.ts`
- Angular auth guard and ability guard implementation
