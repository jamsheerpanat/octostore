#!/bin/bash

# OctoStore Tenant Provisioning Script
# Usage: ./provision_tenant.sh "My Store Name" "mystore" "admin@mystore.com" "password123"

STORE_NAME=$1
SUBDOMAIN=$2
ADMIN_EMAIL=$3
ADMIN_PASS=$4
PLAN_ID=${5:-1} # Default plan 1

if [ -z "$STORE_NAME" ] || [ -z "$SUBDOMAIN" ]; then
  echo "Usage: ./provision_tenant.sh <Name> <Subdomain> <Email> <Password>"
  exit 1
fi

echo "Provisioning Tenant: $STORE_NAME ($SUBDOMAIN.octostore.com)..."

# Call the API to create tenant
# Need a Super Admin Token ideally, or use Artisan if local
# Using Artisan for CLI simplicity

docker exec octostore_app php artisan tenant:create "$STORE_NAME" "$SUBDOMAIN" "$ADMIN_EMAIL" "$ADMIN_PASS" --plan=$PLAN_ID

echo "Done!"
