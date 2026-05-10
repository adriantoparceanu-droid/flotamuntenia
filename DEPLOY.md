# Ghid deploy FlotaMuntenia pe cPanel — FĂRĂ SSH/Terminal

> Deploy automat prin **Git Version Control** + script **`.cpanel.yml`**.
> Singurul lucru manual: editarea `.env` prin **File Manager** (o singură dată).
> Subdomeniu țintă: `app.flotamuntenia.ro`

---

## Cum funcționează fluxul automat

1. Tu modifici cod local → `git push origin main`
2. În cPanel → **Git Version Control** → click **"Update from Remote"** → apoi tab **"Pull or Deploy"** → click **"Deploy HEAD Commit"**
3. cPanel rulează automat ce-i în `.cpanel.yml`:
   - `composer install --no-dev`
   - `php artisan migrate --force`
   - `php artisan db:seed --class=ProductionSeeder` (idempotent)
   - `php artisan app:bootstrap-admin` (creează admin dacă env vars sunt setate)
   - `php artisan storage:link`
   - `php artisan optimize:clear && config:cache + route:cache + view:cache + event:cache`

**Nicio comandă manuală în Terminal.** Tot prin click-uri în cPanel UI.

---

## Cerințe verificate o singură dată în cPanel

- [ ] **MultiPHP CLI Manager** → setează PHP 8.3 pentru home directory (`/home/flotamun`)
- [ ] **MultiPHP Manager** → setează PHP 8.3 pentru subdomeniul `app.flotamuntenia.ro`
- [ ] **Select PHP Version** → extensiile active: `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `json`, `curl`, `zip`, `tokenizer`, `xml`, `fileinfo`, `gd`
- [ ] Composer trebuie să existe la `/usr/local/bin/composer` (cPanel standard)

---

## Pas 1 — Creează `.env` prin File Manager *(o singură dată)*

1. cPanel → **File Manager**
2. Navighează la `/home/flotamun/flotamuntenia/`
3. **Settings** (sus dreapta) → bifează **"Show Hidden Files (dotfiles)"**
4. Selectează `.env.production.example` → click **Copy** → destinație `/home/flotamun/flotamuntenia/` → numele copiei: `.env`
5. Selectează `.env` → click **Edit** (sus) → confirmă Edit la dialog
6. Completează valorile:
   - `DB_DATABASE=flotamun_flotamuntenia`
   - `DB_USERNAME=flotamun_flotaadmin`
   - `DB_PASSWORD=parola_ta_db`
   - `GOOGLE_MAPS_API_KEY=cheia_ta_restrictionata`
7. **Pentru primul deploy** — decomentează (șterge `#`) și completează:
   ```
   INITIAL_ADMIN_EMAIL=adrian.toparceanu@gmail.com
   INITIAL_ADMIN_PASSWORD=O_parola_lunga_si_unica_minim_8_caractere
   INITIAL_ADMIN_NAME="Adrian Toparceanu"
   ```
8. **Save Changes**

> APP_KEY se generează **mai târziu**, prin .cpanel.yml — sau manual, vezi mai jos.

---

## Pas 2 — Generează APP_KEY *(o singură dată)*

`.cpanel.yml` **nu generează** APP_KEY automat (ar suprascrie unul existent la fiecare deploy). Soluții:

### Opțiunea A — generează local și paste în `.env`

Pe mașina ta locală:
```bash
cd ~/Documents/Claude/FlotaMuntenia/app-noua
php artisan key:generate --show
```
Comanda îți afișează un string gen `base64:xxxxxxx==`. Copiază-l în `.env` pe server la linia `APP_KEY=`.

### Opțiunea B — generează prin un comand artisan separat la primul deploy

Modifică temporar `.cpanel.yml` ca să includă o singură dată `php artisan key:generate --force`, apoi scoate-l.

---

## Pas 3 — Primul deploy (declanșat de tine din UI cPanel)

