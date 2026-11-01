# RedStar Platform - Quick Start Guide

Get the platform running in 5 minutes.

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0
- Git

## Installation

```bash
# 1. Clone & Install
cd /home/alexandroskourtis/work/transportmarketing.net/redstar-platform
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# Update .env with your database:
# DB_DATABASE=redstar_platform
# DB_USERNAME=root
# DB_PASSWORD=your_password

# 3. Database
mysql -u root -p -e "CREATE DATABASE redstar_platform;"
php artisan migrate

# 4. Seed Entities (RedStar, BlueLine, GreenPromo, AmberLane)
php artisan db:seed ProductionEntitySeeder

# 5. Build Assets
npm run build:tokens  # Compiles DTCG tokens → CSS
npm run build         # Vite build

# 6. Storage Link
php artisan storage:link

# 7. Start
php artisan serve      # http://localhost:8000
php artisan queue:work # In another terminal
```

## Access

**Admin Panel:** http://localhost:8000/admin
- Email: admin@redstar.test
- Password: (check `ProductionEntitySeeder` or create manually)

**Create Admin User:**
```bash
php artisan tinker
> $user = \App\Models\User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password'), 'super_admin' => true]);
```

## Test Payment Flow

1. **Create Client** (Filament Admin → Clients)
2. **Create Project** (Filament Admin → Projects)
3. **Create Payment** (Filament Admin → Payments)
   - Set amount, gateway, expiry date
   - Generate payment link
4. **Visit Checkout** http://localhost:8000/payment/checkout/{token}
5. **Select Gateway** (Stripe or Viva)
6. **Use Test Card:**
   - Stripe: 4242 4242 4242 4242
   - Expiry: 12/34, CVC: 123

## Queue Worker (Production)

```bash
# Systemd service
sudo nano /etc/systemd/system/redstar-queue.service
```

```ini
[Unit]
Description=RedStar Queue Worker
After=network.target

[Service]
User=forge
Group=forge
Restart=always
WorkingDirectory=/home/forge/redstar.transportmarketing.net
ExecStart=/usr/bin/php artisan queue:work database --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable redstar-queue
sudo systemctl start redstar-queue
```

## Testing Webhooks Locally

**Stripe CLI:**
```bash
stripe listen --forward-to localhost:8000/webhooks/stripe
stripe trigger checkout.session.completed
```

**Viva Webhook:**
Use Postman or Webhook Relay for local testing.

## Environment Variables (Required for Production)

```env
# Stripe
STRIPE_REDSTAR_PUBLIC=pk_live_...
STRIPE_REDSTAR_SECRET=sk_live_...
STRIPE_REDSTAR_WEBHOOK_SECRET=whsec_...

# Viva Wallet
VIVA_REDSTAR_CLIENT_ID=...
VIVA_REDSTAR_CLIENT_SECRET=...
VIVA_REDSTAR_API_KEY=...
VIVA_REDSTAR_SOURCE_CODE=...
VIVA_REDSTAR_MERCHANT_ID=...

# AADE myDATA
AADE_ENVIRONMENT=sandbox
AADE_REDSTAR_USERNAME=123456789  # VAT number
AADE_REDSTAR_SUBSCRIPTION_KEY=...

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_USERNAME=postmaster@mg.redstarweb.net
MAIL_PASSWORD=...
```

## Troubleshooting

**Token compilation fails:**
```bash
npm run build:tokens
# Check: /redstar/tokens/*.json exist
```

**Queue not processing:**
```bash
php artisan queue:restart
php artisan queue:work database --tries=3
```

**Emails not sending:**
```bash
# Test
php artisan tinker
> Mail::raw('Test', fn($m) => $m->to('you@example.com')->subject('Test'));

# Check queue
php artisan queue:failed
```

**Invoice PDF not generating:**
```bash
# Check storage permissions
chmod -R 775 storage
chown -R www-data:www-data storage
```

## Production Deployment

See `IMPLEMENTATION_STATUS.md` for full deployment guide.

**Quick Deploy (Forge):**
1. Create site on Forge
2. Connect GitHub repo
3. Set environment variables
4. Enable queue daemon
5. Configure webhooks (Stripe + Viva)
6. Run migrations: `php artisan migrate --force`
7. Deploy

---

**Need Help?** Check `IMPLEMENTATION_STATUS.md` for detailed documentation.
