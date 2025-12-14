# Qolgan Ishlar - Telegram Bot Channels Manager

## 1. FILAMENT ADMIN PANEL RESOURCES

### 1.1. BotVacancyResource yaratish

**Maqsad**: Admin panelda vakansiyalarni ko'rish, filter qilish, approve/reject qilish

**Yaratish buyrug'i**:
```bash
php artisan make:filament-resource BotVacancy --generate
```

**Kerakli funksiyalar**:
- Table columns: ID, Title, Company, Source, Status, Region, Clicks, Created At
- Filters: Status (pending/approved/rejected/published), Source, Region
- Actions: Approve, Reject, View Details
- Bulk Actions: Bulk Approve, Bulk Reject
- Relations: ChannelPosts, ActionLogs
- Custom page: Statistics Dashboard

**Fayllar**:
- `app/Filament/Resources/BotVacancyResource.php`
- `app/Filament/Resources/BotVacancyResource/Pages/ListBotVacancies.php`
- `app/Filament/Resources/BotVacancyResource/Pages/EditBotVacancy.php`
- `app/Filament/Resources/BotVacancyResource/Pages/ViewBotVacancy.php`

### 1.2. ChannelResource yaratish

**Maqsad**: Telegram kanallarni boshqarish (qo'shish, tahrirlash, o'chirish)

**Yaratish buyrug'i**:
```bash
php artisan make:filament-resource Channel --generate
```

**Kerakli funksiyalar**:
- Table columns: ID, Name, Type, Telegram Chat ID, Region, Active Status, Posts Count
- Filters: Type (management/main/region), Active Status, Region
- Form fields: Name, Type, Telegram Chat ID, Region SOATO, Username, Description, Is Active
- Actions: Activate/Deactivate, Test Channel Connection
- Custom action: Get Channel Info from Telegram

**Fayllar**:
- `app/Filament/Resources/ChannelResource.php`
- `app/Filament/Resources/ChannelResource/Pages/ListChannels.php`
- `app/Filament/Resources/ChannelResource/Pages/CreateChannel.php`
- `app/Filament/Resources/ChannelResource/Pages/EditChannel.php`

### 1.3. VacancyClickResource yaratish (opsional)

**Maqsad**: Click statistikalarini ko'rish

**Yaratish buyrug'i**:
```bash
php artisan make:filament-resource VacancyClick --generate
```

**Kerakli funksiyalar**:
- Table columns: ID, Vacancy Title, Channel Name, IP Address, User Agent, Clicked At
- Filters: Date Range, Channel, Vacancy
- Read-only resource (no create/edit)

### 1.4. Dashboard Widgets yaratish

**Widget 1: StatsOverview** - Umumiy statistika
```bash
php artisan make:filament-widget StatsOverview --resource=BotVacancyResource
```

Kerakli ma'lumotlar:
- Jami vakansiyalar
- Pending vakansiyalar
- Bugun e'lon qilingan vakansiyalar
- Jami clicklar (bugun/hafta/oy)
- Aktiv kanallar soni

**Widget 2: VacancyClicksChart** - Clicklar grafigi
```bash
php artisan make:filament-widget VacancyClicksChart --chart
```

**Widget 3: TopVacanciesTable** - Eng ko'p bosilgan vakansiyalar
```bash
php artisan make:filament-widget TopVacanciesTable --table
```

---

## 2. ARTISAN COMMANDS YARATISH

### 2.1. Telegram Webhook Commands

**File**: `app/Console/Commands/TelegramSetWebhook.php`

**Yaratish**:
```bash
php artisan make:command TelegramSetWebhook
```

**Signature**: `telegram:set-webhook`

**Funksiya**: Telegram webhook ni o'rnatish

**Kod struktura**:
```php
public function handle()
{
    $telegramService = app(TelegramBotService::class);
    $webhookUrl = route('telegram.webhook');

    $result = $telegramService->setWebhook($webhookUrl);

    if ($result) {
        $this->info("Webhook o'rnatildi: {$webhookUrl}");
    } else {
        $this->error("Webhook o'rnatishda xatolik!");
    }
}
```

