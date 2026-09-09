# SHEIN داخل وصلة (مثل Shipshin)

## ليش الموقع من المتصفح ما بيعرض SHEIN داخل الصفحة؟

SHEIN بيمنع `<iframe>` من مواقع ثانية (`frame-ancestors *.shein.com`).

**تطبيق Shipshin = تطبيق Android/iOS** فيه **WebView** — مو iframe. هاد الفرق.

---

## على الموبايل (الحل الصح — زي Shipshin)

1. ثبّتي Capacitor:
   ```bash
   npm install
   npx cap add android
   ```

2. في `android/app/src/main/java/.../MainActivity.java` سجّلي الإضافة:
   ```java
   registerPlugin(SheinBrowserPlugin.class);
   ```

3. عدّلي `capacitor.config.ts` → `server.url` لعنوان سيرفر وصلة.

4. ابني وشغّلي:
   ```bash
   npm run cap:sync
   npm run cap:open
   ```

5. من التطبيق: `/browse/shein` → **WebView كامل** + زر **Submit Link** أحمر.

---

## على المتصفح (ويب)

- `/browse/shein` يحاول iframe → إذا محجوب: زر **"ادخلي SHEIN"** يفتح الموقع **بنفس الشاشة** (مو نافذة جديدة).
- بعد التصفح: ارجعي لوصلة → **Submit Link** → الصقي الرابط.

---

## الملفات

| ملف | دور |
|-----|-----|
| `resources/views/storefront/shein-webview-shell.blade.php` | واجهة Shipshin للويب |
| `android/.../SheinWebViewActivity.java` | WebView داخل التطبيق |
| `android/.../SheinBrowserPlugin.java` | جسر Capacitor |
| `resources/js/capacitor/sheinBrowser.js` | استدعاء من Vue |
