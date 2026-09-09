<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SheinWebViewController extends Controller
{
    /**
     * Minimal chrome page for mobile WebView fallback (non-Capacitor).
     * Capacitor native app uses SheinWebViewActivity instead.
     */
    public function show()
    {
        return view('storefront.shein-webview-shell');
    }
}
