# Deployment Checklist - Multi-Brand RedStar Platform

## Pre-Deployment (Already Complete ✅)

- [x] GitHub repo created: https://github.com/alexkourtis98/redstar-platform
- [x] Code pushed (4 commits, 170+ files)
- [x] Static sites monorepo ready: transportmarketing-net/website
- [x] Old CRM backup: ~/work/redstar/database/database.sqlite
- [x] Deployment guides created (FORGE_SETUP.md, DNS_SETUP.md)
- [x] Verification script ready (verify-deployment.sh)

---

## Phase 2: Deploy Static Sites (30-45 min)

### 2.1 Create Forge Sites (15 min)

In Forge Dashboard, create 4 sites:

- [ ] **RedStar** - redstar.transportmarketing.net
  - Git: `transportmarketing-net/website`
  - Branch: `main`
  - Web Dir: `/redstar`
  - Quick Deploy: ✓

- [ ] **BlueLine** - blueline.transportmarketing.net
  - Git: `transportmarketing-net/website`
  - Branch: `main`
  - Web Dir: `/blueline/blueline.transportmarketing.net`
  - Quick Deploy: ✓

- [ ] **GreenPromo** - greenpromo.transportmarketing.net
  - Git: `transportmarketing-net/website`
  - Branch: `main`
  - Web Dir: `/greenpromo/greenpromo.transportmarketing.net`
  - Quick Deploy: ✓

- [ ] **AmberLane** - amberlane.transportmarketing.net
  - Git: `transportmarketing-net/website`
  - Branch: `main`
  - Web Dir: `/amberlane`
  - Quick Deploy: ✓

### 2.2 Deploy All Sites (5 min)

- [ ] Click "Deploy Now" for RedStar
- [ ] Click "Deploy Now" for BlueLine
- [ ] Click "Deploy Now" for GreenPromo
- [ ] Click "Deploy Now" for AmberLane
- [ ] Wait for all deployments to complete

### 2.3 Enable SSL (10 min)

For each site:
- [ ] RedStar → SSL → Let's Encrypt → Obtain Certificate → Force HTTPS
- [ ] BlueLine → SSL → Let's Encrypt → Obtain Certificate → Force HTTPS
- [ ] GreenPromo → SSL → Let's Encrypt → Obtain Certificate → Force HTTPS
- [ ] AmberLane → SSL → Let's Encrypt → Obtain Certificate → Force HTTPS

### 2.4 Verify Static Sites (5 min)

```bash
curl -I https://redstar.transportmarketing.net
curl -I https://blueline.transportmarketing.net
curl -I https://greenpromo.transportmarketing.net
curl -I https://amberlane.transportmarketing.net
```

Expected: All return `HTTP/2 200` + `strict-transport-security` header

---

## Phase 3: Deploy Laravel Platform (45-60 min)

### 3.1 Create Forge Site (5 min)

In Forge Dashboard:
- [ ] New Site → `manage.redstar.transportmarketing.net`
- [ ] Git: `alexkourtis98/redstar-platform`
- [ ] Branch: `main`
- [ ] Web Dir: `/public`
- [ ] PHP: 8.2
- [ ] Quick Deploy: ✓

### 3.2 Create Production Database (10 min)

```bash
ssh forge@64.227.66.233

# Generate password
DB_PASSWORD=$(openssl rand -base64 24 | tr -d "=+/" | cut -c1-24)
echo "Save this: $DB_PASSWORD"

# Create DB
mysql -u forge -p
```

```sql
CREATE DATABASE redstar_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'redstar_user'@'localhost' IDENTIFIED BY '<PASTE_PASSWORD>';
GRANT ALL PRIVILEGES ON redstar_platform.* TO 'redstar_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

- [ ] Database created
- [ ] Password saved securely

### 3.3 Configure Environment (10 min)

Forge → Site → Environment, update:

- [ ] `APP_URL=https://manage.redstar.transportmarketing.net`
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `DB_DATABASE=redstar_platform`
- [ ] `DB_USERNAME=redstar_user`
- [ ] `DB_PASSWORD=<PASTE_PASSWORD>`
- [ ] `QUEUE_CONNECTION=database`
- [ ] `OLD_CRM_DB_PATH=/tmp/old_crm.sqlite`
- [ ] Click "Save"

### 3.4 Deploy Code (5 min)

- [ ] Forge → Site → Deploy Now
- [ ] Wait for deployment to complete

### 3.5 Run Migrations (5 min)

```bash
ssh forge@64.227.66.233
cd /home/forge/manage.redstar.transportmarketing.net

php artisan migrate --force
php artisan db:seed --class=InitialDataSeeder --force
php artisan tinker --execute="echo \App\Models\Entity::count();"
```

- [ ] Migrations executed
- [ ] 4 entities seeded

### 3.6 Migrate Old CRM Data (15 min)

```bash
# On local machine
scp ~/work/redstar/database/database.sqlite \
    forge@64.227.66.233:/tmp/old_crm.sqlite

# On server
ssh forge@64.227.66.233
cd /home/forge/manage.redstar.transportmarketing.net
php artisan migrate --path=database/migrations/2026_11_05_000001_migrate_old_crm_data.php --force
```

Verify counts:

