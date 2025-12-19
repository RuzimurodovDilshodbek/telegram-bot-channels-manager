# So'rovnoma (Poll) Bot - O'rnatish va Ishlatish

Bu modul vakansiya botidan alohida, mustaqil so'rovnoma botini boshqaradi.

## 📋 Xususiyatlar

- ✅ Admin paneldan so'rovnoma yaratish
- ✅ Nomzodlar qo'shish (rasm, tavsif)
- ✅ Telefon raqam to'plash va tasdiqlash
- ✅ Majburiy kanalga obuna tekshirish
- ✅ ReCaptcha (insonligini tasdiqlash) - kelgusida qo'shiladi
- ✅ IP manzil va user agent tracking
- ✅ Real-time ovozlarni sanash va yangilash
- ✅ Kanallarga avtomatik post chiqarish
- ✅ Post ichidagi ovozlar sonini avtomatik yangilash
- ✅ Natijalarni ko'rish (admin panel)
- ✅ Takroriy ovoz berishni bloklash

## 🚀 O'rnatish

### 1. Database Migrationlarini ishga tushirish

```bash
php artisan migrate
```

Bu quyidagi jadvallarni yaratadi:
- `polls` - So'rovnomalar
- `poll_candidates` - Nomzodlar
- `poll_participants` - Ishtirokchilar (foydalanuvchilar)
- `poll_votes` - Ovozlar
- `poll_channel_posts` - Kanal postlari

### 2. .env faylini sozlash

`.env` faylingizda quyidagi qiymatlarni o'zgartiring:

```env
# Poll Bot Configuration
TELEGRAM_POLL_BOT_TOKEN=8587905046:AAElf_h51lxamgPDbswCYwF8Mu3SVOaqN98
TELEGRAM_POLL_BOT_USERNAME=sizning_bot_username
TELEGRAM_POLL_WEBHOOK_URL=https://admin.ishchi-bozor.uz/api/poll-bot/webhook
```

**Eslatma:** `TELEGRAM_POLL_BOT_USERNAME` ni @BotFather dan olingan username bilan almashtiring (@ belgisisiz).

### 3. Webhookni o'rnatish

Webhookni o'rnatish uchun quyidagi URL ga POST so'rov yuboring:

```bash
curl -X POST https://admin.ishchi-bozor.uz/api/poll-bot/webhook/set
```

Yoki brauzerda ochib tekshiring:
```
https://admin.ishchi-bozor.uz/api/poll-bot/webhook/info
```

### 4. File Storage sozlash

Rasmlar `storage/app/public` papkasida saqlanadi. Agar hali qilmagan bo'lsangiz, symbolic link yarating:

```bash
php artisan storage:link
```

## 📱 Ishlatish

### Admin Panelda So'rovnoma Yaratish

1. Admin panelga kiring: `https://admin.ishchi-bozor.uz/admin`
2. Chap menuda "So'rovnomalar" bo'limiga o'ting
3. "Yangi So'rovnoma" tugmasini bosing
4. Forma to'ldiring:

#### Asosiy ma'lumotlar:
- **Nomi**: So'rovnoma sarlavhasi
- **Tavsif**: Qo'shimcha ma'lumot (ixtiyoriy)
- **Rasm**: So'rovnoma uchun rasm (ixtiyoriy)

#### Vaqt sozlamalari:
- **Boshlanish vaqti**: So'rovnoma qachon boshlanadi
- **Tugash vaqti**: So'rovnoma qachon tugaydi

#### Nomzodlar:
- Kamida 2 ta nomzod qo'shing
- Har bir nomzod uchun:
  - Ism (majburiy)
  - Ma'lumot (ixtiyoriy)
  - Rasm (ixtiyoriy)
  - Tartib raqami

#### Sozlamalar:
- **So'rovnoma faol**: Yoqilgan bo'lsa foydalanuvchilar ovoz bera oladi
- **Telefon raqam talab qilish**: Foydalanuvchilardan telefon raqam so'raladi
- **Kanal obunasi talab qilish**: Foydalanuvchi ovoz berishdan oldin kanallarga obuna bo'lishi kerak
- **Majburiy kanallar**: Qaysi kanallarga obuna bo'lish kerakligi
- **ReCaptcha yoqish**: Inson ekanligini tasdiqlash (kelgusida)

### Kanallarga Chiqarish

1. So'rovnoma yaratgandan so'ng, "So'rovnomalar" ro'yxatidan kerakli so'rovnomani toping
2. "Kanallarga chiqarish" tugmasini bosing
3. Bot avtomatik ravishda barcha faol kanallarga post chiqaradi

### Foydalanuvchi Jarayoni

1. Foydalanuvchi kanal postidagi "🗳 Ovoz berish" tugmasini bosadi
2. Botga o'tadi va `/start poll_123` komandasi avtomatik yuboriladi
3. Agar telefon raqam kerak bo'lsa, telefon raqamni yuboradi
4. Agar kanal obunasi kerak bo'lsa:
   - Majburiy kanallarga obuna bo'ladi
   - "✅ Obunani tekshirish" tugmasini bosadi
