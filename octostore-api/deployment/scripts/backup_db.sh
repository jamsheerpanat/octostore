#!/bin/bash

# Backup All Databases
TIMESTAMP=$(date +"%F_%T")
BACKUP_DIR="./backups/$TIMESTAMP"
mkdir -p $BACKUP_DIR

echo "Backing up Master Database..."
docker exec octostore_db mysqldump -u root -p$DB_PASSWORD octostore_master > $BACKUP_DIR/master.sql

# Backup Tenant Databases
# Need to list them first. 
# This is a simplified loop. In production, fetch list from DB.

DATABASES=$(docker exec octostore_db mysql -u root -p$DB_PASSWORD -e "SHOW DATABASES LIKE 'octostore_tenant_%';" | grep "octostore_tenant_")

for DB in $DATABASES; do
    echo "Backing up Tenant: $DB..."
    docker exec octostore_db mysqldump -u root -p$DB_PASSWORD $DB > $BACKUP_DIR/$DB.sql
done

echo "Backup Complete. Files in $BACKUP_DIR"
