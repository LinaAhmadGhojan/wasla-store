<p align="center">
  <img src="public/brand/wasla-id-horizontal.png" alt="Wasla — وصلة" width="360" />
</p>

<p align="center">
  <img src="public/brand/wasla-id-mark.png" alt="Wasla mark" width="80" />
</p>

<h1 align="center">Wasla Store — وصلة</h1>

<p align="center">
  <strong>سوق إلكتروني سوري (Marketplace)</strong><br/>
  منتجات محلية + تسوق من متاجر عالمية · واجهة زبون عربية · لوحة إدارة · تتبع توصيل
</p>

<p align="center">
  <code>Laravel 10</code> · <code>PHP 8.1+</code> · <code>Vue 3</code> · <code>Vite 5</code> · <code>Sanctum</code> · <code>MySQL</code> · <code>Capacitor 6</code>
</p>

---

## جدول المحتويات

1. [عن المشروع](#1-عن-المشروع)
2. [المميزات بالتفصيل](#2-المميزات-بالتفصيل)
3. [التقنيات](#3-التقنيات)
4. [هيكل المجلدات](#4-هيكل-المجلدات)
5. [المتطلبات](#5-المتطلبات)
6. [التثبيت والتشغيل](#6-التثبيت-والتشغيل)
7. [متغيرات البيئة](#7-متغيرات-البيئة)
8. [الخدمات الاختيارية](#8-الخدمات-الاختيارية)
9. [مسارات الواجهة (Web)](#9-مسارات-الواجهة-web)
10. [واجهة برمجة التطبيقات (API)](#10-واجهة-برمجة-التطبيقات-api)
11. [لوحة الإدارة](#11-لوحة-الإدارة)
12. [تدفق الطلبات والتوصيل](#12-تدفق-الطلبات-والتوصيل)
13. [الدفع والعملة](#13-الدفع-والعملة)
14. [الهوية البصرية](#14-الهوية-البصرية)
15. [حسابات التجربة بعد الـ Seeder](#15-حسابات-التجربة-بعد-الـ-seeder)
16. [أوامر Artisan المفيدة](#16-أوامر-artisan-المفيدة)
17. [التوثيق داخل المشروع](#17-التوثيق-داخل-المشروع)
18. [ملاحظات مهمة](#18-ملاحظات-مهمة)

---

## 1. عن المشروع

**وصلة (Wasla)** منصة تجارة إلكترونية مخصصة للسوق السوري:

| الجانب | الوصف |
|--------|--------|
| للزبون | تصفح، بحث، مقارنة، سلة، دفع محلي، تتبع، إرجاع، تقييم |
| للمتاجر | منتجات محلية، بائعين (Vendors)، علامات |
| عالمي | شراء من أي مكان + تصفح منصات (مثل SHEIN) عبر browse |
| للإدارة | طلبات موحّدة، شراء خارجي، واتساب، أسعار صرف، إعدادات |
| للجوال | غلاف Capacitor Android (`com.wasla.store`) + خطط تطبيقات Flutter في `docs/` |

اللون الأساسي للهوية: **`#006871`** / **`#1c7282`**.

---

## 2. المميزات بالتفصيل

### 2.1 الحساب والمصادقة

- تسجيل بريد + **OTP عبر الإيميل** (التحقق إلزامي افتراضياً)
- تسجيل دخول / نسيان كلمة المرور / إعادة التعيين
- ملف شخصي، صورة، تفضيلات، تغيير كلمة المرور
- تسجيل الدخول الاجتماعي (Google/Apple) **موقوف افتراضياً** (`SOCIAL_LOGIN_ENABLED=false`)
- API محمي بـ **Laravel Sanctum** (Bearer Token)

### 2.2 العناوين والخريطة

- عدة عناوين (بيت / عمل / آخر)
- إحداثيات GPS + ملاحظات للمندوب
- اختيار الموقع على الخريطة (**Leaflet**)
- تعيين عنوان افتراضي

### 2.3 الكتالوج والبحث

- منتجات، تصنيفات، علامات، متاجر
- فلاتر وfacets، اقتراحات بحث، تريند
- مقارنة منتجات (`/compare`)
- مجموعات (Collections)
- منتجات مشابهة + عدّاد مشاهدات
- **بحث بالصورة** (محلي GD أو CLIP)
- دليل مقاسات للأزياء + Find My Size

### 2.4 المفضلة والمتاجر والأسئلة

- قوائم مفضلة متعددة (Wishlist lists)
- متابعة المتاجر + تغذية المتجر
- أسئلة وأجوبة على صفحة المنتج

### 2.5 السلة والكوبونات

- زيادة / تخفيض الكمية، حذف، تغيير الـ Variant
- **Save for Later** (حفظ للاحقاً) للمستخدم والزائر
- تطبيق / إزالة كوبون
- تنبيهات: نفاد، قرب النفاد، تغيّر السعر
- سلة زائر في `localStorage` ثم مزامنة عند الدخول

### 2.6 الدفع (Checkout)

- اختيار عنوان → خريطة → دفع
- طرق الشحن (عادي / سريع)
- كوبون، محفظة (رصيد متجر)، نقاط
- ملاحظات الطلب
- **هدية:** تغليف، رسالة، اسم/هاتف/عنوان المستلم
- ملخص رسوم كامل

### 2.7 الطلبات

- قائمة الطلبات بفلاتر الحالات (Pending, Processing, Shipped, Delivered, Cancelled, Returned, Refunded)
- تفاصيل + تتبع Timeline موسّع
- رقم تتبع، مندوب، ETA شحن
- فاتورة قابلة للطباعة/التحميل
- **Buy Again / Reorder** → إضافة للسلة
- إلغاء حسب الحالة + سبب إلغاء  
  - Pending: دائماً · Processing: ممكن · بعد الشحن: ممنوع

### 2.8 الإرجاع والاستبدال والاسترداد

- Return / Exchange مع: منتج، كمية، سبب، وصف، صور
- استبدال مقاس (مثلاً M → L) عبر variants
- جدولة استلام (Schedule Pickup) + عنوان
- تتبع طلب الإرجاع
- مسار Refund: Requested → Approved → Processing → Refunded (مبلغ / طريقة / تاريخ)

### 2.9 التقييمات

- بعد الاستلام: نجوم + تعليق + صور + فيديو
- **Fit Feedback:** Too Small / Perfect / Too Large
- مكافأة رصيد متجر عند التقييم

### 2.10 الإشعارات و FCM

- إشعارات داخل التطبيق
- Device tokens لـ Firebase Cloud Messaging
- تنبيهات أدمن للطلبات الجديدة
- أمر اختبار: `php artisan fcm:test {userId}`

### 2.11 التسوق الخارجي و SHEIN

- «اشتري من أي مكان» (رابط منتج خارجي → عرض سعر → موافقة)
- Browse منصات (mock / SearchAPI / OTAPI)
- غلاف SHEIN داخل التطبيق (Capacitor WebView)
- دفعات شراء (Procurement batches) من لوحة الإدارة

### 2.12 لوحة الإدارة

مستخدمون، منتجات، تصنيفات، بائعون، علامات، سمات، طلبات موحّدة، تأكيد دفع، مندوب، لوجستي، OTP تسليم، طلبات شراء خارجية، واتساب، سعر الصرف، إعدادات، منصات خارجية.

---

## 3. التقنيات

| الطبقة | التقنية |
|--------|---------|
| Backend | Laravel **10.x** (`laravel/framework ^10.10`) |
| PHP | `^8.1` |
| Auth API | Laravel Sanctum `^3.3` |
| HTTP | Guzzle `^7.2` |
| Frontend | Vue **3.4**, Vite **5**, Axios |
| خرائط | Leaflet `^1.9.4` |
| قاعدة البيانات | MySQL / MariaDB |
| Push | Firebase FCM (`config/firebase.php`) |
| بحث صورة | PHP GD محلي **أو** Python CLIP (`image-search-service/`) |
| واتساب | Node gateway (`whatsapp-gateway/`) |
| أندرويد | Capacitor 6 |

**سكربتات npm:**

```bash
npm run dev        # Vite للتطوير
npm run build      # بناء الإنتاج
npm run cap:sync   # build + مزامنة Android
npm run cap:open   # فتح Android Studio
```

---

## 4. هيكل المجلدات

```
wasla-store/
├── app/
│   ├── Actions/                 # Catalog + ExternalShopping
│   ├── Console/Commands/        # products:*, fcm:test
│   ├── Http/Controllers/
│   │   ├── Admin/               # لوحة الإدارة
│   │   ├── Api/ + Api/V1/       # REST API
│   │   └── StorefrontController.php
│   ├── Models/
│   ├── Notifications/
│   ├── Policies/
│   ├── Services/                # Auth, Catalog, Commerce, Delivery, Push, Reviews…
│   └── Providers/
├── resources/
│   ├── js/
│   │   ├── app.js               # تركيب صفحات Vue
│   │   ├── components/storefront/
│   │   └── storefront/          # api.js, guestCart.js, store.js…
│   ├── css/app.css
│   └── views/storefront|admin|layouts/
├── routes/web.php, api.php
├── config/                      # payments, shipping, catalog, firebase, …
├── database/migrations|seeders/
├── docs/                        # توثيق تفصيلي
├── public/brand/                # شعارات وأيقونات دفع
├── logo/                        # نسخ مصدر الشعارات
├── application/                 # لقطات تصاميم UI
├── image-search-service/        # خدمة CLIP
├── whatsapp-gateway/            # بوابة واتساب
├── android/                     # مشروع Capacitor
├── scripts/
├── .env.example
├── composer.json
├── package.json
├── vite.config.js
├── capacitor.config.ts
└── README.md
```

---

## 5. المتطلبات

- PHP **8.1+** مع امتدادات Laravel المعتادة (pdo_mysql, mbstring, openssl, tokenizer, xml, ctypegd, fileinfo)
- Composer 2
- Node.js **18+** و npm
- MySQL 8 / MariaDB
- (اختياري) Python 3.10+ لخدمة CLIP
- (اختياري) Android Studio لبناء التطبيق

---

## 6. التثبيت والتشغيل

### 6.1 الأساسيات

```bash
# 1) تثبيت PHP
composer install

# 2) البيئة
cp .env.example .env
php artisan key:generate

# 3) عدّل قاعدة البيانات في .env ثم:
php artisan migrate

# 4) بيانات تجريبية (اختياري)
php artisan db:seed

# 5) رابط التخزين (صور/إيصالات)
php artisan storage:link

# 6) الواجهة
npm install
npm run build
# للتطوير مع Hot Reload:
# npm run dev

# 7) تشغيل السيرفر
php artisan serve
```

- الواجهة: [http://127.0.0.1:8000](http://127.0.0.1:8000)  
- الإدارة: [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin)

### 6.2 كوبون تجريبي (اختياري)

لا يوجد كوبون افتراضي في الـ Seeder. لإنشاء خصم 10%:

```bash
php artisan tinker
```

```php
\App\Models\Coupon::create([
  'code' => 'WASLA10',
  'name' => 'خصم وصلة 10%',
  'type' => 'percent',
  'value' => 10,
  'min_subtotal' => 50,
  'max_discount' => 500,
  'is_active' => true,
]);
```

معاينة: `GET /api/v1/coupons/preview?code=WASLA10&subtotal=200`

---

## 7. متغيرات البيئة

من `.env.example` والمجموعات المستخدمة في `config/`:

### تطبيق وقاعدة بيانات

| المتغير | الغرض |
|---------|--------|
| `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | إعدادات Laravel |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL |
| `CACHE_DRIVER`, `SESSION_DRIVER`, `QUEUE_CONNECTION`, `FILESYSTEM_DISK` | كاش/جلسة/طابور |
| `MAIL_*` | البريد (OTP التسجيل) |

### مصادقة

| المتغير | الافتراضي / ملاحظة |
|---------|---------------------|
| `SOCIAL_LOGIN_ENABLED` | `false` |
| `REQUIRE_EMAIL_VERIFICATION` | `true` |
| `GOOGLE_CLIENT_ID`, `APPLE_CLIENT_ID` | عند تفعيل السوشيال |
| `AUTH_OTP_TTL` | دقائق صلاحية OTP (مثلاً 10) |

### كتالوج خارجي

| المتغير | ملاحظة |
|---------|--------|
| `EXTERNAL_CATALOG_DRIVER` | `mock` \| `searchapi` \| `otapi` |
| `SEARCHAPI_KEY`, `SEARCHAPI_BASE_URL` | SearchAPI |
| `OTAPI_KEY` | OTAPI |

### بحث بالصورة

| المتغير | ملاحظة |
|---------|--------|
| `IMAGE_SEARCH_DRIVER` | `local` أو `clip` |
| `IMAGE_SEARCH_CLIP_URL` | مثلاً `http://127.0.0.1:3010` |
| `IMAGE_SEARCH_LIMIT`, `IMAGE_SEARCH_MIN_SCORE` | حدود النتائج |

### Firebase / FCM

| المتغير | ملاحظة |
|---------|--------|
| `FIREBASE_ENABLED` | `false` حتى تجهيز المفاتيح |
| `FIREBASE_PROJECT_ID` | |
| `FIREBASE_CREDENTIALS` | مسار JSON داخل التخزين |
| `FIREBASE_ANDROID_CHANNEL` | قناة أندرويد |

### دفع وشحن (غالباً في config وقد تحتاج إضافتها لـ `.env`)

راجع أيضاً: `config/payments.php`, `config/shipping.php`, `config/currency.php`

- حسابات: شام كاش / الهرم / الفؤاد  
- `SHIPPING_STANDARD_FEE`, `SHIPPING_EXPRESS_FEE`, `GIFT_WRAPPING_FEE`  
- سعر صرف AED → SYP

### واتساب

- `WHATSAPP_INCLUDE_PRODUCT_URL`
- وعنوان البوابة في إعدادات WhatsApp (`WHATSAPP_GATEWAY_URL` / token حسب التوثيق)

---

## 8. الخدمات الاختيارية

### 8.1 بحث بصري محلي

```bash
php artisan products:index-visual
```

بدون بايثون — يستخدم فهرس محلي (GD).

### 8.2 خدمة CLIP

```bash
cd image-search-service
pip install -r requirements.txt
# Windows: start.bat  أو:
uvicorn main:app --host 127.0.0.1 --port 3010
```

ثم في `.env`: `IMAGE_SEARCH_DRIVER=clip`

تفاصيل: [`docs/IMAGE-SEARCH.md`](docs/IMAGE-SEARCH.md)

### 8.3 بوابة واتساب

```bash
cd whatsapp-gateway
npm install
npm start
```

### 8.4 Firebase

راجع [`docs/FCM-SETUP.md`](docs/FCM-SETUP.md)  
ضع ملف الاعتمادات ثم `FIREBASE_ENABLED=true`.

### 8.5 تطبيق أندرويد (Capacitor)

```bash
npm run cap:sync
npm run cap:open
```

راجع [`docs/SHEIN-INSIDE-APP.md`](docs/SHEIN-INSIDE-APP.md)

---

## 9. مسارات الواجهة (Web)

| المسار | الوظيفة |
|--------|---------|
| `/`, `/shop` | الرئيسية / المتجر |
| `/product/{id}`, `/products/{id}` | صفحة المنتج |
| `/compare` | مقارنة |
| `/cart` | السلة |
| `/checkout` | إتمام الطلب |
| `/order-confirmation` | تأكيد الطلب |
| `/my-requests` | طلباتي |
| `/orders/{id}/track` | تتبع الطلب |
| `/orders/{id}/invoice` | الفاتورة |
| `/profile` | الملف الشخصي |
| `/addresses` | العناوين |
| `/favorites` | المفضلة |
| `/stores/{vendor}` | صفحة المتجر |
| `/login`, `/register` | دخول / تسجيل |
| `/forgot-password`, `/reset-password` | استعادة كلمة المرور |
| `/buy-from-anywhere` | اشتري من أي مكان |
| `/browse`, `/browse/{platform}` | تصفح منصات خارجية |
| `/browse/{platform}/product/{id}` | منتج خارجي |
| `/admin/*` | لوحة الإدارة |

صفحات Vue تُركَّب عبر عناصر مخصصة في Blade من `resources/js/app.js` (مثل `<wasla-cart>`, `<wasla-checkout>`, …).

---

## 10. واجهة برمجة التطبيقات (API)

Base للواجهة الأمامية: **`/api`** (Axios في `resources/js/storefront/api.js`).

### 10.1 مصادقة وحساب (`/api/auth/...`)

- `POST register` · `register/verify` · `register/resend-otp`
- `POST login` · `forgot-password` · `reset-password`
- `GET/PUT profile` · avatar · change-password · logout
- OTP / social (حسب الإعداد)

### 10.2 سلة وطلبات وعناوين (Sanctum)

| Method | Path |
|--------|------|
| GET/POST | `/cart`, `/cart/items` |
| PATCH/DELETE | `/cart/items/{id}` |
| POST | `/cart/items/{id}/save-for-later`, `.../move-to-cart` |
| GET/POST | `/orders` |
| GET | `/orders/{order}`, `.../tracking`, `.../invoice` |
| POST | `/orders/{order}/cancel`, `.../reorder`, `.../verify-otp`, `.../receipt` |
| CRUD | `/addresses` + تعيين افتراضي |

### 10.3 V1 (`/api/v1/...`)

| المجال | أمثلة |
|--------|--------|
| الرئيسية/كتالوج | `home`, `catalog`, `facets`, `suggestions`, `trending-searches`, `compare`, `collections` |
| كوبون/شحن | `coupons/preview`, `shipping-methods` |
| بحث صورة | `POST products/search-by-image` |
| مفضلة | `wishlist`, lists, move |
| متاجر | `stores/{vendor}/follow`, `store-follows` |
| أسئلة | `products/{product}/questions` |
| تقييمات | `products/{product}/reviews`, `POST reviews`, `orders/{order}/reviewable-items` |
| إرجاع | `return-exchange-requests`, `orders/{order}/return-exchange-items` |
| طلباتي | `my-orders` |
| إشعارات | `notifications`, `device-tokens`, `push/status` |
| Browse خارجي | `browse/{platform}/categories|products|...` |
| شراء خارجي | purchase-requests (حسب المسارات المفعّلة) |

حدود المعدل تقريباً: محلياً 600/دقيقة، وإلا 180/دقيقة (`RouteServiceProvider`).

---

## 11. لوحة الإدارة

المسار الأساسي: **`/admin`**

يشمل عادةً:

- لوحة مؤشرات
- مستخدمون + رصيد متجر
- منتجات (ألوان، نشر واتساب، سياسات إرجاع)
- تصنيفات، بائعون، علامات، سمات
- طلبات موحّدة + تفاصيل الطلب المحلي (حالة، دفع، مندوب، لوجستي، OTP، تسليم/فشل)
- طلبات الشراء الخارجية + دفعات التوريد
- منصات خارجية + سعر الصرف
- واتساب (اتصال / مجموعات / بث)
- إعدادات المتجر
- تنبيهات الطلبات

---

## 12. تدفق الطلبات والتوصيل

مسار الزبون التقريبي:

```
تم استلام الطلب → تم التأكيد → قيد التجهيز → تم التغليف
→ تم الشحن → خرج للتسليم → تم التسليم
```

حالات إضافية: ملغي، مرتجع، تعذّر التسليم.

خدمات مهمة:

- `app/Services/Delivery/OrderDeliveryService.php`
- أحداث: `delivery_events`
- توثيق: [`docs/ORDER-FLOW-REAL.md`](docs/ORDER-FLOW-REAL.md)، [`docs/DELIVERY-FLOW-AND-TESTS.md`](docs/DELIVERY-FLOW-AND-TESTS.md)

---

## 13. الدفع والعملة

### طرق الدفع (`config/payments.php`)

| المفتاح | الوصف |
|---------|--------|
| `sham_cash` | شام كاش (كود عملية) |
| `al_haram` | الهرم (وصل و/أو كود) |
| `fouad` | الفؤاد |
| `store_credit` | رصيد المحفظة |
| `cash_on_delivery` | قد يكون موقوفاً حسب الإعداد |

### العملة

- تسعير/توريد غالباً بـ **AED**
- عرض الزبون بـ **ل.س (SYP)** عبر خدمة الصرف (`CurrencyService` / `config/currency.php`)

### الشحن والهدايا (`config/shipping.php`)

- شحن عادي / سريع
- رسوم تغليف هدية
- عتبة قرب النفاد
- تحويل نقاط → ل.س

---

## 14. الهوية البصرية

### الشعارات (يُفضّل استخدامها من `public/brand/`)

| الملف | الاستخدام |
|--------|-----------|
| `public/brand/wasla-id-horizontal.png` | شعار أفقي |
| `public/brand/wasla-id-mark.png` | العلامة الدائرية/المارك |
| `public/brand/wasla-id-stacked.png` | شعار عمودي |
| `public/brand/wasla-id-store-mark.png` | علامة المتجر |

متغيرات: `-on-light`, `-on-dark`, `-white`, `-transparent`.

### أخرى

- أيقونات دفع: `public/brand/payments/`
- رسوم onboarding: `public/brand/illustrations/`
- نسخ مصدر: `logo/`
- لقطات تصاميم: `application/wasla-ui-*.png`, `wasla-shein-*.png`, …
- دليل الهوية: [`public/brand/README.md`](public/brand/README.md)

<p align="center">
  <img src="public/brand/wasla-id-stacked.png" alt="Wasla stacked" width="180" />
</p>

---

## 15. حسابات التجربة بعد الـ Seeder

بعد `php artisan db:seed` (حسب `WaslaSeeder` / أدوار النظام) غالباً:

| الدور | البريد التقريبي | كلمة المرور |
|-------|------------------|-------------|
| Admin | `admin@wasla.test` | `password` |
| Customer | `customer@wasla.test` | `password` |
| Seller | `seller@wasla.test` | `password` |
| Driver | `driver@wasla.test` | `password` |

تأكد من الملفات في `database/seeders/` إن تغيّرت البيانات.

الأدوار: **customer · seller · driver · admin**.

---

## 16. أوامر Artisan المفيدة

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve

php artisan products:index-visual      # فهرسة بحث الصورة المحلي
php artisan products:download-images
php artisan products:refresh-images
php artisan fcm:test {userId}         # اختبار إشعار FCM

php artisan route:list --path=api
php artisan route:list --path=admin
```

---

## 17. التوثيق داخل المشروع

| الملف | المحتوى |
|-------|---------|
| [`docs/FCM-SETUP.md`](docs/FCM-SETUP.md) | إعداد الإشعارات |
| [`docs/IMAGE-SEARCH.md`](docs/IMAGE-SEARCH.md) | البحث بالصورة |
| [`docs/SHEIN-INSIDE-APP.md`](docs/SHEIN-INSIDE-APP.md) | SHEIN داخل التطبيق |
| [`docs/ORDER-FLOW-REAL.md`](docs/ORDER-FLOW-REAL.md) | نموذج حالات الطلب |
| [`docs/DELIVERY-FLOW-AND-TESTS.md`](docs/DELIVERY-FLOW-AND-TESTS.md) | التوصيل والاختبارات |
| [`docs/quotation-flutter-apps.md`](docs/quotation-flutter-apps.md) | عرض تطبيقات Flutter |
| `docs/عرض-سعر-تطبيق-الزبون-حسب-التصاميم.*` | تسعير تطبيق الزبون |
| `docs/عرض-متطلبات-تطبيق-الزبون.*` | متطلبات تطبيق الزبون |
| `docs/عرض-متطلبات-تطبيق-التوصيل.*` | متطلبات تطبيق التوصيل |
| `docs/عرض-متطلبات-تطبيقات-وصلة.*` | متطلبات مجمّعة |

---

## 18. ملاحظات مهمة

1. **Laravel 10** وليس 11 — حسب `composer.json`.
2. واجهة الزبون **RTL عربية**؛ كثير من الصفحات Vue داخل Blade.
3. الدفع المحلي يعتمد رفع وصل / كود؛ التأكيد النهائي غالباً من الأدمن.
4. المنتجات الخارجية قد لا تدعم الإرجاع/التقييم بنفس مسار المنتجات المحلية.
5. تأكد من `php artisan storage:link` لعرض الصور والإيصالات.
6. في الإنتاج: عطّل `APP_DEBUG`، اضبط `APP_URL`، استخدم طابور حقيقي إن لزم، وفعّل HTTPS.
7. ملفات الأسرار (`.env`, Firebase JSON) **لا تُرفع** للمستودع.

---

## ترخيص

مشروع **وصلة** — حقوق الاستخدام حسب اتفاق المشروع.  
إطار العمل Laravel مرخّص تحت [MIT](https://opensource.org/licenses/MIT).

---

<p align="center">
  <img src="public/brand/wasla-id-mark.png" alt="Wasla" width="56" /><br/>
  <sub>Wasla Store — بني بحب للسوق السوري</sub>
</p>
