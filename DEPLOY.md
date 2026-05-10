# Ghid deploy FlotaMuntenia pe cPanel

> Pasii concreti pentru deploy pe cPanel shared hosting cu PHP 8.3, MySQL si Git Version Control.
> Subdomeniu tinta: `app.flotamuntenia.ro`

---

## Cerinte verificate inainte de deploy

Confirma in cPanel ca ai:

- [ ] **PHP 8.3** disponibil (cPanel > Select PHP Version)
- [ ] Extensii PHP active: `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `json`, `curl`, `zip`, `tokenizer`, `xml`, `fileinfo`, `gd`
- [ ] **Composer** disponibil (Terminal cPanel: `composer --version` ar trebui sa raspunda)
- [ ] **Git Version Control** activ in cPanel (sub Files)
- [ ] **Terminal** activ sau acces SSH

Daca **Composer lipseste**, instaleaza-l in home folder:
```bash
cd ~ && curl -sS https://getcomposer.org/installer | php
mv composer.phar bin/composer
chmod +x bin/composer
echo 'export PATH="$HOME/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
composer --version
```

---

## Faza 1 — Subdomeniu si baza de date

### 1.1 Creeaza subdomeniul

cPanel > **Domains** > **Create A New Domain**:

- Domain: `app.flotamuntenia.ro`
- **Document Root**: `/home/USER/flotamuntenia/public`
  - **Important**: NU lasa `public_html/app.flotamuntenia.ro`!
  - Pune codul Laravel in `/home/USER/flotamuntenia/` si subdomeniul sa pointeze direct la `public/`.

### 1.2 Creeaza baza de date

cPanel > **MySQL Databases**:

1. Create New Database:
   - Nume: `flotamuntenia` (devine `USER_flotamuntenia`)
2. Add New User:
   - Nume: `flotaadmin` (devine `USER_flotaadmin`)
   - Parola: **genereaza una tare** (min 20 caractere, salveaz-o intr-un password manager)
3. Add User To Database:
   - User → Database → **ALL PRIVILEGES**

**Noteaza-ti pe hartie/notepad**:
- DB name: `USER_flotamuntenia`
- DB user: `USER_flotaadmin`
- DB password: `xxxxxxxxxxxxxxxxxx`
- DB host: `localhost`

---

## Faza 2 — Git Version Control

### 2.1 Pregateste accesul

In GitHub > Settings > Deploy Keys (pentru repo-ul tau) sau Personal Access Tokens:
- Genereaza o cheie SSH publica pe cPanel: Terminal > `ssh-keygen -t ed25519 -C "cpanel-flotamuntenia"`
- Copiaza continutul `~/.ssh/id_ed25519.pub` ca **Deploy Key** in GitHub repo

### 2.2 Cloneaza prin cPanel

cPanel > **Git Version Control** > **Create**:

- Clone URL: `git@github.com:USERNAME/flotamuntenia.git` (sau HTTPS cu token)
- Repository Path: `/home/USER/flotamuntenia`
- Branch: `main`
- Bifeaza "Clone a Repository"

Dupa clone, vei avea cod in `/home/USER/flotamuntenia/`. Subdomeniul deja pointeaza la `public/` din interior.

---

## Faza 3 — Configurare aplicatie

Deschide **Terminal cPanel** si navigheaza la proiect:

```bash
cd ~/flotamuntenia
```

### 3.1 Instaleaza dependentele PHP

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

### 3.2 Configureaza .env

```bash
cp .env.production.example .env
nano .env
```

Completeaza:
- `DB_DATABASE=USER_flotamuntenia`
- `DB_USERNAME=USER_flotaadmin`
- `DB_PASSWORD=parola_ta`
- `GOOGLE_MAPS_API_KEY=cheia_restrictionata`
- `APP_URL=https://app.flotamuntenia.ro` (deja completat)

Salveaza (Ctrl+O, Enter, Ctrl+X).

### 3.3 Genereaza APP_KEY

```bash
php artisan key:generate --force
```

