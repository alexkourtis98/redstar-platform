# RedStar Platform - Production Deployment Guide

**Last Updated:** 2026-11-01
**Target Server:** forge@64.227.66.233 (Laravel Forge)
**Domain:** manage.redstar.transportmarketing.net
**Status:** Ready to Deploy

---

## Overview

This guide deploys the RedStar multi-brand CRM platform to production, including migrating data from the old CRM system (`~/work/redstar/database/database.sqlite`).

**What Gets Deployed:**
- Multi-entity Laravel 12 platform (RedStar, BlueLine, GreenPromo, AmberLane)
- Filament admin panel (`/admin`)
- Payment processing (Stripe + Viva Wallet)
- AADE invoicing integration
- Customer portal (`/portal`)
- Old CRM data (10 clients, 22 projects, 45 tasks, 17 payments, 25 contacts)

**Estimated Time:** 2-3 hours

---

## Prerequisites

### 1. Server Access
```bash
# Verify SSH access
ssh forge@64.227.66.233

# Expected: You should be able to login without errors
```

### 2. Forge Site Configuration
- **Site Name:** `manage.redstar.transportmarketing.net`
- **Web Directory:** `/public`
- **PHP Version:** 8.2
- **Database:** MySQL 8.0

If site doesn't exist yet, create it via Forge dashboard:
1. New Site → manage.redstar.transportmarketing.net
2. Project Type: General PHP/Laravel
3. Web Directory: `/public`

### 3. Git Repository (Optional but Recommended)

**Option A: GitHub (Recommended)**
```bash
# From local machine
cd ~/work/transportmarketing.net/redstar-platform

# Create GitHub repo (via web interface or gh CLI)
gh repo create redstar-platform --private --source=. --remote=origin

# Push code
git push -u origin main
```

Then in Forge:
- Site → Git Repository → Connect to GitHub
- Repository: `yourusername/redstar-platform`
- Branch: `main`
- Enable Quick Deploy

**Option B: Manual rsync (Alternative)**
```bash
# Copy files manually (not recommended for production)
rsync -avz --exclude='.git' \
      --exclude='node_modules' \
      --exclude='vendor' \
      --exclude='storage/logs/*' \
      ~/work/transportmarketing.net/redstar-platform/ \
      forge@64.227.66.233:/home/forge/manage.redstar.transportmarketing.net/
```

---

## Deployment Steps

### Step 1: Create Production Database (10 min)

**1.1 Generate Database Password**
```bash
# On local machine
DB_PASSWORD=$(openssl rand -base64 24 | tr -d "=+/" | cut -c1-24)
echo "Database Password: $DB_PASSWORD"

# IMPORTANT: Save this password! You'll need it for .env
```

**1.2 Create MySQL Database**
```bash
# SSH into server
ssh forge@64.227.66.233

# Create database (you'll be prompted for MySQL root password)
mysql -u forge -p
```

```sql
-- Inside MySQL prompt
CREATE DATABASE redstar_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'redstar_user'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD_FROM_STEP_1.1';
GRANT ALL PRIVILEGES ON redstar_platform.* TO 'redstar_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**1.3 Update Production .env**
```bash
# Still on server
cd /home/forge/manage.redstar.transportmarketing.net
nano .env
```

Update these lines:
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
DB_PASSWORD=YOUR_PASSWORD_FROM_STEP_1.1

QUEUE_CONNECTION=database

# Leave these as placeholders for now (will configure in Phase 2)
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
VIVA_ENVIRONMENT=demo
AADE_ENVIRONMENT=demo
MAIL_MAILER=log
```

**1.4 Generate Application Key**
```bash
php artisan key:generate
```

---

### Step 2: Copy Old CRM Database (5 min)

**2.1 Backup Original Database (Safety First)**
```bash
# On local machine
cp ~/work/redstar/database/database.sqlite \
   ~/work/redstar/database/database.sqlite.backup

# Verify backup
ls -lh ~/work/redstar/database/database.sqlite*
```

