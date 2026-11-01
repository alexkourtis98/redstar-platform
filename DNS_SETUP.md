# DNS Configuration Guide

## Required DNS Records

Add these A records in your DNS provider (Cloudflare, Namecheap, etc.):

```
Type | Name       | Value            | TTL  | Proxy Status (Cloudflare)
-----|------------|------------------|------|---------------------------
A    | redstar    | 64.227.66.233    | Auto | DNS only (grey cloud)
A    | blueline   | 64.227.66.233    | Auto | DNS only (grey cloud)
A    | greenpromo | 64.227.66.233    | Auto | DNS only (grey cloud)
A    | amberlane  | 64.227.66.233    | Auto | DNS only (grey cloud)
A    | manage.redstar | 64.227.66.233 | Auto | DNS only (grey cloud)
```

**Important Notes:**

1. **Cloudflare Users:** Set to "DNS only" (grey cloud) not "Proxied" (orange cloud) for SSL certificate generation to work
2. **Propagation Time:** DNS changes take 5-60 minutes to propagate globally
3. **SSL Certificates:** Can only be issued AFTER DNS points to the server

---

## Verification Commands

After adding DNS records, verify they resolve correctly:

```bash
# Check DNS resolution
dig redstar.transportmarketing.net +short
dig blueline.transportmarketing.net +short
dig greenpromo.transportmarketing.net +short
dig amberlane.transportmarketing.net +short
dig manage.redstar.transportmarketing.net +short
```

All should return: `64.227.66.233`

---

## SSL Certificate Setup (After DNS)

Once DNS is verified:

1. In Forge Dashboard → Site → SSL
2. Click "Let's Encrypt"
3. Click "Obtain Certificate"
4. Wait for certificate to be issued
5. Enable "Force HTTPS"

Repeat for all 5 sites.

---

## Troubleshooting

**"DNS not found" error:**
- DNS not propagated yet, wait 10-30 minutes
- Check DNS records are correct (no typos)

**"Certificate failed to issue":**
- Verify DNS resolves to correct IP
- Check Cloudflare proxy is disabled (grey cloud)
- Wait 5 minutes and retry

**"Site not accessible after DNS change":**
- Clear browser cache
- Try incognito/private mode
- Use `curl -I https://site.example.com` to test

---

## Final URLs

After DNS + SSL setup:

- https://redstar.transportmarketing.net (Static)
- https://blueline.transportmarketing.net (Static)
- https://greenpromo.transportmarketing.net (Static)
- https://amberlane.transportmarketing.net (Static)
- https://manage.redstar.transportmarketing.net/admin (Laravel Admin)