### 3.4 Permisiuni

```bash
chmod -R 755 storage bootstrap/cache
# Pe unele cPanel-uri trebuie 775; daca dai "permission denied" la log-uri:
# chmod -R 775 storage bootstrap/cache
```

### 3.5 Migrari + seed master data

```bash
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
```

> **NU rula `php artisan migrate:fresh`** — sterge tot.
> **NU rula `php artisan db:seed` fara `--class=ProductionSeeder`** — ar incarca DatabaseSeeder cu conturi demo `parola123`.

### 3.6 Creeaza admin-ul real

```bash
php artisan app:create-admin
```
Iti va cere interactiv nume, email, parola (min 8 caractere).

### 3.7 Symlink storage si cache

```bash
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> Daca modifici `.env` mai tarziu, ruleaza `php artisan config:clear && php artisan config:cache`.

---

## Faza 4 — SSL si Cron

### 4.1 SSL gratuit (Let's Encrypt)

cPanel > **SSL/TLS Status** > selecteaza `app.flotamuntenia.ro` > **Run AutoSSL**.
Asteapta ~2 min. Verifica ca `https://app.flotamuntenia.ro` raspunde cu lacatel verde.

### 4.2 Cron pentru scheduler Laravel

cPanel > **Cron Jobs** > Add New Cron Job:

- Common Settings: **Once Per Minute** (`* * * * *`)
- Command:
  ```
  /usr/local/bin/php /home/USER/flotamuntenia/artisan schedule:run >> /dev/null 2>&1
  ```

> Inlocuieste `/usr/local/bin/php` cu calea exacta a PHP 8.3 — afli cu `which php` in Terminal cPanel sau `cPanel > Select PHP Version > scroll jos > Command Line PHP Path`.

---

## Faza 5 — Verificare

1. Deschide `https://app.flotamuntenia.ro/login`
2. Logheaza-te cu admin-ul creat la pasul 3.6
3. Verifica:
   - [ ] Dashboard se incarca
   - [ ] Setari > Catalog > vezi cele 5 produse seedate (45, 46, 47, 52, 55)
   - [ ] Setari > Cote TVA > vezi 19% si 9%
   - [ ] Nu apare "VITE manifest not found" — daca da, `public/build/` lipseste (verifica `git ls-files public/build/ | head`)
4. **Restrictioneaza cheia Google Maps**: Cloud Console > Credentials > cheia ta > Application restrictions > HTTP referrers: `https://app.flotamuntenia.ro/*`

---

## Update-uri ulterioare

Pentru fiecare deploy nou:

**Local (pe masina ta):**
```bash
npm run build              # rebuild asset-uri Tailwind/Vite
git add public/build .
git commit -m "deploy: <mesaj>"
git push origin main
```

**Pe cPanel:**
```bash
cd ~/flotamuntenia
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Pentru update-uri fara migratii noi, poti folosi doar `git pull` din UI-ul Git Version Control + `php artisan config:cache` din terminal.

---

## Troubleshooting

**Eroare: "No application encryption key has been specified"**
→ Ai uitat `php artisan key:generate --force`. Ruleaz-o, apoi `php artisan config:cache`.

**Eroare: "SQLSTATE[HY000] [1045] Access denied"**
→ Credentialele DB din `.env` sunt gresite sau user-ul nu are privilegii pe DB. Reverifica in cPanel > MySQL Databases.

**Eroare 500 fara detalii**
→ Verifica `storage/logs/laravel-YYYY-MM-DD.log`. Daca log-ul nu se scrie: `chmod -R 775 storage bootstrap/cache`.

**Asset-urile CSS/JS nu se incarca**
→ `public/build/manifest.json` lipseste. Verifica: `ls public/build/`. Daca e gol, ai uitat `npm run build` local inainte de push.

**"419 Page Expired" la login**
→ Sesiunile nu se salveaza. Verifica `SESSION_DRIVER=database` in `.env` si ca tabelul `sessions` exista (`php artisan session:table && php artisan migrate --force` daca lipseste).