**2.2 Copy to Production Server**
```bash
# From local machine
scp ~/work/redstar/database/database.sqlite \
    forge@64.227.66.233:/tmp/old_crm.sqlite

# Verify file size matches
ssh forge@64.227.66.233 "ls -lh /tmp/old_crm.sqlite"
# Expected: ~272K (272 KB)
```

**2.3 Configure Migration Path**
```bash
# On server
cd /home/forge/manage.redstar.transportmarketing.net
nano .env
```

Add this line:
```env
OLD_CRM_DB_PATH=/tmp/old_crm.sqlite
```

---

### Step 3: Deploy Code (15 min)

**Option A: Via Git (Recommended)**
```bash
# In Forge dashboard
# Site → Deployments → Deploy Now

# OR via SSH
ssh forge@64.227.66.233
cd /home/forge/manage.redstar.transportmarketing.net
git pull origin main
```

**Option B: Manual Upload (if not using Git)**
```bash
# Already done in Prerequisites Option B
```

**3.1 Install Dependencies**
```bash
# On server
cd /home/forge/manage.redstar.transportmarketing.net

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Install NPM dependencies
npm ci

# Build assets
npm run build

# Link storage
php artisan storage:link
```

**3.2 Set Permissions**
```bash
# On server
cd /home/forge/manage.redstar.transportmarketing.net

# Ensure storage is writable
chmod -R 775 storage bootstrap/cache
chown -R forge:forge storage bootstrap/cache
```

---

### Step 4: Run Migrations (10 min)

**4.1 Fresh Migration (FIRST TIME ONLY)**
```bash
# On server
cd /home/forge/manage.redstar.transportmarketing.net

# Run all migrations
php artisan migrate --force
```

Expected output:
```
Running migrations.
2026_11_01_000001_create_entities_table ...................... 5ms DONE
2026_11_01_000002_extend_users_table ......................... 3ms DONE
2026_11_01_000003_create_clients_table ....................... 4ms DONE
2026_11_01_000004_create_projects_table ...................... 5ms DONE
2026_11_01_000005_create_tasks_table ......................... 3ms DONE
2026_11_01_000006_create_contacts_table ...................... 3ms DONE
2026_11_02_000001_create_payments_table ...................... 6ms DONE
2026_11_03_000001_create_invoices_table ...................... 7ms DONE
2026_11_04_000001_create_expenses_table ...................... 8ms DONE
2026_11_04_000002_create_subscriptions_table ................. 4ms DONE
2026_11_04_000003_create_logging_tables ...................... 5ms DONE
```

**4.2 Seed Entities**
```bash
# Create RedStar, BlueLine, GreenPromo, AmberLane entities
php artisan db:seed --class=InitialDataSeeder --force
```

Expected output:
```
Initial data seeded successfully!
Login credentials:
Email: admin@redstarweb.net
Password: password
```

**IMPORTANT:** Change admin password immediately:
```bash
php artisan tinker
```
```php
$admin = App\Models\User::where('email', 'admin@redstarweb.net')->first();
$admin->password = Hash::make('YOUR_SECURE_PASSWORD');
$admin->save();
```

**4.3 Migrate Old CRM Data**
```bash
# This migration reads /tmp/old_crm.sqlite and imports data
php artisan migrate --path=database/migrations/2026_11_05_000001_migrate_old_crm_data.php --force
```

Expected output:
```
Connecting to old CRM database at: /tmp/old_crm.sqlite
RedStar entity ID: 1
Migrating clients...
Migrated 10 clients
Migrating projects...
Migrated 22 projects
Migrating tasks...
Migrated 45 tasks
Migrating payments...
Migrated 17 payments
Migrating contacts...
Migrated 25 contacts

=== Migration Summary ===
Clients: 10
Projects: 22
Tasks: 45
Payments: 17
Contacts: 25

Migration completed successfully!
```

**If migration fails:** Check `OLD_CRM_DB_PATH` in `.env` matches actual file location.

---

### Step 5: Verify Deployment (10 min)

