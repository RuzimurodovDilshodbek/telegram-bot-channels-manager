# Telegram Bot Channels Manager

Telegram bot orqali vakansiyalarni boshqarish va kanallar bo'yicha tarqatish tizimi.

## 📋 Tizim haqida

Bu tizim [oson-ish-api](https://new.osonish.uz) dan tasdiqlangan vakansiyalarni qabul qilib, moderator kanali orqali tasdiqlashni amalga oshiradi va kanallarga tarqatadi. Har bir post uchun click tracking funksiyasi mavjud.

### Asosiy imkoniyatlar:
- ✅ Vakansiyalarni avtomatik qabul qilish (oson-ish-api dan)
- ✅ **Real-time status sinxronizatsiyasi** - Status o'zgarishi avtomatik kanallarni yangilaydi
- ✅ Moderator kanali orqali vakansiyalarni tasdiqlash/rad etish
- ✅ **Kanal adminlari tizimi** - Faqat ruxsat etilgan adminlar tasdiqlashi mumkin
- ✅ SOATO bo'yicha hududiy kanallarga avtomatik tarqatish
- ✅ **Bir kanal bir nechta hududga xizmat qilishi** (JSONB array)
- ✅ **Alohida vakansiya ma'lumotlar bazasi** - OsonIshVacancy table bilan batafsil ma'lumotlar
- ✅ Click tracking va statistika
- ✅ Filament Admin Panel (o'zbek tilida)
- ✅ Dashboard: umumiy statistika, grafiklar, TOP vakansiyalar
- ✅ **Telegram xabarlarda kanal havolasi ko'rsatiladi**
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

### 5. Regions jadvali (Ma'lumotlar bazasi)

**Muhim:** `regions` jadvali uchun migration yozish shart emas. Bu jadval to'g'ridan-to'g'ri ma'lumotlar bazasiga import qilingan (SOATO kodlari va hudud nomlari). Faqat `Region` modelidan foydalaning:

```php
// Region model mavjud: app/Models/Region.php
$regions = Region::orderBy('name_uz')->get();
```

Jadval tuzilmasi:
- `soato` - SOATO kod (masalan: 1701 - Toshkent shahri)
- `name_uz` - O'zbek tilida nom
- `name_ru` - Rus tilida nom
- `name_en` - Ingliz tilida nom
- `name_cyrl` - Kirill yozuvida nom

### 6. Telegram Webhook o'rnatish

```bash
php artisan telegram:set-webhook
```

Webhookni tekshirish:
```bash
php artisan telegram:get-webhook
php artisan telegram:bot-info
```

### 7. Queue Worker ishga tushirish

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
- SOATO kodlar: `1701, 1702` (bir kanal bir nechta hududga xizmat qilishi mumkin)
- Telegram Chat ID: `-1001111222333`
- Faqat shu hududlarga tegishli vakansiyalar yuboriladi

**Yangilik:** 2025-yil dekabr oyidan boshlab hududiy kanallar bir nechta SOATO kodlarni qo'llab-quvvatlaydi. Masalan, bitta kanal Toshkent shahri va Toshkent viloyatiga xizmat qilishi mumkin.

### 3. Kanal Adminlarini Qo'shish

**Yangi funksiya (2025-yil dekabr):** Endi faqat ruxsat etilgan adminlar boshqaruv kanalida vakansiyalarni tasdiqlashi/rad etishi mumkin.

**Admin panelda → Sozlamalar → Adminlar:**

1. **Yangi admin qo'shish** tugmasini bosing
2. Kerakli ma'lumotlarni kiriting:
   - **Kanal**: Qaysi kanal uchun admin (odatda Boshqaruv kanali)
   - **Ism**: Admin ismi (masalan: "Alisher Valiyev")
   - **Telegram Username**: @username (ixtiyoriy)
   - **Telegram User ID**: Admin ning Telegram foydalanuvchi ID raqami

**Telegram User ID ni qanday olish mumkin:**
- @userinfobot botiga `/start` buyrug'ini yuboring
- Bot sizning user ID ingizni ko'rsatadi
- Yoki admin Telegram profiliga forward qilib user ID ni oling

**Muhim eslatmalar:**
- ✅ Bir admin bir nechta kanalda ishlashi mumkin
- ✅ Bir kanalga bir xil Telegram ID ikki marta qo'shib bo'lmaydi
- ✅ Admin faol/nofaol qilish mumkin
- ✅ Barcha ruxsatsiz urinishlar log ga yoziladi

### 4. Botni admin qilish

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

oson-ish-api dan vakansiya **har qanday status o'zgarishida** webhook yuboriladi:

```
POST https://admin.ishchi-bozor.uz/api/vacancies
X-Webhook-Secret: TgBotCh@nn3lM@n@g3r!2025#SecureKey$

{
  "vacancy_id": 12345,
  "vacancy_status": 2,
  "title": "PHP Developer",
  "count": 1,
  "company_tin": "123456789",
  "company_name": "IT Company",
  "payment_name": "Oylik",
  "work_name": "Odatiy (ish joyida)",
  "min_salary": 5000000,
  "max_salary": 8000000,
  "work_experience_name": "1-3 yil",
  "age_from": 20,
  "age_to": 35,
  "gender": 3,
  "for_whos_name": null,
  "phone": "+998901234567",
  "hr_fio": "Alisher Valiyev",
  "region_code": "1701",
  "region_name": "Toshkent shahri",
  "district_name": "Chilonzor tumani",
  "description": "Talablar va ko'nikmalar...",
  "show_url": "https://new.osonish.uz/vacancies/12345"
}
```

**Yangi format (2025-yil dekabr):**
- `vacancy_id` - Oson-Ish dagi vacancy ID
- `vacancy_status` - Real-time holat (2 = faol, boshqalar = nofaol)
- Sys_config dan name lar: `payment_name`, `work_name`, `work_experience_name`, `for_whos_name`
- HR ma'lumotlari: `phone`, `hr_fio`
- HTML taglarsiz `description`

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

**Faqat ruxsat etilgan admin** tugma bosadi:
- **✅ Tasdiqlash** → Asosiy va hududiy kanallarga yuboriladi
- **❌ Rad etish** → Rad etiladi, hech qaerga yuborilmaydi

**Agar admin emas foydalanuvchi tugmani bossa:**
- 🚫 "Sizda ruxsat yo'q. Faqat adminlar tasdiqlashi mumkin." xabari chiqadi
- Urinish log ga yoziladi (xavfsizlik uchun)

### 4. Kanallarga tarqatish

Tasdiqlangan vakansiya:
- ✅ Asosiy kanalga (har doim)
- ✅ Hududiy kanallarga (agar SOATO mos kelsa)

Har bir postga unique tracking link beriladi:
```
https://admin.ishchi-bozor.uz/track/aBc123XyZ456
```

**Xabar tuzilmasi (yangilangan):**
```
📌 PHP Developer

📍 Joylashuv: Toshkent shahri
💰 Maosh: 5 000 000 - 8 000 000 so'm
...

📝 Batafsil ma'lumot

📣 Kanal: @ishchi_bozor_uz    ← Kanal havolasi
```

**Yangilik:** Har bir xabarda qaysi kanalga yuborilganligi ko'rsatiladi. Agar kanalda @username bo'lsa, havola sifatida ko'rsatiladi.

### 5. Click Tracking

Foydalanuvchi tracking linkni bossa:
- ✅ Click saqlanadi (IP, user agent, vaqt)
- ✅ Bot detection
- ✅ Rate limiting (10 bosish/5 daqiqa)
- ✅ Deduplication (5 daqiqa oyna)
- ↪️ Asl saytga redirect: `https://new.osonish.uz/vacancies/12345`

## 📊 Dashboard va Statistika

**Dashboard yangilangan (2025-yil dekabr):** Yanada ixcham va responsive dizayn.

### Dashboard widgetlari:
1. **StatsOverview** - 6 ta asosiy metrika (3 ustun, 2 qator)
   - Jami vakansiyalar
   - Kutilmoqda (pending)
   - Nashr qilingan
   - Jami bosishlar (bugungi bilan)
   - Faol kanallar
   - O'rtacha bosish

2. **VacancyClicksChart** - Oxirgi 7 kun grafigi (responsive, max height: 300px)
   - Bar chart
   - Har kunlik bosishlar soni
   - O'zbek tilida sanalar

3. **TopVacanciesTable** - Eng ko'p bosilgan TOP 10
   - ID, Vakansiya nomi, Kompaniya, Hudud
   - Bosishlar soni (badge)
   - Nashr vaqti

**Sidebar:** Endi tor va yig'iladigan (collapsible), desktop'da 14rem (224px)

### Vakansiyalar jadvalida:
- Filter: Status, Manba, Viloyat, Sana
- Bulk actions: Tanlanganlarni tasdiqlash/rad etish (**faqat adminlar ko'radi**)
- Actions:
  - ✅ Tasdiqlash (**faqat kanal adminlari**)
  - ❌ Rad etish (**faqat kanal adminlari**)
  - 👁️ Ko'rish
  - 🔗 Saytda ochish

**Muhim:** Tasdiqlash va Rad etish tugmalari faqat boshqaruv kanali adminlari uchun ko'rinadi va ishlaydi.

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

**4.1. Tracking link 404 qaytarmoqda**
- `oson_ish_vacancies` jadvalida `show_url` maydonini tekshiring
- Agar bo'sh bo'lsa, `bot_vacancies.show_url` ishlatiladi
- Ikkalasi ham bo'sh bo'lsa, `source_id` dan URL tuziladi
- `TrackingService::getTargetUrl()` metodi uch bosqichli fallback ishlatadi
- Log faylni tekshiring: `storage/logs/laravel.log`

**5. Admin tasdiqlash tugmasini ko'rmayapti**
- Adminlar bo'limida foydalanuvchi qo'shilganini tekshiring
- Foydalanuvchining `telegram_id` to'g'ri to'ldirilganini tekshiring (User modeli)
- Admin faol holatda ekanligini tekshiring (`is_active = true`)
- Cache ni tozalang: `php artisan cache:clear`
- Policy ro'yxatdan o'tganini tekshiring: `AuthServiceProvider.php`

**6. "Sizda ruxsat yo'q" xabari chiqmoqda**
- Foydalanuvchi Telegram ID si `channel_admins` jadvalida borligini tekshiring
- Admin faol holatda ekanligini tekshiring
- To'g'ri kanal uchun admin qo'shilganligini tekshiring
- Log faylni tekshiring: `storage/logs/laravel.log` (unauthorized attempts)

## 🆕 So'nggi Yangilanishlar

### 2025-yil Dekabr (v2.0)

#### ✨ Yangi funksiyalar:

1. **Kanal Adminlari Tizimi**
   - Faqat ruxsat etilgan adminlar vakansiyalarni tasdiqlashi/rad etishi mumkin
   - Admin panelda "Adminlar" bo'limi qo'shildi
   - Bir admin bir nechta kanalda ishlashi mumkin
   - Telegram bot va Admin panelda avtorizatsiya tekshiruvi
   - Barcha ruxsatsiz urinishlar log ga yoziladi

2. **Ko'p Hududli Kanallar**
   - Bir kanal bir nechta SOATO kodlarni qo'llab-quvvatlaydi
   - JSONB array orqali amalga oshirilgan
   - Masalan: Toshkent shahri + Toshkent viloyati = bitta kanal

3. **Kanal Havolasi Xabarlarda**
   - Har bir vakansiya xabarida qaysi kanalga yuborilganligi ko'rsatiladi
   - Agar kanalda @username bo'lsa, havola sifatida chiqadi
   - `📣 Kanal: @ishchi_bozor_uz` formatida

4. **Dashboard Optimizatsiyasi**
   - Ixcham va responsive dizayn
   - Sidebar tor va yig'iladigan (14rem)
   - StatsOverview: 3 ustun, 2 qator
   - VacancyClicksChart: responsive, max height 300px
   - TopVacanciesTable: yanada ixcham

5. **OsonIshVacancy Alohida Jadval**
   - Oson-Ish dan kelgan barcha vakansiya ma'lumotlari alohida jadvalda saqlanadi
   - `oson_ish_vacancies` jadvali batafsil ma'lumotlar uchun
   - `bot_vacancies` jadvali faqat bot-ga tegishli ma'lumotlar uchun
   - Sys_config dan name lar to'g'ridan-to'g'ri saqlanadi (payment_name, work_name, etc.)
   - Status o'zgarishlari tarixini kuzatish (previous_status, status_changed_at)
   - HTML taglarsiz description (Telegram uchun moslashtirilgan)

6. **Real-time Status Sinxronizatsiyasi**
   - Oson-Ish da vakansiya statusi o'zgarganda avtomatik webhook yuboriladi
   - Barcha kanal postlari avtomatik yangilanadi
   - Nofaol bo'lganda: "🔴 Ish holati: Nofaol" ko'rsatiladi
   - Yana faol bo'lganda: avtomatik republish yoki yangilanadi
   - `SyncVacancyStatusToTelegramJob` - Observer va Schedule uchun birlashtirilgan job
   - Har 10 daqiqada qo'shimcha sync (backup uchun)

#### 🔧 Texnik o'zgarishlar:

- `channel_admins` jadvali va modeli
- `ChannelAdminService` - markazlashtirilgan avtorizatsiya
- `BotVacancyPolicy` - Laravel policy tizimi
- `TelegramWebhookController` - avtorizatsiya tekshiruvi
- `BotVacancyResource` - policy-based authorization
- `Channel.region_soato` - VARCHAR → JSONB migration
- `VacancyPublisher` - kanal parametri qo'shildi, format yangilandi
- `VacancyPublisher::handleVacancyInactivation()` - vakansiya nofaol bo'lganda postlarni yangilash
- `VacancyPublisher::handleVacancyReactivation()` - vakansiya qayta faol bo'lganda yangilash
- `TrackingService::getTargetUrl()` - tracking URL 404 muammosi hal qilindi (uch bosqichli fallback: osonIshVacancy → botVacancy → constructed URL)
- `OsonIshVacancy` model - alohida vakansiya ma'lumotlar modeli
- `BotVacancy.osonIshVacancy()` - BelongsTo relationship
- `2025_12_18_160627_create_oson_ish_vacancies_table.php` - yangi migration
- `2025_12_18_160629_simplify_bot_vacancies_table.php` - eski ustunlarni o'chirish
- `VacancyController::store()` - yangi webhook format qo'llab-quvvatlash

#### 🔗 Oson-Ish-API Integratsiya O'zgarishlari:

- `VacancyObserver` - har qanday status o'zgarishida webhook yuborish
- `SyncVacancyStatusToTelegramJob` - webhook yuborish uchun birlashtirilgan job
  - Observer dan chaqirilganda: bitta vakansiyani yuboradi (dispatchSync)
  - Schedule dan chaqirilganda: oxirgi 2 soat ichidagi barcha o'zgarishlarni yuboradi
- `routes/console.php` - har 10 daqiqada Schedule::job() ishga tushadi
- `SyncVacancyStatusToTelegramJob::getSystemConfigName()` - sys_config dan name olish
- Sys_config lar: payment_type_list, work_type_list, work_experience_list, for_who_list
- HR ma'lumotlari: `vacancy->hr?->phone`, `vacancy->hr?->fio`
- Relationship lar: company, filial.region, filial.city, hr

#### 📚 Hujjatlar:

- README yangilandi (regions table haqida eslatma)
- README yangilandi (OsonIshVacancy table va real-time sync)
- So'nggi o'zgarishlar bo'limi qo'shildi
- Troubleshooting bo'limiga admin muammolari qo'shildi
- Webhook yangi format hujjatlashtirildi

---

## 📜 License

Proprietary - Barcha huquqlar himoyalangan.

## 👨‍💻 Author

Developed for **Ishchi Bozor** (admin.ishchi-bozor.uz)

Integration with **Oson Ish API** (https://new.osonish.uz)
