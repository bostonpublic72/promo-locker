# Deploy guide — promo.chatselfies.com

Step-by-step cPanel deployment for the OGAds followers landing page.

**Target:** `https://promo.chatselfies.com`  
**Document root:** `/public_html/promo.chatselfies.com`  
**PHP:** 8.2 (SSL already live)

---

## 1. Build CSS locally

Before every upload, compile Tailwind on your machine:

```bash
npm install
npm run tailwind-minify
```

Confirm `assets/css/output.css` was updated.

---

## 2. Create MySQL database (cPanel)

1. Log in to cPanel → **MySQL® Databases**.
2. Create a database (e.g. `cpaneluser_promo`).
3. Create a MySQL user with a strong password.
4. Add the user to the database with **ALL PRIVILEGES**.
5. Note: `DB_SERVER_NAME` is usually `localhost`.

The app auto-creates `sessions` and `clicks` tables on first request.

---

## 3. Upload files

Upload everything **except**:

- `.git/`
- `node_modules/`
- `.env` (create on server — see step 4)
- `assets/css/input.css` (optional; not served)
- `tailwind.config.js`, `package.json`, `package-lock.json` (optional; build locally only)

**Include:**

```
/public_html/promo.chatselfies.com/
├── .htaccess
├── bootstrap.php
├── index.php
├── api/
├── assets/
│   ├── css/output.css    ← required (built)
│   ├── js/
│   └── imgs/
├── database/
├── pages/
├── services/
└── utils/
```

Use File Manager or FTP/SFTP. Preserve directory structure.

---

## 4. Create `.env` on the server

In cPanel **File Manager**, create `/public_html/promo.chatselfies.com/.env` (copy from `.env.production.example`).

Fill in real values — **never commit this file to git**.

| Variable | Where to get it |
|----------|-----------------|
| `OFFERS_API_KEY` | OGAds dashboard |
| `SMM_GLOBE_API_KEY` | SMMGlobe account |
| `INSTAGRAM_FOLLOWERS_SERVICE_ID` | Verify on [smmglobe.com/services](https://smmglobe.com/services) |
| `TIKTOK_FOLLOWERS_SERVICE_ID` | Verify on [smmglobe.com/services](https://smmglobe.com/services) |
| `POSTBACK_ALLOWED_IPS` | OGAds postback IP documentation |
| `DB_*` | cPanel MySQL step above |

Set file permissions to **640** or **600** if your host allows.

---

## 5. Configure OGAds postback URL

In the OGAds dashboard, set the postback URL to:

```
https://promo.chatselfies.com/api/postback.php?offer_id={offer_id}&aff_sub4={aff_sub4}&ip={session_ip}
```

Use OGAds’ exact macro names if they differ (`{offer_id}`, `{aff_sub4}`, `{session_ip}`).

---

## 6. Smoke test

1. Visit `https://promo.chatselfies.com` — page loads, no PHP errors.
2. Complete the form flow (username → platform → follower amount → offers).
3. Click an offer — should redirect to the advertiser.
4. After test conversion, check MySQL: `sessions.fulfilled_at` and `smm_order_id` populated once.
5. Run the duplicate-fulfillment test in `QA_CHECKLIST.md`.

---

## 7. Ongoing deploys

1. Pull/copy changed PHP/JS files.
2. Run `npm run tailwind-minify` if CSS changed.
3. Upload changed files only.
4. Do **not** overwrite server `.env` unless adding new keys.

---

## Troubleshooting

| Symptom | Check |
|---------|--------|
| Blank / 500 error | cPanel **Errors** log; PHP 8.2 selected for subdomain |
| “Missing required PHP extensions” | Enable `pdo_mysql`, `curl`, `json` in PHP selector |
| Postback rejected | `POSTBACK_ALLOWED_IPS` set and matches OGAds source IP |
| No followers ordered | SMMGlobe API key, service IDs, account balance |
| DB connection failed | `DB_*` values, user privileges, database name prefix |
