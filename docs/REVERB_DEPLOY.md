# Reverb Deploy

Gunakan Reverb untuk live update `WO Monitoring` dari event produksi seperti:

- start WO
- pause / resume
- finish WO
- QDC
- sync/hourly

## Env server

Tambahkan ke `.env` server:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=682561
REVERB_APP_KEY=e3kon1od7txjg7xvq1yz
REVERB_APP_SECRET=hlzwznunxyhmrvcw09zc

REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

REVERB_HOST=incoming.nooneasku.online
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Jika nanti Reverb sudah diproxy lewat Nginx + HTTPS, ubah:

```env
REVERB_PORT=443
REVERB_SCHEME=https
```

## Deploy

```bash
cd /var/www/material_incoming
git pull origin main
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan optimize:clear
php artisan view:cache
php artisan config:clear
```

## Supervisor

Copy file ini:

- `deploy/supervisor/material_incoming_reverb.conf`

ke:

- `/etc/supervisor/conf.d/material_incoming_reverb.conf`

Lalu jalankan:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start material_incoming_reverb
sudo supervisorctl status material_incoming_reverb
```

## Restart setelah deploy

Kalau Reverb sudah terdaftar di supervisor:

```bash
sudo supervisorctl restart material_incoming_reverb
```

Kalau perlu reload PHP-FPM dan Nginx:

```bash
sudo systemctl restart php8.4-fpm
sudo systemctl reload nginx
```