### 2.2. Telegram Get Webhook Info

**File**: `app/Console/Commands/TelegramGetWebhook.php`

**Signature**: `telegram:get-webhook`

**Funksiya**: Webhook ma'lumotlarini ko'rish

### 2.3. Telegram Bot Info

**File**: `app/Console/Commands/TelegramBotInfo.php`

**Signature**: `telegram:bot-info`

**Funksiya**: Bot haqida ma'lumot olish (username, name, etc)

### 2.4. Create Regional Channels

**File**: `app/Console/Commands/CreateRegionalChannels.php`

**Signature**: `channel:create-regions`

**Funksiya**: Viloyatlar uchun avtomatik Channel record yaratish (database da)

**Kod struktura**:
```php
public function handle()
{
    $regions = [
        ['name' => 'Toshkent viloyati', 'soato' => '1700000000'],
        ['name' => 'Andijon viloyati', 'soato' => '0300000000'],
        ['name' => 'Farg\'ona viloyati', 'soato' => '0600000000'],
        ['name' => 'Namangan viloyati', 'soato' => '1000000000'],
        // ... qolgan viloyatlar
    ];

    foreach ($regions as $region) {
        Channel::updateOrCreate(
            ['region_soato' => $region['soato']],
            [
                'name' => $region['name'] . ' - Vakansiyalar',
                'type' => 'region',
                'is_active' => false, // Admin qo'lda telegram_chat_id qo'shgandan keyin activate qiladi
            ]
        );
    }
}
```

---

## 3. DATABASE VA MIGRATION ISHGA TUSHIRISH

### 3.1. Database yaratish

PostgreSQL da yangi database yaratish:

**Variant 1: psql orqali**
```bash
psql -U postgres
CREATE DATABASE telegram_bot_channels;
\q
```

**Variant 2: phpPgAdmin orqali**
- Browser da phpPgAdmin ochish
- Create database: `telegram_bot_channels`

### 3.2. .env faylni to'ldirish

`.env` faylda quyidagi parametrlarni to'ldirish kerak:

```env
# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=telegram_bot_channels
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Telegram Bot
TELEGRAM_BOT_TOKEN=your_bot_token_from_botfather
TELEGRAM_WEBHOOK_URL=https://your-domain.com/api/telegram/webhook
TELEGRAM_MANAGEMENT_CHANNEL_ID=-100xxxxxxxxxx
TELEGRAM_MAIN_CHANNEL_ID=-100xxxxxxxxxx
TELEGRAM_ADMIN_IDS=123456789,987654321

# Webhook Security
OSON_ISH_WEBHOOK_SECRET=your_secret_key_here

# Tracking
TRACKING_BASE_URL=https://your-domain.com
CLICK_DEDUPLICATION_ENABLED=true
CLICK_RATE_LIMIT_ENABLED=true
CLICK_RATE_LIMIT_MAX_ATTEMPTS=10
CLICK_RATE_LIMIT_DECAY_MINUTES=1
```

### 3.3. Migration ishga tushirish

```bash
# Migrationlarni ishga tushirish
php artisan migrate

# Admin user yaratish
php artisan db:seed --class=AdminUserSeeder

# Yoki barcha seederlarni ishga tushirish
php artisan db:seed
```

---

## 4. TELEGRAM BOT SOZLASH

### 4.1. Bot yaratish (BotFather)