5. Nomzodlardan birini tanlaydi
6. Tasdiqlash tugmasini bosadi
7. Ovoz qabul qilinadi va kanal postlari yangilanadi

## 📊 Natijalarni Ko'rish

1. Admin panelda "So'rovnomalar" ro'yxatiga o'ting
2. Kerakli so'rovnomaning "Natijalar" tugmasini bosing
3. Ko'rsatiladigan ma'lumotlar:
   - Jami ovozlar soni
   - Ishtirokchilar soni
   - Har bir nomzodning ovozlari va foizi
   - So'nggi ovozlar ro'yxati (IP manzil, telefon, vaqt)

## 🔧 Texnik Ma'lumotlar

### Arxitektura

```
┌─────────────────┐
│  Admin Panel    │  (Filament)
│  - Polls CRUD   │
│  - Results View │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Poll Models    │
│  - Poll         │
│  - Candidate    │
│  - Participant  │
│  - Vote         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ PollBotService  │
│  - Webhook      │
│  - Voting Logic │
│  - Channel Post │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Telegram API   │
│  - Send Message │
│  - Edit Post    │
│  - Check Sub    │
└─────────────────┘
```

### Database Schema

```sql
polls
  - id
  - title
  - description
  - image
  - start_date / end_date
  - is_active
  - require_phone / require_subscription / enable_recaptcha
  - required_channels (JSON)
  - total_votes

poll_candidates
  - id
  - poll_id (FK)
  - name, description, photo
  - vote_count
  - order, is_active

poll_participants
  - id
  - poll_id (FK)
  - chat_id, first_name, last_name, username
  - phone, ip_address
  - phone_verified, subscription_verified, recaptcha_verified
  - verified_at

poll_votes
  - id
  - poll_id (FK)
  - poll_candidate_id (FK)
  - poll_participant_id (FK)
  - chat_id, ip_address, user_agent
  - voted_at

poll_channel_posts
  - id
  - poll_id (FK)
  - channel_id, message_id
  - posted_at, last_updated_at
  - update_count
```

### API Endpoints

```
POST   /api/poll-bot/webhook           - Telegram webhook
GET    /api/poll-bot/webhook/info      - Webhook info
POST   /api/poll-bot/webhook/set       - Set webhook
POST   /api/poll-bot/webhook/remove    - Remove webhook
```

### Filament Resources

```
/admin/polls                    - So'rovnomalar ro'yxati
/admin/polls/create            - Yangi so'rovnoma
/admin/polls/{id}/edit         - Tahrirlash
/admin/polls/{id}/results      - Natijalar
```

## 🔐 Xavfsizlik

1. **Takroriy ovoz berish oldini olish**:
   - `poll_votes` jadvalidagi `unique(['poll_id', 'chat_id'])` constraint
   - Kod darajasida qo'shimcha tekshiruv

2. **IP Tracking**:
   - Har bir ovoz IP manzil bilan saqlanadi
   - Shubhali harakatlarni aniqlash uchun

3. **User Agent Tracking**:
   - Botlarni aniqlash uchun

4. **Subscription Verification**:
   - Real-time kanal obunasini tekshirish
   - Telegram API orqali tasdiqlash

## 🐛 Muammolarni Hal Qilish

### Webhook ishlamayapti

1. Webhook URL ni tekshiring:
```bash
curl https://admin.ishchi-bozor.uz/api/poll-bot/webhook/info
```

2. SSL sertifikatini tekshiring (Telegram HTTPS talab qiladi)

3. Loglarni ko'ring:
```bash
tail -f storage/logs/laravel.log
```

### Kanal postlari yangilanmayapti

1. Botning kanal adminligini tekshiring
2. `poll_channel_posts` jadvalidagi ma'lumotlarni tekshiring
3. `PollBotService::updateChannelPosts()` metodini debug qiling

### Foydalanuvchi ovoz bera olmayapti

1. So'rovnoma faol ekanligini tekshiring (`is_active = true`)
2. So'rovnoma vaqti ichida ekanligini tekshiring
3. Foydalanuvchi allaqachon ovoz bergan bo'lishi mumkin

## 📞 Yordam

Muammolar yoki savollar bo'lsa:
1. Loglarni tekshiring: `storage/logs/laravel.log`
2. Database ma'lumotlarini tekshiring
3. Telegram Bot API loglarini ko'ring

## 🎯 Kelajakdagi Yaxshilanishlar

- [ ] ReCaptcha integration
- [ ] Export results to Excel/PDF
- [ ] Multiple poll types (yes/no, rating, etc.)
- [ ] Scheduled poll publishing
- [ ] Anonymous voting option
- [ ] Poll templates
- [ ] Advanced analytics
- [ ] Multi-language support
- [ ] Webhook retry mechanism
- [ ] Rate limiting for voting

## 📝 Eslatmalar

1. Bot tokeni va webhook URL xavfsiz saqlang
2. Database backup oling (ovozlar yo'qolmasligi uchun)
3. File storage hajmini monitoring qiling (rasmlar uchun)
4. Loglarni muntazam tozalang
5. SSL sertifikatini yangilab turing
