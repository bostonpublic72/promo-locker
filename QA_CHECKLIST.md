# QA checklist — pre-launch

Manual tests before pointing live traffic at `https://promo.chatselfies.com`.

---

## Environment & infrastructure

- [ ] PHP 8.0+ on subdomain (target 8.2)
- [ ] Extensions loaded: `pdo_mysql`, `curl`, `json`
- [ ] `.env` exists on server only (not in git)
- [ ] `.htaccess` blocks web access to `.env`
- [ ] `assets/css/output.css` is present and recent (ran `npm run tailwind-minify`)
- [ ] MySQL credentials correct; tables auto-create on first visit
- [ ] SSL active; site loads over HTTPS

## Configuration verification

- [ ] `OFFERS_API_KEY` — offers load on step 3 (no “Failed to fetch offers”)
- [ ] `INSTAGRAM_FOLLOWERS_SERVICE_ID` — verified on [smmglobe.com/services](https://smmglobe.com/services)
- [ ] `TIKTOK_FOLLOWERS_SERVICE_ID` — verified on [smmglobe.com/services](https://smmglobe.com/services)
- [ ] `POSTBACK_ALLOWED_IPS` — populated (non-empty); matches OGAds docs
- [ ] OGAds postback URL configured:
  ```
  https://promo.chatselfies.com/api/postback.php?offer_id={offer_id}&aff_sub4={aff_sub4}&ip={session_ip}
  ```
- [ ] SMMGlobe account has sufficient balance for test + launch volume

## Frontend flow

- [ ] Landing page loads; app name/version display
- [ ] **Loading spinner** shows during session/offers fetch (Alpine `x-show` fix)
- [ ] Username validation — empty username blocked
- [ ] Platform selection — Instagram and TikTok both work
- [ ] Follower amounts — only 250, 500, 1000 selectable
- [ ] Offers list renders with images and copy
- [ ] Clicking offer opens new tab to advertiser (not error page)
- [ ] Status polling eventually redirects to `/pages/completed.php`
- [ ] Completed page displays; session cookie cleared

## Security

- [ ] `aff_sub4` cookie: HttpOnly; Secure on HTTPS
- [ ] `api/click.php?link=https://evil.com` — rejected (400)
- [ ] `api/click.php?offer_id=abc` — rejected (400)
- [ ] Postback from non-allowlisted IP — rejected JSON error
- [ ] Postback with empty `POSTBACK_ALLOWED_IPS` in `.env` — rejected (fail closed)
- [ ] Direct request to `/.env` — 403/denied

## API / session validation

- [ ] `api/session.php?followers=999&…` — returns JSON error (invalid amount)
- [ ] `api/session.php` without `aff_sub4` cookie — returns error

---

## Duplicate-fulfillment regression test (CRITICAL)

This verifies the fix for placing multiple SMMGlobe orders per session.

**Setup:** Use 1,000 followers (requires 3 offers). Have access to MySQL and SMMGlobe order history.

1. Start a fresh browser session (incognito) on the landing page.
2. Enter a test username, pick a platform, select **1,000 followers**.
3. Complete **3 distinct offers** (real or test conversions per OGAds).
4. After the 3rd postback, confirm in MySQL:
   ```sql
   SELECT id, username, followers, fulfilled_at, smm_order_id
   FROM sessions
   ORDER BY id DESC LIMIT 1;
   ```
   - [ ] `fulfilled_at` is set (not NULL)
   - [ ] `smm_order_id` is set (not NULL)
5. Confirm **exactly one** new order in SMMGlobe for this username/quantity.
6. Complete a **4th offer** for the same session (same browser/cookie).
7. Re-check MySQL — same row:
   - [ ] `fulfilled_at` unchanged
   - [ ] `smm_order_id` unchanged
8. Confirm SMMGlobe:
   - [ ] **No second order** was created for this session

**Pass criteria:** One SMMGlobe order total per session, regardless of extra postbacks.

---

## Duplicate offer_id test

1. Complete the same offer twice (if OGAds sends duplicate postbacks for one offer).
2. Check distinct completed count:
   ```sql
   SELECT COUNT(DISTINCT offer_id) FROM clicks
   WHERE session_id = ? AND completed = 1;
   ```
   - [ ] Same offer_id counted once toward threshold

---

## Post-launch monitoring (first 24h)

- [ ] Spot-check 3–5 real sessions in `sessions` table for single fulfillment
- [ ] Monitor SMMGlobe spend vs. completed sessions
- [ ] Watch cPanel error log for PHP exceptions
- [ ] Confirm OGAds postbacks returning `{"success":true}` at threshold
