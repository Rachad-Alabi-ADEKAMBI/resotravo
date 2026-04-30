/**
 * mobile.js — Intégration Capacitor pour mesotravo
 * Chargé uniquement quand l'app tourne dans un contexte natif Capacitor.
 */

import { Capacitor } from '@capacitor/core';
import { StatusBar, Style } from '@capacitor/status-bar';
import { SplashScreen } from '@capacitor/splash-screen';
import { App } from '@capacitor/app';
import { Keyboard } from '@capacitor/keyboard';
import { Network } from '@capacitor/network';

const isMobile = Capacitor.isNativePlatform();

export async function initMobile() {
    if (!isMobile) return;

    // ── Status bar ────────────────────────────────────────────────
    try {
        await StatusBar.setStyle({ style: Style.Dark }); // texte blanc
        await StatusBar.setBackgroundColor({ color: '#1C1412' });
    } catch (e) { /* iOS ne supporte pas setBackgroundColor */ }

    // ── Splash screen ─────────────────────────────────────────────
    await SplashScreen.hide({ fadeOutDuration: 300 });

    // ── Bouton retour Android ─────────────────────────────────────
    App.addListener('backButton', ({ canGoBack }) => {
        if (canGoBack) {
            window.history.back();
        } else {
            // Minimise l'app au lieu de la fermer
            App.minimizeApp();
        }
    });

    // ── Réseau — alerte hors-ligne ────────────────────────────────
    Network.addListener('networkStatusChange', (status) => {
        if (!status.connected) {
            showOfflineBanner();
        } else {
            hideOfflineBanner();
        }
    });

    // Vérification initiale
    const status = await Network.getStatus();
    if (!status.connected) showOfflineBanner();

    // ── Keyboard — ajuster le scroll ─────────────────────────────
    Keyboard.addListener('keyboardWillShow', (info) => {
        document.documentElement.style.setProperty(
            '--keyboard-height', `${info.keyboardHeight}px`
        );
        document.body.classList.add('keyboard-open');
    });
    Keyboard.addListener('keyboardWillHide', () => {
        document.documentElement.style.setProperty('--keyboard-height', '0px');
        document.body.classList.remove('keyboard-open');
    });

    // ── Safe area (notch / Dynamic Island) ───────────────────────
    applySafeArea();
}

// ── Bannière hors-ligne ───────────────────────────────────────────
function showOfflineBanner() {
    if (document.getElementById('rt-offline-banner')) return;
    const el = document.createElement('div');
    el.id = 'rt-offline-banner';
    el.textContent = '📡 Pas de connexion internet';
    el.style.cssText = `
        position:fixed;top:0;left:0;right:0;z-index:99999;
        background:#ef4444;color:#fff;text-align:center;
        padding:8px 16px;font-family:Poppins,sans-serif;
        font-size:13px;font-weight:700;
        padding-top:calc(8px + env(safe-area-inset-top));
    `;
    document.body.prepend(el);
}

function hideOfflineBanner() {
    document.getElementById('rt-offline-banner')?.remove();
}

// ── Safe area CSS vars ────────────────────────────────────────────
function applySafeArea() {
    const style = document.createElement('style');
    style.textContent = `
        :root {
            --safe-top:    env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-left:   env(safe-area-inset-left, 0px);
            --safe-right:  env(safe-area-inset-right, 0px);
        }
        /* Ajouter padding pour les topbars */
        .ab-sidebar {
            padding-top: env(safe-area-inset-top, 0px) !important;
        }
        /* Assurer que le contenu ne se cache pas derrière la home bar iOS */
        .ab-main {
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }
    `;
    document.head.appendChild(style);
}

export { isMobile };