1. Telegram da `@BotFather` ni ochish
2. `/newbot` buyrug'ini yuborish
3. Bot nomini kiriting (masalan: "Oson Ish Channels Manager")
4. Bot username kiriting (masalan: "oson_ish_channels_bot")
5. Token olasiz: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`
6. `.env` ga `TELEGRAM_BOT_TOKEN` ga qo'yish

### 4.2. Kanallar yaratish va ID olish

**Management Channel:**
1. Telegram da yangi kanal yaratish: "Vakansiyalar - Tasdiqlash"
2. Kanalga botni admin qilib qo'shish
3. Channel ID olish:
   - Kanal postga biror narsa yozing
   - Postni forward qiling @userinfobot ga
   - Chat ID ni ko'rasiz: `-1001234567890`
4. `.env` ga `TELEGRAM_MANAGEMENT_CHANNEL_ID` ga qo'yish

**Main Channel:**
1. Yangi kanal yaratish: "Oson Ish - Barcha Vakansiyalar"
2. Botni admin qilib qo'shish
3. ID olish (yuqoridagi usulda)
4. `.env` ga `TELEGRAM_MAIN_CHANNEL_ID` ga qo'yish

**Regional Channels:**
1. Har bir viloyat uchun kanal yaratish
2. Database da `channels` jadvaliga qo'lda qo'shish yoki Filament admin panel orqali

### 4.3. Webhook o'rnatish

```bash
php artisan telegram:set-webhook
```

Yoki qo'lda:
```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
  -d "url=https://your-domain.com/api/telegram/webhook"
```

Webhook tekshirish:
```bash
php artisan telegram:get-webhook
```

---

## 5. OSON-ISH-API INTEGRATSIYA

### 5.1. Webhook URL berish

Oson-ish-api loyihasida quyidagi URL ni sozlash kerak:

**Webhook URL**: `https://your-telegram-bot-domain.com/api/vacancies`

**Headers**:
```
Content-Type: application/json
X-Webhook-Secret: your_secret_key_here
```

### 5.2. Oson-ish-api da VacancyObserver yangilash

`app/Observers/VacancyObserver.php` faylda `updated` metodiga webhook qo'shish:

```php
public function updated(Vacancy $vacancy)
{
    // Agar status 2 (tasdiqlangan) ga o'zgargan bo'lsa
    if ($vacancy->isDirty('status') && $vacancy->status == Vacancy::STATUS_APPROVED) {
        // Telegram bot ga webhook yuborish
        dispatch(new SendVacancyToTelegramBot($vacancy));
    }
}
```

**Job yaratish**: `app/Jobs/SendVacancyToTelegramBot.php`

```php
public function handle()
{
    $webhookUrl = config('services.telegram_bot.webhook_url');
    $secret = config('services.telegram_bot.webhook_secret');

    Http::withHeaders([
        'X-Webhook-Secret' => $secret,
    ])->post($webhookUrl, [
        'vacancy_id' => $this->vacancy->id,
        'source' => 'oson-ish',
        'title' => $this->vacancy->name,
        'company_name' => $this->vacancy->filial->company->name ?? null,
        // ... qolgan ma'lumotlar
    ]);
}
```

---

## 6. QUEUE WORKER ISHGA TUSHIRISH

### 6.1. Redis ishga tushirish

```bash
# OSPanel da Redis ishlatilsa
# Redis service ni ishga tushirish (OSPanel modules)
```

### 6.2. Queue Worker

**Development muhitida:**
```bash
php artisan queue:work redis --queue=click-tracking,default --tries=3
```

**Production muhitida (Supervisor):**

`/etc/supervisor/conf.d/telegram-bot-worker.conf` yaratish:
```ini
[program:telegram-bot-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/worker.log
stopwaitsecs=3600
```

---

## 7. TESTING (OXIRIDA BIRGALIKDA)

### 7.1. Manual Testing Steps

1. **Database va Migration test**:
   ```bash
   php artisan migrate:fresh --seed
   ```

2. **Admin Panel kirish**:
   - URL: `http://localhost/admin` yoki `https://your-domain.com/admin`
   - Email: `admin@telegram-bot.local`
   - Password: `password`

3. **Telegram Bot test**:
   - Telegram da botni `/start` buyrug'i bilan boshlash
   - Management kanalga test vakansiya yuborish

4. **Webhook test (Postman yoki curl)**:
   ```bash
   curl -X POST http://localhost/api/vacancies \
     -H "Content-Type: application/json" \
     -H "X-Webhook-Secret: your_secret" \
     -d '{
       "vacancy_id": 123,
       "source": "oson-ish",
       "title": "PHP Developer",
       "company_name": "Test Company",
       "region_soato": "1700000000",
       "salary_min": 5000000,
       "salary_max": 8000000,
       "url": "https://oson-ish.uz/vacancy/123"
     }'
   ```