**5.1 Verify Data Counts**
```bash
# On server
cd /home/forge/manage.redstar.transportmarketing.net
php artisan tinker --execute="
  echo 'Entities: ' . \App\Models\Entity::count() . PHP_EOL;
  echo 'Clients: ' . \App\Models\Client::count() . PHP_EOL;
  echo 'Projects: ' . \App\Models\Project::count() . PHP_EOL;
  echo 'Tasks: ' . \App\Models\Task::count() . PHP_EOL;
  echo 'Payments: ' . \App\Models\Payment::count() . PHP_EOL;
  echo 'Contacts: ' . \App\Models\Contact::count() . PHP_EOL;
"
```

Expected output:
```
Entities: 4
Clients: 10
Projects: 22
Tasks: 45
Payments: 17
Contacts: 25
```

**If counts are 0:** Migration didn't run. Check `OLD_CRM_DB_PATH` and re-run Step 4.3.

**5.2 Test Relationships**
```bash
php artisan tinker --execute="
  \$client = \App\Models\Client::with('projects.tasks')->first();
  echo 'Client: ' . \$client->name . PHP_EOL;
  echo 'Projects: ' . \$client->projects->count() . PHP_EOL;
  echo 'Tasks: ' . \$client->projects->sum(fn(\$p) => \$p->tasks->count()) . PHP_EOL;
"
```

Expected: Client name + project/task counts > 0

**5.3 Test Admin Login**
```bash
# From local machine or browser
curl -I https://manage.redstar.transportmarketing.net/admin/login
```

Expected:
```
HTTP/2 200
strict-transport-security: max-age=31536000; includeSubDomains
```

**5.4 Access Admin Panel**
Open in browser: https://manage.redstar.transportmarketing.net/admin

Login with:
- Email: `admin@redstarweb.net`
- Password: `password` (or new password if you changed it)

You should see:
- 4 entities in Entity Resource
- 10 clients in Client Resource
- 22 projects in Project Resource
- 17 payments in Payment Resource

---

### Step 6: Configure Queue Worker (15 min)

Email notifications and invoice generation run via queue. Set up supervisor daemon.

**6.1 Create Queue Daemon in Forge**
1. Go to Site → Daemons → New Daemon
2. Configuration:
   - **Command:** `php artisan queue:work database --sleep=3 --tries=3 --max-time=3600`
   - **Directory:** `/home/forge/manage.redstar.transportmarketing.net`
   - **User:** `forge`
   - **Processes:** `2`
   - **Startsecs:** `1`
3. Click "Create Daemon"

**6.2 Verify Queue Worker Running**
```bash
# On server
supervisorctl status
```

Expected output includes:
```
daemon-XXXXX:daemon-XXXXX_00   RUNNING   pid XXXXX, uptime 0:00:05
daemon-XXXXX:daemon-XXXXX_01   RUNNING   pid XXXXX, uptime 0:00:05
```

**6.3 Test Queue Processing**
```bash
# Create test job
php artisan tinker
```
```php
\Illuminate\Support\Facades\Mail::to('test@example.com')
    ->send(new \App\Mail\PaymentLinkMail(
        \App\Models\Payment::first()
    ));
```

Check queue table:
```bash
php artisan queue:work database --once
# Should process the job without errors
```

---

### Step 7: Configure SSL Certificate (5 min)

**7.1 Enable SSL in Forge**
1. Site → SSL → Let's Encrypt
2. Domain: `manage.redstar.transportmarketing.net`
3. Click "Obtain Certificate"

**7.2 Enable Force HTTPS**
1. After SSL is active, go to Site → SSL
2. Check "Force HTTPS"

**7.3 Verify SSL**
```bash
curl -I https://manage.redstar.transportmarketing.net/admin/login | grep -i strict
```

Expected:
```
strict-transport-security: max-age=31536000; includeSubDomains
```

---

### Step 8: Configure Email (Optional - For Production)

For now, emails log to `storage/logs/laravel.log`. To send real emails:

**8.1 Mailgun Setup**
1. Sign up for Mailgun (free tier: 5,000 emails/month)
2. Add domain: `redstarweb.net`
3. Verify DNS records (MX, TXT)
4. Get SMTP credentials

**8.2 Update .env**
```bash
# On server
nano .env
```

