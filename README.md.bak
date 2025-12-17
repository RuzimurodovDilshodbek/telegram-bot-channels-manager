# Telegram Bot Channels Manager

Telegram bot orqali vakansiyalarni boshqarish va kanallar bo'yicha tarqatish tizimi.

## 📋 Tizim haqida

Bu tizim [oson-ish-api](https://new.osonish.uz) dan tasdiqlangan vakansiyalarni qabul qilib, moderator kanali orqali tasdiqlashni amalga oshiradi va kanallarga tarqatadi. Har bir post uchun click tracking funksiyasi mavjud.

### Asosiy imkoniyatlar:
- ✅ Vakansiyalarni avtomatik qabul qilish (oson-ish-api dan)
- ✅ Moderator kanali orqali vakansiyalarni tasdiqlash/rad etish
- ✅ SOATO bo'yicha hududiy kanallarga avtomatik tarqatish
- ✅ Click tracking va statistika
- ✅ Filament Admin Panel (o'zbek tilida)
- ✅ Dashboard: umumiy statistika, grafiklar, TOP vakansiyalar
- ✅ Telegram bot bilan to'liq integratsiya

## 🚀 Texnologiyalar

- **Laravel 10.x** - Backend framework
- **Filament 3.x** - Admin panel
- **PostgreSQL/MySQL** - Database
- **Redis** - Queue va cache
- **Telegram Bot API** - Bot integration

## 📦 O'rnatish

### 1. Loyihani klonlash

```bash
git clone <repository-url> telegram-bot-channels-manager
cd telegram-bot-channels-manager
```

### 2. Dependencies o'rnatish

```bash
composer install
npm install && npm run build
```

### 3. .env faylni sozlash

```.env
cp .env.example .env
```

`.env` faylda quyidagilarni to'ldiring:

```env
APP_NAME="Telegram Bot Channels Manager"
APP_URL=https://admin.ishchi-bozor.uz
APP_ENV=production
APP_DEBUG=false

# Database (PostgreSQL tavsiya etiladi)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=telegram_bot_channels
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Redis (Queue va Cache uchun)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# Telegram Bot
TELEGRAM_BOT_TOKEN=7233377843:AAFCNyUdcE1is7ekkQZd-SHb1nI9EsW7kpo
TELEGRAM_WEBHOOK_URL=https://admin.ishchi-bozor.uz/api/telegram/webhook

# Oson-Ish API Webhook Secret
OSON_ISH_WEBHOOK_SECRET=TgBotCh@nn3lM@n@g3r!2025#SecureKey$
```

### 4. Application key va migration

```bash
php artisan key:generate
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
```

**Admin foydalanuvchi:**
- Email: `admin@telegram-bot.local`
- Parol: `password`

### 5. Telegram Webhook o'rnatish

```bash
php artisan telegram:set-webhook
```

Webhookni tekshirish:
```bash
php artisan telegram:get-webhook
php artisan telegram:bot-info
```

### 6. Queue Worker ishga tushirish

```bash
php artisan queue:work redis --queue=click-tracking --tries=3
```

Production uchun supervisor yoki systemd ishlatilishi tavsiya etiladi.

## 🔧 Sozlash

### 1. Admin panelga kirish

https://admin.ishchi-bozor.uz/admin

### 2. Kanallarni qo'shish

**Kanallar** bo'limida:

#### a) Boshqaruv kanali (Management Channel)
- Turi: Boshqaruv kanali
- Telegram Chat ID: `-1001234567890` (misol)
- Bu kanalda vakansiyalar tasdiqlash/rad etish tugmalari bilan yuboriladi

#### b) Asosiy kanal (Main Channel)
- Turi: Asosiy kanal
- Telegram Chat ID: `-1009876543210` (misol)
- Barcha tasdiqlangan vakansiyalar bu kanalga yuboriladi

#### c) Hududiy kanallar (Regional Channels)
- Turi: Hududiy kanal
- SOATO kod: `1701` (misol: Toshkent shahri)
- Telegram Chat ID: `-1001111222333`
- Faqat shu hududga tegishli vakansiyalar yuboriladi

### 3. Botni admin qilish

Har bir kanal uchun:

1. Admin panelda kanal ro'yxatida "Bot admin qilish" tugmasini bosing
2. Ko'rsatmalarni bajaring:
   - Telegram'da kanalga o'ting
   - Kanal sozlamalariga kiring
   - Administrators → Add Admin
   - Botni qidiring va admin qiling
   - Kerakli ruxsatlarni bering:
     - ✅ Post messages
     - ✅ Edit messages
     - ✅ Delete messages

3. "Test" tugmasi orqali ulanishni tekshiring

## 🎯 Ishlash tartibi

### 1. Vakansiya qabul qilish

oson-ish-api dan vakansiya status=2 (tasdiqlangan) bo'lganda:

```
POST https://admin.ishchi-bozor.uz/api/vacancies
X-Webhook-Secret: TgBotCh@nn3lM@n@g3r!2025#SecureKey$

{
  "source": "oson-ish",
  "source_id": 12345,
  "title": "PHP Developer",
  "company_name": "IT Company",
  "region_soato": "1701",
  "min_salary": 5000000,
  ...
}
```

### 2. Moderator kanali

Vakansiya avtomatik **boshqaruv kanaliga** yuboriladi:

```
🏢 PHP Developer

🏭 Kompaniya: IT Company
📍 Joylashuv: Toshkent shahri
💰 Maosh: 5 000 000 - 8 000 000 so'm
...

[✅ Tasdiqlash] [❌ Rad etish]
```

### 3. Tasdiqlash jarayoni

Admin tugma bosadi:
- **✅ Tasdiqlash** → Asosiy va hududiy kanallarga yuboriladi
- **❌ Rad etish** → Rad etiladi, hech qaerga yuborilmaydi

### 4. Kanallarga tarqatish

Tasdiqlangan vakansiya:
- ✅ Asosiy kanalga (har doim)
- ✅ Hududiy kanalga (agar SOATO mos kelsa)

Har bir postga unique tracking link beriladi:
```
https://admin.ishchi-bozor.uz/track/aBc123XyZ456
```

### 5. Click Tracking

Foydalanuvchi tracking linkni bossa:
- ✅ Click saqlanadi (IP, user agent, vaqt)
- ✅ Bot detection
- ✅ Rate limiting (10 bosish/5 daqiqa)
- ✅ Deduplication (5 daqiqa oyna)
- ↪️ Asl saytga redirect: `https://new.osonish.uz/vacancies/12345`

## 📊 Dashboard va Statistika

### Dashboard widgetlari:
1. **StatsOverview** - 6 ta asosiy metrika
2. **VacancyClicksChart** - Oxirgi 7 kun grafigi
3. **TopVacanciesTable** - Eng ko'p bosilgan TOP 10

### Vakansiyalar jadvalida:
- Filter: Status, Manba, Viloyat, Sana
- Bulk actions: Tanlanganlarni tasdiqlash/rad etish
- Actions: Tasdiqlash, Rad etish, Ko'rish, Saytda ochish

## 🛠 Artisan Commands

```bash
# Webhook sozlash
php artisan telegram:set-webhook

# Webhook o'chirish
php artisan telegram:set-webhook --delete

# Webhook ma'lumotlarini ko'rish
php artisan telegram:get-webhook

# Bot ma'lumotlarini ko'rish
php artisan telegram:bot-info
```

## 📡 API Endpoints

### Webhook (oson-ish-api uchun)

```http
POST /api/vacancies
X-Webhook-Secret: TgBotCh@nn3lM@n@g3r!2025#SecureKey$
Content-Type: application/json

{
  "source": "oson-ish",
  "source_id": 12345,
  "title": "...",
  ...
}
```

### Telegram Webhook

```http
POST /api/telegram/webhook
```

### Tracking

```http
GET /track/{trackingCode}
```

### Statistika

```http
GET /api/vacancies/{id}/statistics
```

## 🔒 Xavfsizlik

1. **Webhook Secret** - Barcha API so'rovlarda tekshiriladi
2. **Bot Detection** - Botlar filtrlash
3. **Rate Limiting** - Spam oldini olish
4. **HTTPS** - Production da majburiy

## 🐛 Debug

### Loglar

```bash
tail -f storage/logs/laravel.log
```

### Queue Failed Jobs

```bash
php artisan queue:failed
php artisan queue:retry all
```

### Telegram xatolari

Webhook xatolari:
```bash
php artisan telegram:get-webhook
```

Bot ulanishini tekshirish:
```bash
php artisan telegram:bot-info
```

## 📝 Production Deployment

### 1. Server tayyorlash

```bash
# Dependencies
sudo apt install php8.1 php8.1-fpm php8.1-pgsql php8.1-redis php8.1-mbstring php8.1-xml php8.1-curl
sudo apt install postgresql redis-server nginx

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Proyektni deploy qilish

```bash
cd /var/www/telegram-bot-channels-manager
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

### 3. Queue Worker (Supervisor)

`/etc/supervisor/conf.d/telegram-bot-worker.conf`:

```ini
[program:telegram-bot-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/telegram-bot-channels-manager/artisan queue:work redis --queue=click-tracking --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/telegram-bot-channels-manager/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start telegram-bot-worker:*
```

### 4. Nginx konfiguratsiyasi

`/etc/nginx/sites-available/admin.ishchi-bozor.uz`:

```nginx
server {
    listen 443 ssl http2;
    server_name admin.ishchi-bozor.uz;
    root /var/www/telegram-bot-channels-manager/public;

    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/key.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 📞 Muammolar va Yordam

### Umumiy muammolar:

**1. Webhook ishlamayapti**
- SSL sertifikat mavjudligini tekshiring
- Webhook URL to'g'ri sozlanganini tekshiring
- `php artisan telegram:get-webhook` orqali xatoliklarni ko'ring

**2. Queue ishlamayapti**
- Redis serverini tekshiring: `redis-cli ping`
- Worker ishlab turganini tekshiring: `sudo supervisorctl status`
- Failed joblarni ko'ring: `php artisan queue:failed`

**3. Kanalga xabar yuborilmayapti**
- Bot kanalga admin qilinganini tekshiring
- Chat ID to'g'ri kiritilganini tekshiring (- belgisi bilan)
- "Test" tugmasi orqali ulanishni tekshiring

**4. Click tracking ishlamayapti**
- Queue worker ishlab turganini tekshiring
- Redis ulanishini tekshiring
- Tracking URL to'g'ri sozlanganini tekshiring

## 📜 License

Proprietary - Barcha huquqlar himoyalangan.

## 👨‍💻 Author

Developed for **Ishchi Bozor** (admin.ishchi-bozor.uz)

Integration with **Oson Ish API** (https://new.osonish.uz)
