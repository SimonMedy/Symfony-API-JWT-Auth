# Symfony-API-JWT-Auth

Complete API JWT Authentication system developed using Symfony by myself. This repository serves as a template for new projects.

## Prerequisites

Before you begin, ensure you have the following installed on your system:
- PHP (including Composer)
- Docker

## Project Installation

### 1. Navigate to the project directory:

```bash
cd .\back_symfony\
```

### 2. Modify your php.ini file:

Open your php.ini file located where the PHP executable is situated. Uncomment (remove the semicolon) the following line:

```ini
extension=sodium
```

### 3. Install dependencies using Composer:

```bash
composer install
```

### 4. Start the Docker container for the database:

```bash
docker-compose up -d
```
Once the container is running, you can access phpMyAdmin at the following URL:
```arduino
http://localhost:8080/
```

### 5. Run database migrations:

```bash
php bin/console doctrine:migrations:migrate
```

### 6. Launch the Symfony server:

```bash
symfony serve
```