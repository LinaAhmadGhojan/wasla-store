<!DOCTYPE html>
<html lang="ar" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SHEIN — Wasla</title>
    @vite(['resources/js/shein-shop-shell.js'])
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; font-family: system-ui, sans-serif; background: #fff; }
        .shell { display: flex; flex-direction: column; height: 100%; height: 100dvh; }
        .bar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.5rem 0.75rem; background: #1c7282; height: 48px; flex-shrink: 0; color: #fff;
        }
        .bar a { color: #c9eef2; font-weight: 700; text-decoration: none; font-size: 0.85rem; }
        .bar .center { font-weight: 800; font-size: 0.95rem; }
        .cart-link { position: relative; }
        .cart-badge {
            position: absolute; top: -6px; right: -8px; background: #26b4c3; color: #fff;
            font-size: 0.65rem; font-weight: 800; min-width: 1.1rem; height: 1.1rem;
            border-radius: 999px; display: flex; align-items: center; justify-content: center;
        }
        .frame-wrap { flex: 1; position: relative; min-height: 0; background: #f5fbfc; }
        iframe { width: 100%; height: 100%; border: none; }
        .blocked {
            position: absolute; inset: 0; display: none; flex-direction: column;
            align-items: center; justify-content: center; padding: 1.5rem; text-align: center;
            background: #f5fbfc; color: #4d6b72; line-height: 1.65; gap: 1rem;
        }
        .blocked p { max-width: 340px; font-size: 0.88rem; }
        .enter-btn {
            background: #1c7282; color: #fff; border: none; padding: 0.9rem 1.6rem;
            border-radius: 999px; font-weight: 800; font-size: 0.95rem; cursor: pointer;
        }
        .submit-fab {
            position: fixed; bottom: calc(1rem + env(safe-area-inset-bottom)); left: 50%;
            transform: translateX(-50%); background: #1c7282; color: #fff; border: none;
            padding: 0.9rem 2rem; border-radius: 999px; font-weight: 800; font-size: 0.95rem;
            box-shadow: 0 6px 24px rgba(15, 90, 107, 0.45); cursor: pointer; z-index: 100;
        }
        .sheet {
            position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 200;
            display: none; align-items: flex-end;
        }
        .sheet.open { display: flex; }
        .sheet-panel {
            background: #fff; width: 100%; border-radius: 1.1rem 1.1rem 0 0;
            padding: 1.1rem 1.1rem calc(1.1rem + env(safe-area-inset-bottom));
        }
        .sheet-panel h3 { margin: 0 0 0.5rem; color: #132f37; font-size: 1rem; }
        .sheet-panel input {
            width: 100%; padding: 0.75rem; border: 1px solid rgba(15,90,107,0.2);
            border-radius: 0.6rem; margin: 0.5rem 0; font-size: 0.85rem;
        }
        .sheet-panel button.primary {
            width: 100%; padding: 0.9rem; background: #1c7282; color: #fff;
            border: none; border-radius: 0.65rem; font-weight: 800; cursor: pointer; margin-top: 0.35rem;
        }
        .price-row { display: none; gap: 0.5rem; margin-top: 0.5rem; }
        .price-row input { flex: 1; margin: 0; }
        .price-row button {
            padding: 0.75rem 1rem; background: #eef4f5; color: #1c7282;
            border: none; border-radius: 0.6rem; font-weight: 700; cursor: pointer;
        }
        .sheet-status { margin-top: 0.65rem; font-size: 0.85rem; font-weight: 600; color: #1c7282; }
        .sheet-status.error { color: #a82626; }
        .sheet-success { display: none; margin-top: 0.75rem; text-align: center; }
        .sheet-success a {
            display: inline-block; margin-top: 0.5rem; color: #1c7282; font-weight: 800;
        }
    </style>
</head>
<body>
<div class="shell">
    <div class="bar">
        <a href="/">Home</a>
        <span class="center">SHEIN</span>
        <a href="/cart" class="cart-link">Cart <span id="cartBadge" class="cart-badge">0</span></a>
    </div>
    <div class="frame-wrap">
        <iframe id="sheinFrame" src="https://ar.shein.com/" title="SHEIN"></iframe>
        <div id="blocked" class="blocked">
            <p>اضغطي بالأسفل لفتح SHEIN وتصفّحي المنتجات.<br>
            بعد ما تختاري منتج: انسخي الرابط واضغطي <strong>Submit Link</strong> — بينضاف لسلة وصلة.</p>
            <button type="button" class="enter-btn" id="enterShein">ادخلي SHEIN</button>
        </div>
    </div>
</div>

<button type="button" class="submit-fab" id="submitFab">🔗 Submit Link</button>

<div class="sheet" id="sheet">
    <div class="sheet-panel">
        <h3>أضيفي المنتج لسلة وصلة</h3>
        <input type="url" id="productUrl" placeholder="https://ar.shein.com/...-p-12345.html">
        <div id="priceRow" class="price-row">
            <input type="number" id="manualPrice" step="0.01" min="0" placeholder="السعر من SHEIN">
            <button type="button" id="confirmPriceBtn">تأكيد</button>
        </div>
        <button type="button" class="primary" id="goPreview">أضيف لسلة وصلة</button>
        <p id="sheetStatus" class="sheet-status"></p>
        <div id="sheetSuccess" class="sheet-success">
            <strong>تمت الإضافة ✓</strong><br>
            <a href="/cart">عرض السلة ←</a>
        </div>
    </div>
</div>
</body>
</html>
