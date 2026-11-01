#!/bin/bash
#
# RedStar Platform - Production Deployment Script
#
# This script guides you through deploying the RedStar multi-brand CRM platform
# to production on Laravel Forge (forge@64.227.66.233).
#
# Prerequisites:
#   1. Laravel Forge site configured for manage.redstar.transportmarketing.net
#   2. MySQL database created on production server
#   3. SSH access to forge@64.227.66.233
#
# Usage:
#   ./deploy.sh [step]
#
# Steps:
#   1. setup-database    - Create MySQL database and user
#   2. copy-old-crm      - Copy old CRM database to server
#   3. deploy-code       - Deploy Laravel code to server
#   4. run-migrations    - Execute migrations (including data migration)
#   5. verify            - Verify deployment and data counts
#   6. full              - Run all steps sequentially
#

set -e  # Exit on error

# Configuration
SERVER="forge@64.227.66.233"
SITE_DIR="/home/forge/manage.redstar.transportmarketing.net"
DB_NAME="redstar_platform"
DB_USER="redstar_user"
OLD_CRM_PATH="$HOME/work/redstar/database/database.sqlite"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Helper functions
info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

confirm() {
    read -p "$1 (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        error "Deployment cancelled by user"
    fi
}

# Step 1: Setup Database
setup_database() {
    info "Step 1: Creating MySQL database on production server..."

    warn "This will SSH into $SERVER and create database '$DB_NAME'"
    confirm "Continue?"

    # Generate strong random password
    DB_PASSWORD=$(openssl rand -base64 24 | tr -d "=+/" | cut -c1-24)

    info "Generated database password: $DB_PASSWORD"
    warn "SAVE THIS PASSWORD! You'll need it for .env configuration."

    # Create database via SSH
    ssh $SERVER << EOF
mysql -u forge -p << 'MYSQL_SCRIPT'
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
MYSQL_SCRIPT
EOF

    info "Database created successfully!"
    info "Update production .env with:"
    echo "DB_CONNECTION=mysql"
    echo "DB_DATABASE=$DB_NAME"
    echo "DB_USERNAME=$DB_USER"
    echo "DB_PASSWORD=$DB_PASSWORD"
}

# Step 2: Copy Old CRM Database
copy_old_crm() {
    info "Step 2: Copying old CRM database to production server..."

    if [ ! -f "$OLD_CRM_PATH" ]; then
        error "Old CRM database not found at: $OLD_CRM_PATH"
    fi

    info "Database found ($(du -h "$OLD_CRM_PATH" | cut -f1))"
    confirm "Copy to $SERVER:/tmp/old_crm.sqlite?"

    scp "$OLD_CRM_PATH" "$SERVER:/tmp/old_crm.sqlite"

    info "Old CRM database copied to /tmp/old_crm.sqlite on production"
    info "Set in production .env: OLD_CRM_DB_PATH=/tmp/old_crm.sqlite"
}

# Step 3: Deploy Code
deploy_code() {
    info "Step 3: Deploying code to production server..."

    warn "This assumes you've already:"
    echo "  1. Created Forge site for manage.redstar.transportmarketing.net"
    echo "  2. Configured Git repository in Forge"
    echo "  3. Pushed code to Git remote"
    confirm "Continue?"

    # Manual deployment commands (user should adapt for their setup)
    cat << 'DEPLOY_INSTRUCTIONS'

    DEPLOYMENT INSTRUCTIONS:
    ========================

    If using GitHub:
    ----------------
    1. Create GitHub repository: github.com/yourusername/redstar-platform
    2. Add remote: git remote add origin git@github.com:yourusername/redstar-platform.git
    3. Push code: git push -u origin main
    4. In Forge: New Site → manage.redstar.transportmarketing.net
       - Git Repository: yourusername/redstar-platform
       - Branch: main
       - Enable Quick Deploy
    5. Deploy via Forge dashboard

    If manually copying files:
    -------------------------
    rsync -avz --exclude='.git' \
          --exclude='node_modules' \
          --exclude='vendor' \
          --exclude='storage/logs/*' \
          . $SERVER:$SITE_DIR/

    Then SSH in and run:
        cd $SITE_DIR
        composer install --no-dev --optimize-autoloader
        npm ci && npm run build

DEPLOY_INSTRUCTIONS

    warn "Follow instructions above, then press Enter to continue..."
    read
}