1. cPanel → **Git Version Control**
2. La repo-ul `flotamuntenia` click pe **Manage**
3. Tab **"Pull or Deploy"**:
   - Click **"Update from Remote"** *(face git pull)*
   - Click **"Deploy HEAD Commit"** *(rulează `.cpanel.yml`)*
4. Așteaptă ~1-2 min. Pagina îți va arăta log-ul cu output din comenzi:
   - ✅ "Generating optimized autoload files"
   - ✅ "Migration table created successfully"
   - ✅ "Migrating: ... Migrated successfully"
   - ✅ "[bootstrap-admin] Admin creat: adrian.toparceanu@gmail.com"
   - ✅ "Configuration cache cleared / cached"

> Dacă vezi erori, fă screenshot la log și trimite-mi-l — îți spun ce să corectezi.

---

## Pas 4 — Verificare în browser

1. Deschide `https://app.flotamuntenia.ro/login`
2. Loghează-te cu emailul și parola din `INITIAL_ADMIN_*`
3. Verifică:
   - [ ] Dashboard se încarcă
   - [ ] Setări > Catalog → vezi cele 5 produse seed-uite (45, 46, 47, 52, 55)
   - [ ] Setări > Cote TVA → vezi 19% și 9%
   - [ ] CSS/JS funcționează (NU apare layout "naked")

❌ **"VITE manifest not found"** → `public/build/` lipsește. Pe local: `npm run build`, commit, push, redeploy.
❌ **Eroare 500** → verifică `storage/logs/laravel.log` prin File Manager.
❌ **"No application encryption key"** → APP_KEY lipsește în `.env`. Vezi Pas 2.
❌ **"SQLSTATE[HY000] [1045] Access denied"** → credențiale DB greșite în `.env`.

---

## Pas 5 — Securizare după primul deploy

### 5.1 Schimbă parola admin
1. Login pe `app.flotamuntenia.ro`
2. Click pe profil (dreapta sus) → **Profil**
3. Schimbă parola cu una nouă, unică, salvată în password manager

### 5.2 Șterge INITIAL_ADMIN_* din `.env`
1. cPanel → File Manager → `/home/flotamun/flotamuntenia/.env` → Edit
2. **Șterge** sau **comentează cu `#`** cele 3 linii `INITIAL_ADMIN_*`
3. Save

### 5.3 Redeploy *(pentru cache nou)*
Git Version Control → Manage → Deploy HEAD Commit (chiar dacă nu s-a schimbat nimic în git, refresh-uiește cache-ul).

