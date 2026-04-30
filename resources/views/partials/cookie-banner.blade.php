{{--
    Banniere de consentement aux cookies (RGPD)
    La bannière est masquée par défaut (display:none).
    Le JS vérifie localStorage pour savoir si l'utilisateur a déjà répondu.
    On n'utilise plus request()->cookie() car Laravel chiffre ses cookies
    et ne peut pas lire un cookie défini en clair par JavaScript.
--}}
<div id="cookie-banner" style="
    display:none;
    position:fixed; bottom:0; left:0; right:0; z-index:99999;
    background:#1a1a1a; color:#f5f5f5;
    padding:16px 24px;
    flex-wrap:wrap; align-items:center; justify-content:center; gap:12px 24px;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
    font-size:14px; line-height:1.5;
    box-shadow:0 -2px 12px rgba(0,0,0,.25);
">
    <p style="margin:0; flex:1 1 300px; min-width:0;">
        Ce site utilise des cookies pour améliorer votre expérience.
        En cliquant sur <strong>Accepter</strong>, vous consentez à l'utilisation de cookies analytiques.
        <a href="/policy" style="color:#f97316;text-decoration:underline;">En savoir plus</a>
    </p>
    <div style="display:flex; gap:10px; flex-shrink:0;">
        <button onclick="setCookieConsent('refused')" style="
            padding:8px 20px; border-radius:6px;
            border:1px solid #666; background:transparent; color:#f5f5f5;
            font-size:14px; cursor:pointer;
        ">Refuser</button>
        <button onclick="setCookieConsent('accepted')" style="
            padding:8px 20px; border-radius:6px;
            border:none; background:#f97316; color:#fff;
            font-size:14px; font-weight:600; cursor:pointer;
        ">Accepter</button>
    </div>
</div>
<script>
(function () {
    // Si l'utilisateur a déjà répondu, on n'affiche pas la bannière
    if (localStorage.getItem('cookie_consent')) return;

    var banner = document.getElementById('cookie-banner');
    if (banner) banner.style.display = 'flex';
})();

function setCookieConsent(value) {
    localStorage.setItem('cookie_consent', value);
    document.getElementById('cookie-banner').remove();
    if (value === 'accepted') {
        document.dispatchEvent(new Event('cookie_consent_accepted'));
    }
}
</script>
