# RedStar Multi-Brand Business Management Platform

Laravel 12 business management system for 4 brands with CRM, dual payment gateways (Stripe + Viva), AADE invoicing, expense tracking.

## Implementation Status

**COMPLETE:** Database (10 migrations), Models (12), Multi-entity scoping, Filament admin (5 resources), Payment services (Stripe + Viva), AADE invoicing, Expense approval

**REMAINING:** Frontend templates, Email notifications, Production deployment

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

## Next Steps

1. Migrate static templates → Blade
2. Add email notifications
3. Create invoice PDF templates
4. Migrate portfolio/blog to DB
5. Production Stripe/Viva/AADE credentials
6. Deploy + monitoring

---

## Testing

**Stripe:** Card 4242 4242 4242 4242 (success)
**Viva:** Use demo credentials
**AADE:** Test sandbox at mydata-dev.azure-api.net

---

**RedStar Digital © 2026**
