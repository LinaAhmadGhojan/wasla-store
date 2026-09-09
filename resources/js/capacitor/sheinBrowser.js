import { registerPlugin } from '@capacitor/core';

/**
 * Native in-app SHEIN browser (Android WebView) — same pattern as Shipshin.
 * Opens ar.shein.com full-screen INSIDE the app with a Submit Link button.
 */
export const SheinBrowser = registerPlugin('SheinBrowser');

export async function openSheinInsideApp() {
  if (!window.Capacitor?.isNativePlatform?.()) {
    return { supported: false, url: null };
  }

  const result = await SheinBrowser.open({ startUrl: 'https://ar.shein.com/' });

  return { supported: true, url: result?.url || null };
}

export function isNativeApp() {
  return !!window.Capacitor?.isNativePlatform?.();
}
