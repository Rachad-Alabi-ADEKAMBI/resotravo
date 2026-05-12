<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mot de passe oublié - Mesotravo</title>
    <style>
        :root {
            --orange: #ff6b0a;
            --orange-dark: #e85a00;
            --ink: #19120f;
            --muted: #74645a;
            --line: #eadfd8;
            --soft: #fff7ed;
            --green: #16a34a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(255, 107, 10, .16), transparent 34%),
                linear-gradient(135deg, #fff7ed 0%, #fff 42%, #f8fafc 100%);
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .auth-card {
            width: min(100%, 460px);
            background: #fff;
            border: 1px solid rgba(234, 223, 216, .95);
            border-radius: 22px;
            box-shadow: 0 24px 70px rgba(25, 18, 15, .14);
            overflow: hidden;
        }
        .auth-head {
            padding: 30px 30px 22px;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(180deg, #fff, #fffaf5);
        }
        .logo-link {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            margin-bottom: 22px;
        }
        .logo-link img {
            height: 48px;
            width: auto;
            display: block;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 28px;
            line-height: 1.1;
            letter-spacing: 0;
        }
        .subtitle {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.55;
        }
        .auth-body { padding: 26px 30px 30px; }
        .notice,
        .error-box {
            border-radius: 14px;
            padding: 13px 14px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.45;
        }
        .notice {
            color: #166534;
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
        }
        .error-box {
            color: #991b1b;
            background: #fef2f2;
            border: 1px solid #fecaca;
        }
        label {
            display: block;
            font-weight: 800;
            font-size: 13px;
            margin-bottom: 8px;
        }
        input {
            width: 100%;
            border: 1px solid #e3d6ce;
            border-radius: 14px;
            padding: 14px 15px;
            font: inherit;
            outline: none;
            color: var(--ink);
            background: #fff;
            transition: border-color .18s, box-shadow .18s;
        }
        input:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 4px rgba(255, 107, 10, .12);
        }
        .field-error {
            color: #b91c1c;
            font-size: 12px;
            margin-top: 7px;
            font-weight: 700;
        }
        .actions {
            display: grid;
            gap: 12px;
            margin-top: 20px;
        }
        .btn {
            border: 0;
            border-radius: 14px;
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 900;
            font-size: 15px;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--orange), var(--orange-dark));
            box-shadow: 0 12px 24px rgba(255, 107, 10, .26);
        }
        .btn-light {
            color: var(--ink);
            background: #f5eee9;
        }
        .help {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
            margin-top: 16px;
        }
        @media (max-width: 520px) {
            body { padding: 14px; }
            .auth-head, .auth-body { padding-left: 22px; padding-right: 22px; }
            h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <main class="auth-card">
        <section class="auth-head">
            <a class="logo-link" href="{{ route('home') }}" aria-label="Accueil Mesotravo">
                <img src="{{ asset('images/logo_mesotravo.png') }}" alt="Mesotravo">
            </a>
            <h1>Mot de passe oublié ?</h1>
            <p class="subtitle">
                Entrez l'adresse email de votre compte. Nous vous enverrons un lien sécurisé pour créer un nouveau mot de passe.
            </p>
        </section>

        <section class="auth-body">
            @if (session('status'))
                <div class="notice">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="error-box">Veuillez corriger les informations indiquées.</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <label for="email">Adresse email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="votre@email.com" required autofocus autocomplete="email">
                @error('email')
                    <div class="field-error">{{ $message }}</div>
                @enderror

                <div class="actions">
                    <button class="btn btn-primary" type="submit">
                        <span aria-hidden="true">✉</span>
                        Envoyer le lien de réinitialisation
                    </button>
                    <a class="btn btn-light" href="{{ route('login') }}">Retour à la connexion</a>
                </div>
            </form>

            <p class="help">
                Le lien expire automatiquement après 60 minutes. Si vous ne trouvez pas l'email, vérifiez aussi vos spams.
            </p>
        </section>
    </main>
</body>
</html>
