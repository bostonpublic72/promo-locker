# Orientation

Overview of the OGAds followers landing page: request flow, database schema, environment variables, and external APIs.

## Request flow

```
User browser
    │
    ▼
index.php                    Alpine.js UI, sets aff_sub4 cookie
    │
    ├── api/session.php      Create/update session (username, platform, followers)
    │
    ├── api/offers.php       Fetch OGAds offers for this visitor
    │
    ├── api/click.php        Record click, redirect to offer URL
    │       │
    │       └── User completes offer on advertiser site
    │
    ├── OGAds postback ──► api/postback.php
    │       │              Mark click complete; if threshold met → SMMGlobe order (once)
    │       │
    └── api/status.php       Poll until enough offers complete → pages/completed.php
```

### Conversion thresholds

| Followers requested | Offers required |
|---------------------|-----------------|
| 250                 | 1               |
| 500                 | 2               |
| 1,000               | 3               |

Logic lives in `services/ConversionService.php`.

### Fulfillment (idempotent)

When the required number of **distinct** completed offers is reached, `api/postback.php` places **one** SMMGlobe order per session. The session row tracks:

- `fulfilled_at` — when the order was placed
- `smm_order_id` — SMMGlobe order ID

Concurrent postbacks at the threshold use a DB transaction + `SELECT … FOR UPDATE` on the session row so only one order is created.

## Database schema

Tables are created automatically on first use (`database/Session.php`, `database/Click.php`). Existing deployments get new columns via migration on boot.

### `sessions`

| Column        | Type           | Notes                          |
|---------------|----------------|--------------------------------|
| id            | INT PK         | Auto-increment                 |
| aff_sub4      | VARCHAR(255)   | Cookie / OGAds sub ID          |
| username      | VARCHAR(255)   | Target social handle           |
| platform      | VARCHAR(255)   | `instagram` or `tiktok`        |
| followers     | INT            | 250, 500, or 1000              |
| ip_address    | VARCHAR(255)   | Visitor IP (used in postback)  |
| fulfilled_at  | DATETIME NULL  | Set when SMMGlobe order placed |
| smm_order_id  | VARCHAR(255)   | SMMGlobe order reference       |
| created_at    | DATETIME       | Default CURRENT_TIMESTAMP      |

### `clicks`

| Column       | Type         | Notes                    |
|--------------|--------------|--------------------------|
| id           | INT PK       | Auto-increment           |
| offer_id     | INT          | OGAds offer ID           |
| session_id   | INT FK       | → sessions.id            |
| completed    | BOOL         | Default false            |
| completed_at | DATETIME     | Set by postback          |
| created_at   | DATETIME     | When user clicked offer  |

The same `offer_id` is only counted once per session toward the conversion threshold.

## Environment variables

See `.env.example` and `.env.production.example`. Summary:

| Variable | Purpose |
|----------|---------|
| `APP_NAME` | Site title |
| `APP_VERSION` | Version shown in UI |
| `OFFERS_API_KEY` | OGAds locker API bearer token |
| `OFFERS_ENDPOINT` | OGAds offers API base URL |
| `OFFERS_LIMIT` | Max offers shown (0 = no limit) |
| `OFFERS_CTYPE` | Content type for mobile/tablet |
| `SMM_GLOBE_API_KEY` | SMMGlobe API key |
| `INSTAGRAM_FOLLOWERS_SERVICE_ID` | SMMGlobe service ID for Instagram |
| `TIKTOK_FOLLOWERS_SERVICE_ID` | SMMGlobe service ID for TikTok |
| `POSTBACK_ALLOWED_IPS` | Comma-separated OGAds postback IPs (required; empty = reject all) |
| `DB_*` | MySQL connection |

## External APIs

### OGAds (Offer API)

- **Endpoint:** `https://appsave.online/api/v2` (OGAds rotates domains; older endpoints still work)
- **Auth:** `Authorization: Bearer {OFFERS_API_KEY}` — generate the key in the OGAds dashboard
- **Offers:** `GET {OFFERS_ENDPOINT}?ip=…&user_agent=…&aff_sub4=…&ctype=…` (see below)
- **Required params:** `ip`, `user_agent`
- **Optional params:** `ctype` (mobile/tablet only; bitwise — 1=CPI, 2=CPA, 4=PIN, 8=VID), `max`, `min`, `aff_sub4`, `aff_sub5`
- This app sends `aff_sub4` from the visitor cookie and `ctype` from `OFFERS_CTYPE` on mobile/tablet only
- **Postback (configure in OGAds dashboard):**
  ```
  https://promo.chatselfies.com/api/postback.php?offer_id={offer_id}&aff_sub4={aff_sub4}&ip={session_ip}
  ```
- Postbacks must originate from an IP in `POSTBACK_ALLOWED_IPS`.

### SMMGlobe (follower delivery)

- **Add order:** `GET https://smmglobe.com/api/v2?key=…&action=add&service=…&link=…&quantity=…`
- Service IDs must match live services on [smmglobe.com/services](https://smmglobe.com/services).
- Profile links are built from username: Instagram `https://www.instagram.com/{username}/`, TikTok `https://www.tiktok.com/@{username}/`.

## Key files

| File | Role |
|------|------|
| `index.php` | Frontend shell + aff_sub4 cookie |
| `assets/js/main.js` | Alpine.js store, polling |
| `api/session.php` | Session CRUD |
| `api/offers.php` | Proxy to OGAds offers |
| `api/click.php` | Click tracking + redirect |
| `api/postback.php` | OGAds conversion + SMMGlobe fulfillment |
| `api/status.php` | Completion polling |
| `bootstrap.php` | PHP 8+ and extension checks |
| `services/SMMGlobeService.php` | SMMGlobe client |
| `services/ConversionService.php` | Followers → offer count |
| `database/Session.php` | Session schema + fulfillment |
| `database/Click.php` | Click schema + queries |

## Build

CSS is compiled from Tailwind source:

```bash
npm install
npm run tailwind-minify
```

The site loads `assets/css/output.css` (not `input.css`).
