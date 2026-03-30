import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
    appId: 'com.resotravo.app',
    appName: 'ResoTravo',
    webDir: 'www',

    server: {
        // ── Développement (XAMPP local) ─────────────────────────────────
        // Remplace par l'IP LAN de ta machine pour tester sur un vrai appareil
        // Ex: 'http://192.168.1.42/resotravo/public'
        url: 'http://10.0.2.2/resotravo/public', // 10.0.2.2 = localhost depuis l'émulateur Android
        cleartext: true,       // Autorise HTTP (désactiver en production)
        androidScheme: 'http', // Scheme utilisé dans la WebView Android

        // ── Production ─────────────────────────────────────────────────
        // Décommente la ligne ci-dessous et commente les lignes dev :
        // url: 'https://resotravo.com',
        // cleartext: false,
    },

    android: {
        backgroundColor: '#1C1412',
    },

    ios: {
        backgroundColor: '#1C1412',
    },

    plugins: {
        SplashScreen: {
            launchShowDuration: 2000,
            backgroundColor: '#1C1412',
            androidSplashResourceName: 'splash',
            showSpinner: false,
        },
        StatusBar: {
            style: 'light',
            backgroundColor: '#1C1412',
        },
    },
};

export default config;