Add:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.eu.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@redstarweb.net
MAIL_PASSWORD=YOUR_MAILGUN_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@redstarweb.net
MAIL_FROM_NAME="RedStar Platform"
```

**8.3 Test Email Delivery**
```bash
php artisan tinker
```
```php
Mail::raw('Test email from RedStar Platform', function($message) {
    $message->to('your-email@example.com')->subject('Test Email');
});
```

Check your inbox for the test email.

---

### Step 9: Optimize for Production (10 min)

**9.1 Cache Configuration**
```bash
# On server
cd /home/forge/manage.redstar.transportmarketing.net

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

**9.2 Update Forge Deploy Script**

Forge → Site → Deployment Script (edit):
```bash
cd /home/forge/manage.redstar.transportmarketing.net
git pull origin main

composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart
php artisan storage:link

echo "Deployment completed at $(date)"
```

This ensures every `git push` triggers auto-deployment.

---

## Post-Deployment Checklist

After completing all 9 steps, verify:

- [ ] Admin panel accessible at https://manage.redstar.transportmarketing.net/admin
- [ ] Login works with admin credentials
- [ ] 4 entities visible (RedStar, BlueLine, GreenPromo, AmberLane)
- [ ] 10 clients migrated with correct data
- [ ] 22 projects with client relationships intact
- [ ] 45 tasks associated with projects
- [ ] 17 payments with metadata preserved
- [ ] 25 contacts linked to clients
- [ ] SSL certificate active (HTTPS enforced)
- [ ] Queue worker running (2 processes)
- [ ] Email configuration tested (or set to `log` for now)

---

## Next Steps (Phase 2 - AADE Integration)

Once platform is stable and data verified:

1. **Obtain AADE Sandbox Credentials**
   - Register at https://mydata-dev.azure-api.net/
   - Get User ID (VAT number) + Subscription Key

2. **Configure AADE in .env**
   ```env
   AADE_ENVIRONMENT=sandbox
   AADE_USER_ID=999999999
   AADE_SUBSCRIPTION_KEY=your_key_here
   AADE_COMPANY_VAT=999999999
   AADE_COMPANY_NAME="RedStar Digital"
   ```

3. **Test Invoice Generation**
   - Create test payment via Filament
   - Generate invoice
   - Verify AADE transmission in logs
   - Download PDF with MARK + QR code

4. **Switch to Production AADE** (after Oct 1, 2026)
   ```env
   AADE_ENVIRONMENT=production
   ```

---

## Troubleshooting

### Issue: Migration fails with "No such file"
**Solution:** Verify `OLD_CRM_DB_PATH` in `.env`:
```bash
cat .env | grep OLD_CRM
ls -lh /tmp/old_crm.sqlite
```

### Issue: Admin login shows 500 error
**Solution:** Check logs and clear caches:
```bash
tail -50 storage/logs/laravel.log
php artisan cache:clear
php artisan config:clear
```

### Issue: Queue jobs not processing
**Solution:** Verify daemon running:
```bash
supervisorctl status | grep daemon
supervisorctl restart all
```

### Issue: Relationships return 0 results
**Solution:** Check foreign keys:
```bash
php artisan tinker --execute="
  DB::table('projects')->select('id', 'client_id')->take(5)->get();
"
```

If `client_id` is NULL, migration didn't preserve relationships. Re-run Step 4.3.

---

## Support & Maintenance

**Logs:**
- Application: `storage/logs/laravel.log`
- AADE API: `storage/logs/aade/aade.log`
- Queue: `storage/logs/worker.log`

**Database Backup:**
```bash
# On server (run daily via cron)
mysqldump -u redstar_user -p redstar_platform > /home/forge/backups/redstar_$(date +%Y%m%d).sql
```

**Update Application:**
```bash
# Push to Git
git push origin main

# Auto-deploys via Forge
# Or manually: Site → Deployments → Deploy Now
```

**Monitor Queue:**
```bash
# View failed jobs
php artisan queue:failed

# Retry specific job
php artisan queue:retry {job-id}

# Retry all failed jobs
php artisan queue:retry all
```

---

**Deployment completed successfully!** 🎉

Access your platform: https://manage.redstar.transportmarketing.net/admin
