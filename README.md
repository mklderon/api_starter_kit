# PHP API Starter Kit

A lightweight PHP API framework designed as a starter kit for rapid API development with built-in authentication, middleware system, and database integration.

## Features

- 🚀 **Lightweight & Fast** - Minimal dependencies for optimal performance
- 🔒 **JWT Authentication** - Secure token-based authentication
- 🛡️ **Middleware System** - CORS, Logging, and custom middleware support
- 📊 **Database Integration** - PDO-based database operations with model abstraction
- 🔄 **RESTful Routing** - Clean URL routing with resource controllers
- 📝 **Comprehensive Logging** - Built-in logging system for debugging
- ⚙️ **Environment Configuration** - Easy configuration management
- 🌍 **CORS Support** - Cross-origin resource sharing enabled
- 🕒 **Timezone Support** - Built-in timezone handling

## Prerequisites

- PHP >= 7.4
- Composer (optional, for dependency management)
- MySQL (optional, if using database)
- XAMPP or PHP built-in server

## Installation

1. **Clone or Download the Repository**
   ```bash
   git clone <repository-url>
   cd <project-directory>
   ```

2. **Set up Environment**
   ```bash
   # Copy environment file
   cp .env.example .env
   
   # Edit configuration
   nano .env
   ```

3. **Configure Database (Optional)**
   - Set `DB_ENABLE=true` in your `.env` file
   - Configure database connection settings
   - Create your database

4. **Start the Server**
   ```bash
   # Using PHP built-in server
   php -S localhost:8000 -t public
   
   # Or using XAMPP
   # Place project in htdocs folder
   ```

## Project Structure

```
├── app/
│   ├── config/
│   │   ├── config.php          # Application configuration
│   │   └── routes.php          # Route definitions
│   ├── controllers/            # Application controllers
│   ├── helpers/
│   │   └── functions.php       # Helper functions
│   ├── middleware/
│   │   ├── CorsMiddleware.php  # CORS handling
│   │   └── LoggingMiddleware.php # Request logging
│   └── models/                 # Database models
├── core/
│   ├── Controller.php          # Base controller class
│   ├── Database.php            # Database connection handler
│   ├── Framework.php           # Main framework class
│   ├── JWT.php                 # JWT token handling
│   ├── Logger.php              # Logging system
│   ├── Middleware.php          # Base middleware class
│   ├── Model.php               # Base model class
│   ├── Response.php            # HTTP response handler
│   └── Router.php              # URL routing system
├── public/
│   └── index.php               # Application entry point
├── storage/
│   └── logs/                   # Application logs
├── .env.example                # Environment configuration template
└── README.md                   # This file
```

## Configuration

### Environment Variables

Edit your `.env` file to configure the application:

```env
# Application
APP_NAME=StarterKit
APP_URL=http://localhost
APP_ENV=development
DEBUG=true
TIMEZONE=UTC

# Database (set DB_ENABLE=true to use database)
DB_ENABLE=false
DB_HOST=localhost
DB_PORT=3306
DB_NAME=db_starter_kit
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

# JWT Authentication
JWT_SECRET=your-super-secret-jwt-key-change-this
JWT_EXPIRATION=3600

# CORS Configuration
CORS_ORIGINS=*
```

## Usage

### Basic Routing

Define routes in `app/config/routes.php`:

```php
use Core\Framework;

$app = Framework::app();

// Simple route
$app->get('/hello', function() {
    response()->success(['message' => 'Hello World!']);
});

// Route with parameters
$app->get('/user/{id}', function($id) {
    response()->success(['user_id' => $id]);
});

// Controller route
$app->get('/users', ['UserController', 'index']);

// Resource routes (generates all CRUD routes)
$app->resource('users', 'UserController');
```

### Creating Controllers

Create controllers in `app/controllers/`:

```php
<?php

namespace App\Controllers;

use Core\Controller;

class UserController extends Controller
{
    protected $model = 'App\Models\User';
    
    public function index()
    {
        try {
            $users = $this->model->all();
            response()->success($users);
        } catch (\Exception $e) {
            response()->error($e->getMessage(), 500);
        }
    }
    
    public function show($id)
    {
        try {
            $user = $this->model->find($id);
            if (!$user) {
                response()->error('User not found', 404);
                return;
            }
            response()->success($user);
        } catch (\Exception $e) {
            response()->error($e->getMessage(), 500);
        }
    }
    
    public function store()
    {
        $data = $this->getRequestData();
        
        $this->validate($data, [
            'name' => 'required',
            'email' => 'required'
        ]);
        
        try {
            $this->model->create($data);
            response()->success(['message' => 'User created successfully'], 201);
        } catch (\Exception $e) {
            response()->error($e->getMessage(), 500);
        }
    }
}
```