### 5.4 Restricționează cheia Google Maps
1. [Google Cloud Console](https://console.cloud.google.com/) → APIs & Services → Credentials
2. Cheia ta → Application restrictions → **HTTP referrers**
3. Adaugă: `https://app.flotamuntenia.ro/*` și `https://*.flotamuntenia.ro/*`
4. API restrictions → Restrict key → bifează doar **Maps JavaScript API**, **Geocoding API** (dacă folosești)
5. Save

---

## Pas 6 — SSL gratuit (Let's Encrypt prin AutoSSL)

1. cPanel → **SSL/TLS Status**
2. Selectează `app.flotamuntenia.ro`
3. Click **"Run AutoSSL"**
4. Așteaptă ~2 min → verifică https://app.flotamuntenia.ro cu lacăt verde

---

## Pas 7 — Cron pentru Laravel Scheduler

1. cPanel → **Cron Jobs**
2. **Add New Cron Job**:
   - Common Settings: **Once Per Minute** (`* * * * *`)
   - Command:
     ```
     /usr/local/bin/php /home/flotamun/flotamuntenia/artisan schedule:run >> /dev/null 2>&1
     ```
3. Add New Cron Job

> Dacă `/usr/local/bin/php` nu e PHP 8.3, înlocuiește cu calea exactă (vezi cPanel > Select PHP Version > "Command Line PHP Path").

---

## Update-uri ulterioare *(workflow standard — deploy prin URL HTTPS)*

După primul deploy reușit, NU mai e nevoie să intri în cPanel pentru deploy-uri viitoare. Există un **webhook PHP** la `public/deploy.php` care face TOTUL (git pull + composer + migrate + cache).

### Pregătire one-time pentru webhook *(DOAR la primul deploy)*

1. cPanel → **File Manager** → activează "Show Hidden Files"
2. Navighează la `/home/flotamun/` *(home folder, NU în `flotamuntenia/`)*
3. **+ File** → numele fișierului: `.deploy-token`
4. Edit `.deploy-token` → paste-uiește **doar token-ul, fără ghilimele, fără spații, fără newline**:
   ```
   da3149b7a4cd0f9b24dfda8fd9315584fbd702bff975d94c63399b0f865c28d0
   ```
5. Save

### Workflow de deploy

**Pe local:**
```bash
# 1. modifici cod
npm run build              # DOAR dacă ai modificat CSS/JS/Blade
git add .
git commit -m "deploy: <mesaj>"
git push origin main
```

**Pe browser (oriunde):**
Deschide:
```
https://app.flotamuntenia.ro/deploy.php?token=da3149b7a4cd0f9b24dfda8fd9315584fbd702bff975d94c63399b0f865c28d0
```

Vei vedea log-ul deploy-ului în timp real:
- `>>> Git fetch ... OK`
- `>>> Git reset hard origin/main ... OK`
- `>>> Composer install ... OK`
- `>>> Migrate ... OK`
- `>>> Seed production ... OK`
- `>>> Storage link ... OK`
- `>>> Config cache ... OK`
- ...
- `STATUS: OK`

> 💡 **Salvează URL-ul ca bookmark** — un click = deploy.
>
> ⚠️ **NU partaja URL-ul** — oricine îl are poate forța deploy.
>
> ⚠️ Token-ul este în `~/.deploy-token` pe server (nu în git). Dacă bănuiești compromitere: înlocuiește-l prin File Manager.

### Cum funcționează `deploy.php`

- Verifică token-ul cu comparație timing-safe (`hash_equals`)
- Forțează HTTPS (refuză HTTP)
- Rulează aceleași comenzi ca `.cpanel.yml`, dar prin `shell_exec()`
- Loghează fiecare apel (succes/eșec) în `storage/logs/deploy.log`
- Continuă chiar dacă o comandă eșuează (vrei să vezi toate erorile, nu doar prima)

> Dacă `shell_exec()` e dezactivat în `php.ini`, primești mesaj clar cu soluția. În cPanel se rezolvă din **Select PHP Version → Options → disable_functions** (scoate `shell_exec`).

---

## Troubleshooting

**`composer install` eșuează cu "memory_limit"**
→ În `.cpanel.yml`, înlocuiește comanda composer cu:
  `/usr/local/bin/php -d memory_limit=512M /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction --quiet`

**`composer install` eșuează cu "Could not find package"**
→ cPanel folosește alt PHP la CLI decât crezi. Verifică **MultiPHP CLI Manager** → setează PHP 8.3 pentru home folder.

**`php artisan migrate` eșuează cu "could not find driver"**
→ Lipsește extensia `pdo_mysql`. cPanel → Select PHP Version → bifează `pdo_mysql`.

**Site arată 500 după deploy reușit**
→ Permisiuni storage. În `.cpanel.yml` adaugă temporar:
  `- chmod -R 775 storage bootstrap/cache`

**Eroare "419 Page Expired" la login**
→ Sesiunile nu se salvează. Verifică în `.env`: `SESSION_DRIVER=database`. Dacă tabelul `sessions` lipsește:
  - Adaugă în `.cpanel.yml`: `- /usr/local/bin/php artisan session:table` (doar o dată)
  - Redeploy

**Vrei să rulezi un comand artisan manual (ad-hoc)**
→ Modifică temporar `.cpanel.yml` cu linia respectivă, commit + push + deploy. După, scoate linia + redeploy. Sau cere reactivare Terminal de la providerul de hosting.