```bash
php artisan tinker --execute="
  echo 'Entities: ' . \App\Models\Entity::count() . PHP_EOL;
  echo 'Clients: ' . \App\Models\Client::count() . PHP_EOL;
  echo 'Projects: ' . \App\Models\Project::count() . PHP_EOL;
  echo 'Tasks: ' . \App\Models\Task::count() . PHP_EOL;
  echo 'Payments: ' . \App\Models\Payment::count() . PHP_EOL;
  echo 'Contacts: ' . \App\Models\Contact::count() . PHP_EOL;
"
```

Expected:
- [ ] Entities: 4
- [ ] Clients: 10
- [ ] Projects: 22
- [ ] Tasks: 45
- [ ] Payments: 17
- [ ] Contacts: 25

### 3.7 Configure Queue Worker (5 min)

Forge → Site → Daemons → New Daemon:

- [ ] Command: `php artisan queue:work database --sleep=3 --tries=3 --max-time=3600`
- [ ] Directory: `/home/forge/manage.redstar.transportmarketing.net`
- [ ] User: `forge`
- [ ] Processes: 2
- [ ] Save

### 3.8 Enable SSL & Optimize (10 min)

Forge → Site → SSL:
- [ ] Let's Encrypt → Obtain Certificate
- [ ] Force HTTPS

On server:
```bash
ssh forge@64.227.66.233
cd /home/forge/manage.redstar.transportmarketing.net
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

- [ ] SSL enabled
- [ ] Caches optimized

---

## Phase 4: DNS Configuration (15 min)

In DNS provider (Cloudflare, Namecheap, etc.):

- [ ] Add A record: `redstar` → 64.227.66.233
- [ ] Add A record: `blueline` → 64.227.66.233
- [ ] Add A record: `greenpromo` → 64.227.66.233
- [ ] Add A record: `amberlane` → 64.227.66.233
- [ ] Add A record: `manage.redstar` → 64.227.66.233
- [ ] Set Cloudflare to "DNS only" (grey cloud)
- [ ] Wait 5-30 min for propagation

Verify:
```bash
dig redstar.transportmarketing.net +short
dig blueline.transportmarketing.net +short
dig greenpromo.transportmarketing.net +short
dig amberlane.transportmarketing.net +short
dig manage.redstar.transportmarketing.net +short
```

- [ ] All return `64.227.66.233`

---

## Phase 5: Final Verification (15 min)

### Run Automated Tests

```bash
cd ~/work/transportmarketing.net/redstar-platform
./verify-deployment.sh
```

### Manual Checks

- [ ] Visit https://redstar.transportmarketing.net - homepage loads
- [ ] Visit https://blueline.transportmarketing.net - homepage loads
- [ ] Visit https://greenpromo.transportmarketing.net - homepage loads
- [ ] Visit https://amberlane.transportmarketing.net - homepage loads
- [ ] Visit https://manage.redstar.transportmarketing.net/admin - Filament login
- [ ] Login with: admin@redstarweb.net / password
- [ ] Dashboard shows 4 entities in switcher
- [ ] Clients table shows 10 records
- [ ] Projects table shows 22 records
- [ ] Tasks exist under projects
- [ ] Payments table shows 17 records
- [ ] Contacts exist under clients

### Data Integrity Check

```bash
ssh forge@64.227.66.233
cd /home/forge/manage.redstar.transportmarketing.net
php artisan tinker --execute="
  \$client = \App\Models\Client::with('projects.tasks', 'contacts')->first();
  echo 'Client: ' . \$client->name . PHP_EOL;
  echo 'Projects: ' . \$client->projects->count() . PHP_EOL;
  echo 'Tasks: ' . \$client->projects->sum(fn(\$p) => \$p->tasks->count()) . PHP_EOL;
  echo 'Contacts: ' . \$client->contacts->count() . PHP_EOL;
"
```

- [ ] Relationships intact
- [ ] No errors

### Queue Worker Check

```bash
ssh forge@64.227.66.233
supervisorctl status | grep daemon
```

- [ ] 2 processes RUNNING

---

## Post-Deployment (Day 1)

- [ ] Create admin user (if default not working)
- [ ] Test creating new client
- [ ] Test creating new project
- [ ] Test creating new task
- [ ] Verify email delivery (password resets)
- [ ] Train team on Filament admin panel

---

## Known Issues & Solutions

**Problem:** SSL certificate fails to issue
- **Solution:** Wait 10 min for DNS propagation, disable Cloudflare proxy, retry

**Problem:** Admin panel 500 error
- **Solution:** Check `storage/logs/laravel.log`, likely .env issue

**Problem:** Data migration imported 0 records
- **Solution:** Verify `/tmp/old_crm.sqlite` exists on server, check file permissions

**Problem:** Queue worker not running
- **Solution:** Check Supervisor daemon, restart via Forge

---

## Total Deployment Time

- Phase 2 (Static Sites): 30-45 min
- Phase 3 (Laravel Platform): 45-60 min
- Phase 4 (DNS): 15 min (+ propagation wait)
- Phase 5 (Verification): 15 min

**Total:** 2-2.5 hours

---

## Support

**Repository:** https://github.com/alexkourtis98/redstar-platform
**Server:** forge@64.227.66.233
**Logs:** `/home/forge/manage.redstar.transportmarketing.net/storage/logs/laravel.log`

**Common Commands:**
```bash
# Restart queue
php artisan queue:restart

# Clear caches
php artisan optimize:clear

# View failed jobs
php artisan queue:failed

# Check logs
tail -50 storage/logs/laravel.log
```
