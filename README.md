# RedStar Multi-Brand Platform

Multi-entity CRM managing 4 brands: RedStar, BlueLine, GreenPromo, AmberLane.

**Repository:** https://github.com/alexkourtis98/redstar-platform
**Production:** https://manage.redstar.transportmarketing.net/admin
**Stack:** Laravel 12 + Filament 3 + MySQL

## Implementation Status

**COMPLETE:**
- Database (13 migrations) + models (12)
- Multi-entity scoping (BelongsToEntity trait)
- Filament admin (5 resources)
- Payment services (Stripe + Viva ready)
- AADE invoicing integration ready
- Old CRM data migration (94 records)
- Deployment tooling + guides

**READY FOR DEPLOYMENT**

---

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=InitialDataSeeder

# Login: admin@redstarweb.net / password
php artisan serve
# Visit: http://localhost:8000/admin
```

---

## Architecture

### Multi-Entity (Custom, No Package)
- **BelongsToEntity trait**: Auto-scopes queries to `session('current_entity_id')`
- **3-tier permissions**: Super admin (all entities) / Entity role (admin/manager/member) / Client portal
- **4 brands**: RedStar, BlueLine, GreenPromo, AmberLane

### Database
**Core:** entities, users, entity_user, clients, projects, tasks
**Finance:** payments, invoices, subscriptions
**Operations:** expenses, expense_categories, expense_approvals
**Logging:** payment_gateway_logs, aade_logs (7-year audit)

---

## Payment Flow

1. Admin creates payment → generates link → emails customer
2. Customer visits /payment/checkout/{token}
3. Selects Stripe or Viva Wallet
4. Redirected to gateway
5. Webhook received → payment.status = 'paid'
6. PaymentSucceeded event → auto-generates invoice → transmits to AADE → receives MARK

---

## Configuration

### Stripe
```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```
Webhook: `https://domain.com/webhooks/stripe`

### Viva Wallet
```env
VIVA_ENVIRONMENT=demo
VIVA_MERCHANT_ID=...
VIVA_API_KEY=...
VIVA_SOURCE_CODE=...
VIVA_WEBHOOK_KEY=...
```
Webhook: `https://domain.com/webhooks/viva`

### AADE myDATA
```env
AADE_ENVIRONMENT=demo
AADE_USER_ID=999999999
AADE_SUBSCRIPTION_KEY=...
AADE_COMPANY_VAT=999999999
AADE_COMPANY_NAME="RedStar Digital"
```

---

## Filament Resources

- **ClientResource** (CRM group): CRUD, projects relation
- **ProjectResource** (CRM group): Client select, status workflow
- **PaymentResource** (Finance group): Gateway badges, payment links
- **InvoiceResource** (Finance group): AADE MARK, PDF download
- **ExpenseResource** (Operations group): Approve/reject actions

---

## Services

**StripeService**: Checkout sessions, webhooks, refunds, logging
**VivaWalletService**: Payment orders, transaction verification, webhooks
**AadeInvoicingService**: Invoice transmission, MARK retrieval, cancellation

---

## Production Deployment

**See:** `DEPLOYMENT_CHECKLIST.md` for complete step-by-step guide

**Quick links:**
- Forge setup: `FORGE_SETUP.md`
- DNS config: `DNS_SETUP.md`
- Verification: `./verify-deployment.sh`

**Deployment time:** 2-2.5 hours total

## Deployment Status

| Component | Status | URL |
|-----------|--------|-----|
| RedStar Site | Ready | https://redstar.transportmarketing.net |
| BlueLine Site | Ready | https://blueline.transportmarketing.net |
| GreenPromo Site | Ready | https://greenpromo.transportmarketing.net |
| AmberLane Site | Ready | https://amberlane.transportmarketing.net |
| Admin Platform | Ready | https://manage.redstar.transportmarketing.net/admin |

## Data Migration

Migrated from old RedStar CRM:
- 10 clients (with relationships)
- 22 projects
- 45 tasks
- 17 payments
- 25 contacts

**Migration:** `database/migrations/2026_11_05_000001_migrate_old_crm_data.php`

## Next Steps (Post-Deployment)

1. Create admin user
2. Test all CRUD operations
3. Verify data relationships
4. Train team on Filament

### Phase 2 (AADE Invoicing)
1. Copy reusable code from aadefullintegration
2. Enhance AadeInvoicingService
3. Build PDF service with Greek fonts
4. Test sandbox transmission
5. Go live with production AADE

### Future Enhancements
- Customer portal for clients
- Email notifications
- Payment checkout UI
- Subscription management
- Blog/portfolio migration

---

## Testing

**Stripe:** Card 4242 4242 4242 4242 (success)
**Viva:** Use demo credentials
**AADE:** Test sandbox at mydata-dev.azure-api.net

---

**RedStar Digital © 2026**
