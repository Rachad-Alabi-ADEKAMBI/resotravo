<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aucun compte trouvé - Mesotravo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f8f4f0;
            font-family: Poppins, Arial, sans-serif;
            color: #1c1412;
        }
        .gnf-card {
            width: 100%;
            max-width: 500px;
            background: #fff;
            border: 1px solid #eadfd7;
            border-radius: 18px;
            padding: 34px 30px;
            box-shadow: 0 12px 42px rgba(28, 20, 18, .09);
        }
        .gnf-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            font-weight: 900;
            letter-spacing: .2px;
        }
        .gnf-logo img { width: 34px; height: 34px; border-radius: 9px; flex: 0 0 auto; }
        .gnf-logo span { font-size: 28px; line-height: 1; color: #1c1412; }
        .gnf-logo span em { color: #f97316; font-style: normal; }
        .gnf-badge {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #fff7ed;
            color: #ea580c;
            font-size: 24px;
            margin-bottom: 18px;
        }
        .gnf-title {
            font-size: 24px;
            line-height: 1.25;
            font-weight: 850;
            margin-bottom: 10px;
        }
        .gnf-text {
            color: #6b5f5a;
            font-size: 14.5px;
            line-height: 1.7;
            margin-bottom: 18px;
        }
        .gnf-google {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 22px;
        }
        .gnf-google img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            flex: 0 0 auto;
        }
        .gnf-google strong {
            display: block;
            font-size: 14px;
            margin-bottom: 2px;
        }
        .gnf-google span {
            display: block;
            color: #6b7280;
            font-size: 13px;
            overflow-wrap: anywhere;
        }
        .gnf-actions {
            display: grid;
            gap: 10px;
            margin-top: 8px;
        }
        .gnf-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            padding: 12px 16px;
            border-radius: 11px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 800;
            transition: transform .18s, box-shadow .18s, background .18s;
        }
        .gnf-btn-primary {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #fff;
            box-shadow: 0 8px 20px rgba(249, 115, 22, .24);
        }
        .gnf-btn-icon { width: 18px; height: 18px; color: #fff; display: inline-flex; align-items: center; justify-content: center; }
        .gnf-btn-icon svg { width: 18px; height: 18px; display: block; }
        .gnf-btn-ghost {
            color: #6b5f5a;
            background: transparent;
        }
        .gnf-btn:hover {
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <main class="gnf-card">
        <div class="gnf-logo">
            <img src="/images/favicon.PNG" alt="Mesotravo">
            <span>MESO<em>TRAVO</em></span>
        </div>

        <div class="gnf-badge">!</div>
        <h1 class="gnf-title">Aucun compte trouvé avec cet email</h1>
        <p class="gnf-text">
            L'adresse Gmail ci-dessous est bien authentifiée par Google, mais aucun compte Mesotravo
            n'existe encore avec cet email. Vous pouvez créer un compte pour continuer.
        </p>

        <div class="gnf-google">
            @if (! empty($googleUser['avatar']))
                <img src="{{ $googleUser['avatar'] }}" alt="">
            @endif
            <div>
                <strong>{{ $googleUser['name'] }}</strong>
                <span>{{ $googleUser['email'] }}</span>
            </div>
        </div>

        <div class="gnf-actions">
            <a class="gnf-btn gnf-btn-primary" href="{{ route('auth.google.complete') }}">
                <span class="gnf-btn-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M20 21a8 8 0 0 0-16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </span>
                <span>Inscription</span>
            </a>
            <a class="gnf-btn gnf-btn-ghost" href="{{ route('login') }}">
                Revenir à la connexion
            </a>
        </div>
    </main>
</body>
</html>
