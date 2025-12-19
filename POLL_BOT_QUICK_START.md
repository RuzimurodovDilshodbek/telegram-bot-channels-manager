# So'rovnoma Bot - Tezkor Boshlash

## 🚀 5 Daqiqada Ishga Tushirish

### 1-qadam: Database migrationlarini ishga tushiring

```bash
php artisan migrate
```

### 2-qadam: .env faylida bot username ni o'zgartiring

`.env` faylida quyidagi qatorni toping va bot username ni o'zgartiring:

```env
TELEGRAM_POLL_BOT_USERNAME=sizning_bot_username
```

**Qanday topish?** @BotFather ga `/mybots` yuboring va bot username ni ko'ring (@ belgisisiz).

### 3-qadam: Webhookni o'rnating

Brauzerda quyidagi URL ni oching:

```
https://admin.ishchi-bozor.uz/api/poll-bot/webhook/set
```

Yoki curl orqali:

```bash
curl -X POST https://admin.ishchi-bozor.uz/api/poll-bot/webhook/set
```

### 4-qadam: Admin panelda so'rovnoma yarating

1. Admin panelga kiring: https://admin.ishchi-bozor.uz/admin
2. Chap menuda "So'rovnomalar" ni tanlang
3. "Yangi So'rovnoma" tugmasini bosing
4. Formani to'ldiring:
   - **Nomi**: Masalan "Eng yaxshi dasturchi"
   - **Boshlanish/Tugash vaqti**: Kerakli vaqtni tanlang
   - **Nomzodlar**: Kamida 2 ta nomzod qo'shing
5. "Saqlash" tugmasini bosing

### 5-qadam: Kanallarga chiqaring

1. So'rovnomalar ro'yxatidan so'rovnomangizni toping
2. "Kanallarga chiqarish" tugmasini bosing
3. Kanallarni tanlang
4. "Tasdiqlash" tugmasini bosing

**Tayyor!** Endi foydalanuvchilar kanal postidagi "🗳 Ovoz berish" tugmasini bosib ovoz bera oladilar.

---

## 📊 Natijalarni Ko'rish

1. So'rovnomalar ro'yxatidan so'rovnomangizni toping
2. "Natijalar" tugmasini bosing
3. Real-time natijalar va statistikani ko'ring!

---

## 🎯 Asosiy Funksiyalar

### Admin Panel Bo'limlari:

1. **So'rovnomalar** - So'rovnomalarni yaratish va boshqarish
2. **Ishtirokchilar** - Kim qatnashganini ko'rish
3. **Ovozlar** - Barcha ovozlarni ko'rish (kim kimga ovoz bergan)

### Foydalanuvchi Oqimi:

```
1. Kanal postidagi "Ovoz berish" tugmasini bosadi
   ↓
2. Botga o'tadi
   ↓
3. Telefon raqamni yuboradi (agar kerak bo'lsa)
   ↓
4. Kanallarga obuna bo'ladi (agar kerak bo'lsa)
   ↓
5. Nomzodni tanlaydi
   ↓
6. Tasdiqlaydi
   ↓
7. Ovoz qabul qilinadi! 🎉
```

---

## 🔧 Muammolarni Hal Qilish

### Bot javob bermayapti?

1. Webhookni tekshiring:
   ```
   https://admin.ishchi-bozor.uz/api/poll-bot/webhook/info
   ```

2. Loglarni ko'ring:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Kanal postlari yangilanmayapti?

1. Botning kanal adminligini tekshiring
2. Botga kanalda xabar yuborish huquqi bering

### Foydalanuvchi ovoz bera olmayapti?

1. So'rovnoma "Faol" holatda ekanligini tekshiring
2. So'rovnoma vaqti hali tugamagan ekanligini tekshiring

---

## 💡 Maslahatlar

1. **Test qiling**: Birinchi marta kichik so'rovnoma yarating va test qiling
2. **Telefon**: Agar ma'lumot to'plash kerak bo'lsa "Telefon raqam talab qilish" ni yoqing
3. **Kanallar**: Agar faqat obunachilarga ovoz berish imkonini bermoqchi bo'lsangiz, "Kanal obunasi talab qilish" ni yoqing
4. **Vaqt**: So'rovnoma tugash vaqtini diqqat bilan belgilang

---

## 📞 Yordam Kerakmi?

Batafsil ma'lumot uchun `POLL_BOT_SETUP.md` faylini o'qing.
