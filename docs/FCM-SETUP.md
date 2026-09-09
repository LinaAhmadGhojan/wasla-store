# إعداد Firebase Cloud Messaging (FCM) لتطبيق Flutter

الـ backend جاهز. الإشعارات تُرسل تلقائياً عند تحديث الطلب **بعد** ما تكمّل الخطوات تحت.

---

## المطلوب منك (مرة واحدة)

### 1) مشروع Firebase
1. ادخل [Firebase Console](https://console.firebase.google.com/)
2. أنشئ مشروع (أو استخدم موجود) باسم مثلاً **Wasla**
3. أضف تطبيق **Android** و/أو **iOS** (نفس package/bundle تبع Flutter)

### 2) ملف Service Account (للسيرفر)
1. Project settings → **Service accounts**
2. **Generate new private key** → يتنزّل ملف JSON
3. انسخه إلى المشروع:
   ```
   storage/app/firebase-credentials.json
   ```
   (الملف **سري** — لا ترفعه على Git)

### 3) ضبط `.env`
```env
FIREBASE_ENABLED=true
FIREBASE_PROJECT_ID=your-firebase-project-id
FIREBASE_CREDENTIALS=E:\erp\wasla-store\storage\app\firebase-credentials.json
FIREBASE_ANDROID_CHANNEL=wasla_orders
```

`FIREBASE_PROJECT_ID` = قيمة `project_id` داخل ملف الـ JSON.

بعد التعديل:
```bash
php artisan config:clear
```

تحقق:
```bash
# من المتصفح أو Postman بدون توكن
GET /api/v1/push/status
# → { "fcm_ready": true, "project_id": "..." }
```

### 4) تطبيق Flutter (لما تبلّش التطبيق)
عند تسجيل الدخول احفظ توكن الجهاز:

```http
POST /api/v1/device-tokens
Authorization: Bearer {sanctum_token}
Content-Type: application/json

{
  "token": "<FCM_DEVICE_TOKEN>",
  "platform": "android",
  "device_name": "Pixel 7",
  "app": "customer"
}
```

عند تسجيل الخروج:
```http
DELETE /api/v1/device-tokens
{ "token": "<FCM_DEVICE_TOKEN>" }
```

في Flutter عادةً: حزمة `firebase_messaging` + قناة Android اسمها `wasla_orders`.

### 5) تجربة من السيرفر
بعد ما التطبيق يسجّل توكن لمستخدم:
```bash
php artisan fcm:test {USER_ID}
```

---

## شو بيصير تلقائياً؟
عند طلب جديد أو تغيّر حالة (تجهيز / شحن / خرج للتسليم / مستلم…):

1. إشعار في قاعدة البيانات → `GET /api/v1/notifications`
2. **Push FCM** للجهاز (إذا مفعّل وفيه توكن)
3. واتساب (إذا الـ gateway شغّال)

---

## ملاحظات
- بدون ملف credentials أو `FIREBASE_ENABLED=false` → النظام يكمّل بدون خطأ (DB + واتساب فقط).
- توكنات باطلة تُحذف تلقائياً.
- ادفع لاحقاً: نفس الآلية لسائق/أدمن عبر `"app": "driver"` أو `"admin"`.
