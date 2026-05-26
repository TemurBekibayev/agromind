# Laravel Sanctum Setup & Usage Guide for AgroMind

Laravel Sanctum is used for API token issuance and verification for the mobile app (Flutter client). Since the API installation command has been run, Sanctum is already installed. Follow these configuration steps to ensure everything runs smoothly.

---

## 1. Configure the User Model

Make sure the `HasApiTokens` trait is imported and used in the `App\Models\User` model. This is already implemented in `app/Models/User.php`:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    // ...
}
```

---

## 2. API Middleware Configuration

In Laravel 11, the middleware stack is configured in `bootstrap/app.php`. When `install:api` is run, Laravel automatically sets up the Sanctum middleware in the API route group.

Verify that your `bootstrap/app.php` contains the api configuration:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

---

## 3. Database Configurations

To run the API token schema and write the personal access tokens to the database, run:

```bash
# Runs migrations, including the token schema managed internally by Sanctum
php artisan migrate
```

---

## 4. Connection Details & Request Headers

To make requests to protected API endpoints, clients must include the generated token in the HTTP `Authorization` header as a Bearer token.

### Login Request (Public)
* **Endpoint**: `POST /api/auth/login`
* **Body (JSON)**:
```json
{
  "phone": "+998901234567",
  "password": "your_secure_password",
  "device_name": "android_phone"
}
```
* **Response**:
```json
{
  "status": "success",
  "message": "Muvaffaqiyatli tizimga kirildi.",
  "token": "1|abcdef1234567890...",
  "user": {
    "id": 1,
    "name": "Eldor Alimov",
    "phone": "+998901234567",
    "role": "farmer",
    "region_id": 2
  }
}
```

### Authenticated Request
For all subsequent requests under the `/api/*` guard, supply the following header:
* **Headers**:
  * `Authorization`: `Bearer 1|abcdef1234567890...`
  * `Accept`: `application/json`
  * `Content-Type`: `application/json`

---

## 5. Localhost Testing (IP Binding)
When connecting from a physical phone or an Android emulator to your local development machine:
1. Ensure your device is on the same local network.
2. Bind Laravel to `0.0.0.0` or run it via Docker Compose.
3. Access the APIs via `http://<YOUR_LOCAL_IP>/api/...` (instead of `localhost`).
