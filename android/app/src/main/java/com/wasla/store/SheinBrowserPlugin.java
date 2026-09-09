package com.wasla.store;

import android.content.Intent;
import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;
import com.getcapacitor.annotation.ActivityCallback;
import androidx.activity.result.ActivityResult;

@CapacitorPlugin(name = "SheinBrowser")
public class SheinBrowserPlugin extends Plugin {

    public static final int SHEIN_REQUEST = 9001;
    private PluginCall pendingCall;

    @PluginMethod
    public void open(PluginCall call) {
        pendingCall = call;
        String startUrl = call.getString("startUrl", "https://ar.shein.com/");

        Intent intent = new Intent(getContext(), SheinWebViewActivity.class);
        intent.putExtra("startUrl", startUrl);
        startActivityForResult(call, intent, "sheinResult");
    }

    @ActivityCallback
    private void sheinResult(PluginCall call, ActivityResult result) {
        if (pendingCall == null) {
            return;
        }

        JSObject ret = new JSObject();
        if (result.getResultCode() == android.app.Activity.RESULT_OK && result.getData() != null) {
            ret.put("url", result.getData().getStringExtra("productUrl"));
        } else {
            ret.put("url", null);
        }
        pendingCall.resolve(ret);
        pendingCall = null;
    }
}