5. **Click Tracking test**:
   - Management kanalda vakansiyani approve qilish
   - Main kanalda tracking linkni bosish
   - Admin panelda click statistikani ko'rish

6. **Queue test**:
   ```bash
   # Queue worker ishga tushirish
   php artisan queue:work

   # Boshqa terminalda click yuborish va queue da job paydo bo'lishini kuzatish
   php artisan queue:listen --verbose
   ```

### 7.2. Automated Testing (agar kerak bo'lsa)

**Feature tests yaratish**:
```bash
php artisan make:test VacancyWebhookTest
php artisan make:test TelegramWebhookTest
php artisan make:test ClickTrackingTest
```

**Testlarni ishga tushirish**:
```bash
php artisan test
```

---

## 8. QOLGAN KICHIK ISHLAR

### 8.1. Logging sozlash

`config/logging.php` da telegram va webhook uchun maxsus channel qo'shish:

```php
'telegram' => [
    'driver' => 'daily',
    'path' => storage_path('logs/telegram.log'),
    'level' => 'debug',
    'days' => 14,
],
'webhook' => [
    'driver' => 'daily',
    'path' => storage_path('logs/webhook.log'),
    'level' => 'info',
    'days' => 30,
],
```

### 8.2. Rate Limiting sozlash

`app/Http/Kernel.php` da API routes uchun rate limit:

```php
'api' => [
    'throttle:60,1',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

### 8.3. CORS sozlash (agar frontend bo'lsa)

```bash
php artisan config:publish cors
```

### 8.4. Scheduler sozlash (agar kerak bo'lsa)

`app/Console/Kernel.php` da:

```php
protected function schedule(Schedule $schedule)
{
    // Har kuni eski clicklarni tozalash (90 kundan eski)
    $schedule->command('clicks:cleanup --days=90')->daily();

    // Har soatda statistikani yangilash
    $schedule->command('statistics:refresh')->hourly();
}
```

Cron sozlash:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 9. PRODUCTION DEPLOYMENT

### 9.1. Optimizatsiya

```bash
# Config cache
php artisan config:cache

# Route cache
php artisan route:cache

# View cache
php artisan view:cache

# Autoload optimization
composer install --optimize-autoloader --no-dev
```

### 9.2. .env Production Settings

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_LEVEL=error
```

### 9.3. SSL Certificate (HTTPS)

Telegram webhook HTTPS talab qiladi. Let's Encrypt yoki boshqa SSL sertifikat olish.

---

## 10. XULOSA

**To'liq tayyor bo'lgan qismlar:**
- ✅ Laravel proyekt strukturasi
- ✅ Database migrations (6 ta jadval)
- ✅ Eloquent Models (5 ta model)
- ✅ Services (3 ta: TelegramBotService, VacancyPublisher, TrackingService)
- ✅ Controllers (3 ta: VacancyController, TelegramWebhookController, TrackingController)
- ✅ Queue Job (RecordVacancyClick)
- ✅ Routes (api.php, web.php)
- ✅ Config files (telegram.php, tracking.php)
- ✅ Admin User Seeder

**Qolgan ishlar:**
- ⏳ Filament Resources (BotVacancy, Channel)
- ⏳ Filament Dashboard Widgets
- ⏳ Artisan Commands (telegram:*, channel:*)
- ⏳ Database yaratish va migration
- ⏳ Telegram bot sozlash (BotFather, channels)
- ⏳ Webhook o'rnatish
- ⏳ Queue worker ishga tushirish
- ⏳ Testing

**Keyingi qadamlar:**
1. Filament Resources yaratish
2. Artisan Commands yozish
3. Database sozlash va migrate qilish
4. Telegram bot sozlash va kanallar yaratish
5. Barcha qismlarni test qilish

---

**Eslatma**: Barcha kerakli kodlar tayyor. Faqat qolgan resurslar va commandlarni yaratish kerak. Keyin database sozlash va test qilish qoladi.
