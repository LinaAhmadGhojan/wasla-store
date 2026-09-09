package com.wasla.store;

import android.annotation.SuppressLint;
import android.content.Intent;
import android.graphics.Color;
import android.os.Bundle;
import android.view.Gravity;
import android.view.ViewGroup;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.FrameLayout;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;

/**
 * Full-screen SHEIN WebView inside Wasla app — same UX as Shipshin.
 * Floating "Submit Link" reads the current product URL from the WebView.
 */
public class SheinWebViewActivity extends AppCompatActivity {

    private WebView webView;

    @SuppressLint("SetJavaScriptEnabled")
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        String startUrl = getIntent().getStringExtra("startUrl");
        if (startUrl == null || startUrl.isEmpty()) {
            startUrl = "https://ar.shein.com/";
        }

        FrameLayout root = new FrameLayout(this);
        root.setLayoutParams(new FrameLayout.LayoutParams(
            ViewGroup.LayoutParams.MATCH_PARENT,
            ViewGroup.LayoutParams.MATCH_PARENT
        ));
        root.setBackgroundColor(Color.WHITE);

        webView = new WebView(this);
        webView.setLayoutParams(new FrameLayout.LayoutParams(
            ViewGroup.LayoutParams.MATCH_PARENT,
            ViewGroup.LayoutParams.MATCH_PARENT
        ));
        webView.getSettings().setJavaScriptEnabled(true);
        webView.getSettings().setDomStorageEnabled(true);
        webView.getSettings().setUserAgentString(
            "Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Mobile Safari/537.36"
        );
        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                return false;
            }
        });
        webView.setWebChromeClient(new WebChromeClient());
        webView.loadUrl(startUrl);

        TextView submitBtn = new TextView(this);
        FrameLayout.LayoutParams fabParams = new FrameLayout.LayoutParams(
            ViewGroup.LayoutParams.WRAP_CONTENT,
            ViewGroup.LayoutParams.WRAP_CONTENT
        );
        fabParams.gravity = Gravity.BOTTOM | Gravity.CENTER_HORIZONTAL;
        fabParams.bottomMargin = dp(24);
        submitBtn.setLayoutParams(fabParams);
        submitBtn.setText("🔗 Submit Link");
        submitBtn.setTextColor(Color.WHITE);
        submitBtn.setBackgroundColor(Color.parseColor("#1c7282"));
        submitBtn.setPadding(dp(28), dp(16), dp(28), dp(16));
        submitBtn.setTextSize(16f);
        submitBtn.setOnClickListener(v -> submitCurrentUrl());

        root.addView(webView);
        root.addView(submitBtn);
        setContentView(root);
    }

    private void submitCurrentUrl() {
        String url = webView.getUrl();
        if (url == null || !url.contains("-p-")) {
            android.widget.Toast.makeText(this, "Open a product page first", android.widget.Toast.LENGTH_SHORT).show();
            return;
        }

        Intent result = new Intent();
        result.putExtra("productUrl", url);
        setResult(RESULT_OK, result);
        finish();
    }

    @Override
    public void onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack();
        } else {
            super.onBackPressed();
        }
    }

    private int dp(int value) {
        return (int) (value * getResources().getDisplayMetrics().density);
    }
}
