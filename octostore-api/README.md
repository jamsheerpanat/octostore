# OctoStore API (Backend)

## Overview
A production-ready, multi-tenant Laravel backend for OctoStore.
- **Tenancy**: Database-per-tenant architecture.
- **Queue/Cache**: Redis.
- **Container**: Docker + Nginx + PHP 8.3.

## Setup Instructions

### 1. Requirements
- Docker & Docker Compose
- PHP 8.3+ (for local command line)
- Composer

### 2. Installation
```bash
# Install PHP dependencies
composer install

# Copy configuration
cp .env.example .env
php artisan key:generate

# Start Containers
docker-compose up -d --build
```

### 3. Database Setup (Master)
Ensure the `octostore_master` database exists. Then run:
```bash
php artisan migrate
```
This enables the `tenants` table.

### 4. Managing Tenants
#### Create a New Tenant
```bash
# Usage: php artisan tenant:create {name} {domain}
php artisan tenant:create store1 store1.localhost
```
This will:
1. Create a database `tenant_store1_xxxxx`.
2. Register it in the `tenants` table.
3. Run migrations on the new tenant database.

#### Migrate All Tenants
To update all tenant databases with new migrations:
```bash
php artisan tenant:migrate
```

### 5. Deployment
- **CI/CD**: Use `.env.example` as a base.
- **Migrations**:
  - `database/migrations` -> Runs on Master DB.
  - `database/migrations/tenant` -> Runs on Tenant DBs.

## API Documentation
Routes are defined in `routes/api.php`.
To access tenant-specific data, ensure your request `Host` header matches the tenant domain.
```bash
curl -H "Host: store1.localhost" http://localhost:8000/api/products
```
