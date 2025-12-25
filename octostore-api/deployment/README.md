# Deployment Guide

## 1. Docker Deployment (Recommended)

### Prerequisites
- Docker & Docker Compose
- A domain name pointing to your server IP (e.g., `*.octostore.com` -> `A Record` -> `IP`)

### Steps
1. **Copy Configuration**:
   ```bash
   cp .env.example .env
   # Edit .env with secure DB passwords and domain
   ```

2. **Start Services**:
   ```bash
   cd deployment/docker
   docker-compose up -d --build
   ```

3. **Initialize Master DB**:
   ```bash
   docker exec -it octostore_app php artisan migrate --seed
   ```

4. **Nginx Wildcard Setup**:
   Ensure `deployment/docker/nginx/conf.d/app.conf` has your correct domain in `server_name`.

---

## 2. Hostinger / Shared Hosting Deployment

If Docker is not an option (e.g., standard cPanel/Hostinger Shared):

### Database Strategy
- **Master DB**: Create one manually via panel.
- **Tenant DBs**: The API code creates databases like `octostore_tenant_*`.
- **CRITICAL**: The MySQL user configured in `.env` MUST have `CREATE DATABASE` privileges.
- Shared hosts often prefix DB names (e.g., `u12345_octostore_...`).
  - *Modification*: You may need to update `TenantManagementController` to prepend the host's username requirement to DB names.

### Subdomains
- **Wildcards**: Enable Wildcard Subdomains (`*.yourdomain.com`) in your hosting panel (cPanel/hPanel) to point to the `public_html/octostore-api/public` folder.
- If Wildcards aren't supported, you must manually create a subdomain for each new tenant in the hosting panel, which defeats auto-provisioning.

### Environment
- PHP 8.2+
- Extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML.
- Redis (Optional but recommended). If not available, set `CACHE_DRIVER=file` or `database`.

---

## 3. Provisioning New Tenants (Production)

### Method A: Super Admin API
Call `POST /api/v1/super-admin/tenants` with a secure token.

### Method B: CLI Helper (Server Access)
Run the script:
```bash
./deployment/scripts/provision_tenant.sh "New Store" "store1" "admin@store1.com" "password"
```

## 4. Maintenance
- **Backups**: Run `./deployment/scripts/backup_db.sh` via Cron Job nightly.
- **Updates**: `git pull` then `docker-compose restart app` and run `php artisan migrate` (for master) and `php artisan tenants:migrate` (for all tenants).
