# Environment & Deployment Guide — PUPBC CareLink

Ang CareLink ay may **3 hiwalay na environments** (walang shared database o shared secrets):

| Environment | Git Branch | Hosting | Database | URLs |
|---|---|---|---|---|
| **Localhost** | any local branch | XAMPP / Local PHP + Vite (o Docker) | Local MySQL (`carelink`) | Frontend: `http://localhost:5173`<br>Backend: `http://127.0.0.1:8000` |
| **Staging** | `staging` | Render Free (Docker + Static Site) | **NEW Supabase Staging DB** (Postgres) | Frontend: `https://pupbc-carelink-staging.onrender.com`<br>Backend: `https://pupbc-carelink-staging-api.onrender.com` |
| **Production** | `main` | Render Free (Docker + Static Site) | **NEW Supabase Production DB** (Postgres) | Frontend: `https://pupbc-carelink-prod.onrender.com`<br>Backend: `https://pupbc-carelink-prod-api.onrender.com` |

> Automated PHPUnit tests use a separate `testing` environment configured in [`backend/phpunit.xml`](../backend/phpunit.xml).

Lahat ng Render service configs ay nasa [`render.yaml`](../render.yaml).

---

## 1. Localhost Setup (Local MySQL)

1. Kopyahin ang templates kung wala pa:
   - `backend/.env.example` → `backend/.env`
   - `frontend/.env.example` → `frontend/.env`
2. Siguraduhing may `APP_KEY` at `JWT_SECRET` sa `backend/.env`:
   ```bash
   cd backend
   php artisan key:generate
   php artisan jwt:secret
   ```
3. Siguraduhing may MySQL database na `carelink` sa XAMPP, pagkatapos:
   ```bash
   cd backend
   php artisan migrate --seed
   php artisan serve
   ```
4. Sa hiwalay na terminal:
   ```bash
   cd frontend
   npm run dev
   ```
   Awtomatikong naka-proxy ang `/api` sa `http://127.0.0.1:8000` (tingnan ang `frontend/vite.config.js`).

---

## 2. Gumawa ng Dalawang Bagong Supabase Projects

Gumawa ng **2 magkahiwalay na Supabase project** sa [supabase.com](https://supabase.com), parehong nasa **Southeast Asia (Singapore) — `ap-southeast-1`**:

1. **Staging DB Project** — hal. `pupbc-carelink-staging`
2. **Production DB Project** — hal. `pupbc-carelink-prod`

Sa **bawat** project, pindutin ang **Connect → Session pooler** (port **5432**, HUWAG 6543) at kunin ang:
- `SUPABASE_DB_HOST` (hal. `aws-0-ap-southeast-1.pooler.supabase.com`)
- `SUPABASE_DB_USERNAME` (hal. `postgres.<project-ref>`)
- `SUPABASE_DB_PASSWORD` (malakas at **magkaiba** para sa Staging at Production)

---

## 3. Generate Unique Keys (Hiwalay para sa Staging at Production)

Patakbuhin ito **dalawang beses** (isang set para sa Staging, isang set para sa Production — huwag mag-reuse ng keys):

```bash
node -e "
const c = require('crypto');
console.log('APP_KEY=base64:' + c.randomBytes(32).toString('base64'));
console.log('JWT_SECRET=' + c.randomBytes(32).toString('hex'));
console.log('KIOSK_DEVICE_TOKEN=' + c.randomBytes(24).toString('hex'));
"
```

---

## 4. Brevo SMTP (Email)

Bina-block ng Render Free ang SMTP ports 25/465/587, kaya **Brevo sa port `2525`** ang gamit.

1. Mag-sign up sa [brevo.com](https://www.brevo.com) (Free plan, 300 emails/day).
2. **Senders, Domains & Dedicated IPs → Senders → Add a sender**: `pupbinancarelink@gmail.com` at i-verify.
3. **SMTP & API → SMTP** tab → **Generate a new SMTP key** → kopyahin ang `MAIL_USERNAME` at `MAIL_PASSWORD`.
4. **Staging safety**: naka-`EMAIL_TEST_MODE=true` ang Staging sa `render.yaml` kaya lahat ng staging emails ay mapupunta sa `EMAIL_TEST_RECIPIENT` (`delivered@resend.dev`) at hindi makaka-spam ng totoong students. Sa Production, `EMAIL_TEST_MODE=false`.

---

## 5. Git Branch Workflow (`staging` → `main`)

Gumawa at i-push ang `staging` branch sa GitHub repo mo:

```bash
git checkout -b staging
git push -u origin staging
git checkout main
```

**Daily workflow:**
1. Mag-develop at mag-test muna sa **Localhost**.
2. I-push / i-merge sa `staging` branch → automatic magde-deploy sa **Render Staging** (`pupbc-carelink-staging*`).
3. I-test sa Staging. Kapag pasado na, i-merge ang `staging` sa `main` → automatic magde-deploy sa **Render Production** (`pupbc-carelink-prod*`).

---

## 6. Deploy sa Render Blueprint

1. Pumunta sa [dashboard.render.com](https://dashboard.render.com) → **New → Blueprint** → piliin ang GitHub repo mo.
2. Babasahin ng Render ang [`render.yaml`](../render.yaml) at gagawa ng **4 na bagong services**:
   - `pupbc-carelink-staging-api` (`branch: staging`)
   - `pupbc-carelink-staging` (`branch: staging`)
   - `pupbc-carelink-prod-api` (`branch: main`)
   - `pupbc-carelink-prod` (`branch: main`)
3. Ilagay ang mga `sync: false` secrets para sa **Staging** at **Production** (tingnan ang `backend/.env.staging.example` at `backend/.env.production.example`):
   - `APP_KEY`, `JWT_SECRET`, `KIOSK_DEVICE_TOKEN`, `SEED_NURSE_PASSWORD`
   - `SUPABASE_DB_HOST`, `SUPABASE_DB_USERNAME`, `SUPABASE_DB_PASSWORD`
   - `MAIL_USERNAME`, `MAIL_PASSWORD`
   - `OLLAMA_API_KEY`, `GEMINI_API_KEY` (optional, para sa AI chatbot)
4. Pindutin ang **Apply**.
5. *(Kung taken na ang service name sa Render)*: i-update ang URLs sa `render.yaml` (`APP_URL`, `FRONTEND_URL`, `SANCTUM_STATEFUL_DOMAINS`, `/api/*` rewrite destination) at sa `backend/config/cors.php`.

---

## 7. One-Time Seed (Kapag Bago/Empty pa ang Supabase DB)

Gawin ito nang hiwalay sa Staging Backend at Production Backend:
1. Render Dashboard → Backend Service → **Environment** → idagdag ang `SEED_ON_BOOT` = `true` → **Save** (magre-redeploy).
2. Kapag **Live** na at na-verify nang may nurse account, **burahin ang `SEED_ON_BOOT`** → **Save** (para hindi ma-reset ang nurse password sa bawat restart).

---

## 8. Verification Checklist

- **Staging**
  - `https://pupbc-carelink-staging-api.onrender.com/api/health` → `"database": "connected"`
  - `https://pupbc-carelink-staging.onrender.com` → naglo-load at makaka-login
- **Production**
  - `https://pupbc-carelink-prod-api.onrender.com/api/health` → `"database": "connected"`
  - `https://pupbc-carelink-prod.onrender.com` → naglo-load, makaka-login, at nagpapadala ng totoong OTP email