### Creating Models

Create models in `app/models/`:

```php
<?php

namespace App\Models;

use Core\Model;

class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'email', 'password'];
    protected $invisible = ['password', 'created_at', 'updated_at'];
    
    // Custom methods
    public function findByEmail($email)
    {
        return $this->db->query(
            "SELECT * FROM {$this->table} WHERE email = ?", 
            [$email]
        )->fetch();
    }
}
```

### JWT Authentication

```php
// Generate token
$payload = ['user_id' => 1, 'email' => 'user@example.com'];
$token = jwt()->encode($payload);

// Verify token
try {
    $decoded = jwt()->decode($token);
    echo $decoded['user_id'];
} catch (Exception $e) {
    echo 'Invalid token: ' . $e->getMessage();
}

// Get token from header
$token = jwt()->getToken();
```

### Middleware

Create custom middleware in `app/middleware/`:

```php
<?php

namespace App\Middleware;

use Core\Middleware;

class AuthMiddleware extends Middleware
{
    public function handle()
    {
        $token = jwt()->getToken();
        
        if (!$token) {
            response()->error('Token required', 401);
            return false;
        }
        
        try {
            $decoded = jwt()->decode($token);
            // Token is valid, continue
            return true;
        } catch (Exception $e) {
            response()->error('Invalid token', 401);
            return false;
        }
    }
}
```

Apply middleware to routes:

```php
// Global middleware
$app->addMiddleware('AuthMiddleware');

// Route-specific middleware
$app->get('/protected', ['UserController', 'profile'], ['AuthMiddleware']);
```

### Database Operations

```php
// Using models
$user = new User($db);
$users = $user->all();
$user = $user->find(1);
$user->create(['name' => 'John', 'email' => 'john@example.com']);
$user->update(1, ['name' => 'John Updated']);
$user->delete(1);

// Direct database queries
$db = database();
$users = $db->query("SELECT * FROM users")->fetchAll();
$db->insert('users', ['name' => 'Jane', 'email' => 'jane@example.com']);
```

### Helper Functions

```php
// Environment
$appName = env('APP_NAME');
$debug = env('DEBUG', false);

// Configuration
$dbHost = config('database.host');
$jwtSecret = config('jwt.secret');

// Response
response()->success(['data' => 'success']);
response()->error('Error message', 400);

// Dates
$now = now(); // Current timestamp
$today = today(); // Today's date
$carbon = carbon('2024-01-01'); // DateTime object
```

## API Examples

### Health Check
```bash
GET /
```

### User Management
```bash
# Get all users
GET /users

# Get specific user
GET /users/1

# Create user
POST /users
{
    "name": "John Doe",
    "email": "john@example.com"
}

# Update user
PUT /users/1
{
    "name": "John Updated"
}

# Delete user
DELETE /users/1
```

## Error Handling

The framework provides comprehensive error handling:

```php
try {
    // Your code here
} catch (\Exception $e) {
    response()->error($e->getMessage(), 500);
}
```

## Logging

Logs are automatically generated in `storage/logs/`:

```php
// Manual logging
debug('Debug information');

// Automatic logging
// - All requests are logged
// - Database operations are logged
// - Errors are logged
// - JWT operations are logged
```

## Security Features

- **JWT Authentication**: Secure token-based authentication
- **CORS Protection**: Configurable cross-origin resource sharing
- **Input Validation**: Built-in validation methods
- **Error Handling**: Comprehensive error management
- **SQL Injection Protection**: PDO prepared statements

## Development

### Debug Mode

Set `DEBUG=true` in your `.env` file to enable debug mode:
- Detailed error messages
- Debug logging
- Development-friendly responses

### Adding New Features

1. **Controllers**: Add to `app/controllers/`
2. **Models**: Add to `app/models/`
3. **Middleware**: Add to `app/middleware/`
4. **Routes**: Define in `app/config/routes.php`
5. **Configuration**: Update `app/config/config.php`

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open-source and available under the MIT License.

## Support

For issues and questions:
- Check the documentation
- Review the example code
- Create an issue in the repository

## Changelog

### v1.0.0
- Initial release
- JWT authentication
- Database integration
- Middleware system
- RESTful routing
- Comprehensive logging