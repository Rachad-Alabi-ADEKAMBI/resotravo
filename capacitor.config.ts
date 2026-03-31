import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
    appId: 'com.resotravo.app',
    appName: 'ResoTravo',
    webDir: 'www',
    server: {
        url: 'https://resotravo.xo.je/',
        cleartext: false,
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