# Step 4: Run Migrations
run_migrations() {
    info "Step 4: Running migrations on production server..."

    confirm "This will run migrations and migrate old CRM data. Continue?"

    ssh $SERVER << EOF
cd $SITE_DIR

# Verify .env exists
if [ ! -f .env ]; then
    echo "ERROR: .env file not found!"
    exit 1
fi

# Run migrations
php artisan migrate --force

# Seed entities (RedStar, BlueLine, GreenPromo, AmberLane)
php artisan db:seed --class=InitialDataSeeder --force

# Migrate old CRM data (checks for OLD_CRM_DB_PATH env)
php artisan migrate --path=database/migrations/2026_11_05_000001_migrate_old_crm_data.php --force

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Link storage
php artisan storage:link

echo ""
echo "Migrations completed!"
EOF

    info "Migrations completed successfully!"
}

# Step 5: Verify Deployment
verify_deployment() {
    info "Step 5: Verifying deployment..."

    ssh $SERVER << 'EOF'
cd $SITE_DIR

echo "=== Data Counts ==="
php artisan tinker --execute="
  echo 'Entities: ' . \App\Models\Entity::count() . PHP_EOL;
  echo 'Clients: ' . \App\Models\Client::count() . PHP_EOL;
  echo 'Projects: ' . \App\Models\Project::count() . PHP_EOL;
  echo 'Tasks: ' . \App\Models\Task::count() . PHP_EOL;
  echo 'Payments: ' . \App\Models\Payment::count() . PHP_EOL;
  echo 'Contacts: ' . \App\Models\Contact::count() . PHP_EOL;
"

echo ""
echo "=== Verify Relationships ==="
php artisan tinker --execute="
  \$client = \App\Models\Client::with('projects.tasks')->first();
  if (\$client) {
    echo 'Sample Client: ' . \$client->name . PHP_EOL;
    echo 'Projects: ' . \$client->projects->count() . PHP_EOL;
    echo 'Tasks: ' . \$client->projects->sum(fn(\$p) => \$p->tasks->count()) . PHP_EOL;
  } else {
    echo 'No clients found (migration may not have run)' . PHP_EOL;
  }
"

echo ""
echo "=== Test Admin Panel ==="
curl -I https://manage.redstar.transportmarketing.net/admin/login 2>&1 | grep -i "HTTP\|strict-transport"

echo ""
echo "=== Queue Worker Status ==="
supervisorctl status | grep -i queue || echo "Queue worker not configured yet"
EOF

    info "Verification complete!"
    info "Access admin panel: https://manage.redstar.transportmarketing.net/admin"
}

# Full deployment (all steps)
full_deployment() {
    info "Starting full deployment process..."

    setup_database
    echo ""
    copy_old_crm
    echo ""
    deploy_code
    echo ""
    run_migrations
    echo ""
    verify_deployment

    info "=== DEPLOYMENT COMPLETE ==="
    info "Next steps:"
    echo "  1. Configure queue worker daemon in Forge"
    echo "  2. Configure email settings in production .env"
    echo "  3. Create admin user: php artisan make:filament-user"
    echo "  4. Set up AADE sandbox credentials"
    echo "  5. Set up Stripe test mode credentials"
}

# Main script
case "${1:-help}" in
    setup-database)
        setup_database
        ;;
    copy-old-crm)
        copy_old_crm
        ;;
    deploy-code)
        deploy_code
        ;;
    run-migrations)
        run_migrations
        ;;
    verify)
        verify_deployment
        ;;
    full)
        full_deployment
        ;;
    help|*)
        cat << 'HELP'
RedStar Platform - Production Deployment Script

Usage: ./deploy.sh [step]

Steps:
  setup-database    - Create MySQL database and user
  copy-old-crm      - Copy old CRM database to server
  deploy-code       - Deploy Laravel code to server (instructions)
  run-migrations    - Execute migrations (including data migration)
  verify            - Verify deployment and data counts
  full              - Run all steps sequentially

Example (step-by-step):
  ./deploy.sh setup-database
  ./deploy.sh copy-old-crm
  ./deploy.sh deploy-code
  ./deploy.sh run-migrations
  ./deploy.sh verify

Example (full auto):
  ./deploy.sh full

HELP
        ;;
esac
