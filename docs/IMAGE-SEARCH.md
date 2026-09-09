# البحث بالصورة — Wasla Visual Search

## ماذا أضفنا؟

في صفحة المتجر بجانب البحث النصي في أيقونة **كاميرا**:
1. ارفعي صورة منتج
2. النظام يرجع المنتجات المشابهة مرتبة: مطابق ≈ ثم قريب جداً ثم مشابه

## التشغيل السريع (PHP — الافتراضي)

Python غير مطلوب لهذه المرحلة.

```bash
php artisan migrate
php artisan products:index-visual
npm run build
```

ثم افتحي المتجر واضغطي أيقونة الكاميرا.

### إعدادات `.env`

```
IMAGE_SEARCH_DRIVER=local
IMAGE_SEARCH_LIMIT=24
IMAGE_SEARCH_MIN_SCORE=0.45
```

- `local` = محرك PHP (شكل الصورة + الألوان)
- أعدي تشغيل `products:index-visual` بعد إضافة منتجات/صور جديدة

### API

`POST /api/v1/products/search-by-image`  
form-data: `image` (ملف صورة)، اختياري `limit`

## ترقية لاحقاً (CLIP — أدق)

إذا ثبّتي Python:

```bash
cd image-search-service
pip install -r requirements.txt
uvicorn main:app --host 127.0.0.1 --port 3010
```

في `.env`:

```
IMAGE_SEARCH_DRIVER=clip
IMAGE_SEARCH_CLIP_URL=http://127.0.0.1:3010
```

ثم ابنِ فهرس CLIP عبر `POST /index` على الخدمة (قائمة مسارات الصور).

## ملاحظات جودة

| المحرك | الجودة | المتطلبات |
|--------|--------|-----------|
| local (الحالي) | جيدة للون/الشكل العام | PHP GD فقط |
| clip | أفضل للملابس/الاستايل | Python + torch |

النتائج مرتبة تنازلياً حسب درجة التشابه.
