# Forge Deployment Guide - RedStar Platform

## Quick Reference

**Repository:** https://github.com/alexkourtis98/redstar-platform
**Domain:** manage.redstar.transportmarketing.net
**Server:** 64.227.66.233 (forge user)
**Database:** redstar_platform

---

## Step 1: Create Forge Site (Web UI)

In Forge Dashboard → New Site:

- **Domain:** `manage.redstar.transportmarketing.net`
- **Git Repository:** `alexkourtis98/redstar-platform`
- **Branch:** `main`
- **Web Directory:** `/public`
- **PHP Version:** 8.2
- **Quick Deploy:** Enable

---

## Step 2: Create Production Database

SSH into server and create database:

```bash
ssh forge@64.227.66.233

# Generate strong password
DB_PASSWORD=$(openssl rand -base64 24 | tr -d "=+/" | cut -c1-24)
echo "Save this password: $DB_PASSWORD"

# Create database
mysql -u forge -p
```

```sql
CREATE DATABASE redstar_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'redstar_user'@'localhost' IDENTIFIED BY 'PASTE_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON redstar_platform.* TO 'redstar_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## Step 3: Configure Environment

In Forge Dashboard → Site → Environment, update `.env`:

```env
APP_NAME="RedStar Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://manage.redstar.transportmarketing.net

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=redstar_platform
DB_USERNAME=redstar_user
DB_PASSWORD=PASTE_PASSWORD_FROM_STEP_2

QUEUE_CONNECTION=database

# Migration configuration
OLD_CRM_DB_PATH=/tmp/old_crm.sqlite

# Placeholder credentials (configure later)
MAIL_MAILER=log
STRIPE_KEY=pk_test_placeholder
VIVA_ENVIRONMENT=demo
AADE_ENVIRONMENT=sandbox
```

Click "Save" after updating.

---

## Step 4: Deploy Code

In Forge Dashboard → Site → Deploy Now

Wait for deployment to complete.

---

## Step 5: Run Migrations

```bash
ssh forge@64.227.66.233
cd /home/forge/manage.redstar.transportmarketing.net

# Run migrations
php artisan migrate --force

# Seed entities (RedStar, BlueLine, GreenPromo, AmberLane)
php artisan db:seed --class=InitialDataSeeder --force

# Verify entities created
php artisan tinker --execute="echo \App\Models\Entity::count();"
# Expected: 4
```

---

## Step 6: Migrate Old CRM Data

```bash
# On LOCAL machine: Copy old SQLite database to server
scp ~/work/redstar/database/database.sqlite \
    forge@64.227.66.233:/tmp/old_crm.sqlite

# On SERVER: Run migration
cd /home/forge/manage.redstar.transportmarketing.net
php artisan migrate --path=database/migrations/2026_11_05_000001_migrate_old_crm_data.php --force

# Verify data counts
php artisan tinker --execute="
  echo 'Entities: ' . \App\Models\Entity::count() . PHP_EOL;
  echo 'Clients: ' . \App\Models\Client::count() . PHP_EOL;
  echo 'Projects: ' . \App\Models\Project::count() . PHP_EOL;
  echo 'Tasks: ' . \App\Models\Task::count() . PHP_EOL;
  echo 'Payments: ' . \App\Models\Payment::count() . PHP_EOL;
  echo 'Contacts: ' . \App\Models\Contact::count() . PHP_EOL;
"
# Expected: Entities: 4, Clients: 10, Projects: 22, Tasks: 45, Payments: 17, Contacts: 25
```

---

## Step 7: Configure Queue Worker

In Forge Dashboard → Site → Daemons → New Daemon:

- **Command:** `php artisan queue:work database --sleep=3 --tries=3 --max-time=3600`
- **Directory:** `/home/forge/manage.redstar.transportmarketing.net`
- **User:** `forge`
- **Processes:** 2

---

## Step 8: Enable SSL & Optimize

```bash
# Via Forge Dashboard
# Site → SSL → Let's Encrypt → Obtain Certificate
# After issued: Enable "Force HTTPS"

# On server, optimize caches
ssh forge@64.227.66.233
cd /home/forge/manage.redstar.transportmarketing.net
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Verification Checklist

```bash
# Test admin login
curl -I https://manage.redstar.transportmarketing.net/admin/login
# Expected: 200 OK, Filament login page

# Verify data relationships
ssh forge@64.227.66.233
cd /home/forge/manage.redstar.transportmarketing.net
php artisan tinker --execute="
  \$client = \App\Models\Client::with('projects.tasks', 'contacts')->first();
  echo 'Client: ' . \$client->name . PHP_EOL;
  echo 'Projects: ' . \$client->projects->count() . PHP_EOL;
  echo 'Tasks: ' . \$client->projects->sum(fn(\$p) => \$p->tasks->count()) . PHP_EOL;
  echo 'Contacts: ' . \$client->contacts->count() . PHP_EOL;
"

# Check queue worker
supervisorctl status | grep daemon
# Expected: 2 processes RUNNING
```

---

## Deployment Complete

After all steps:

- ✅ Laravel platform live at https://manage.redstar.transportmarketing.net
- ✅ 4 entities seeded
- ✅ 94 records migrated from old CRM
- ✅ SSL enabled
- ✅ Queue worker running
- ✅ Admin panel accessible at `/admin`

Default admin credentials (change after first login):
- Email: admin@redstarweb.net
- Password: password
