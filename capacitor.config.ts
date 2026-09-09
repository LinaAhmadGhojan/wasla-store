import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.wasla.store',
  appName: 'Wasla',
  webDir: 'public',
  server: {
    // Point to your Wasla Laravel server (change for production)
    url: process.env.CAPACITOR_SERVER_URL || 'http://10.0.2.2:8000',
    cleartext: true,
  },
  android: {
    allowMixedContent: true,
  },
};

export default config;
