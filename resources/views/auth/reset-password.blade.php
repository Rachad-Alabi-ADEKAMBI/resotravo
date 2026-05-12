<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nouveau mot de passe - Mesotravo</title>
    <style>
        :root {
            --orange: #ff6b0a;
            --orange-dark: #e85a00;
            --ink: #19120f;
            --muted: #74645a;
            --line: #eadfd8;
            --soft: #fff7ed;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top right, rgba(255, 107, 10, .16), transparent 34%),
                linear-gradient(135deg, #fff7ed 0%, #fff 42%, #f8fafc 100%);
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .auth-card {
            width: min(100%, 500px);
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
        .error-box {
            color: #991b1b;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 14px;
            padding: 13px 14px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.45;
        }
        .field { margin-bottom: 16px; }
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
        .btn {
            width: 100%;
            border: 0;
            border-radius: 14px;
            min-height: 50px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #fff;
            background: linear-gradient(135deg, var(--orange), var(--orange-dark));
            box-shadow: 0 12px 24px rgba(255, 107, 10, .26);
            font-weight: 900;
            font-size: 15px;
            cursor: pointer;
        }
        .help {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
            margin: 16px 0 0;
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
            <h1>Créez un nouveau mot de passe</h1>
            <p class="subtitle">
                Votre lien a été vérifié. Choisissez un mot de passe solide pour sécuriser votre compte Mesotravo.
            </p>
        </section>

        <section class="auth-body">
            @if ($errors->any())
                <div class="error-box">Le lien ou les informations saisies ne sont pas valides.</div>
            @endif

            <form method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="field">
                    <label for="email">Adresse email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
                    @error('email')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Nouveau mot de passe</label>
                    <input id="password" type="password" name="password" placeholder="Au moins 8 caractères" required autocomplete="new-password">
                    @error('password')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirmer le mot de passe</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                    @error('password_confirmation')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <button class="btn" type="submit">
                    <span aria-hidden="true">✓</span>
                    Enregistrer le nouveau mot de passe
                </button>
            </form>

            <p class="help">
                Après validation, vous pourrez vous connecter avec ce nouveau mot de passe.
            </p>
        </section>
    </main>
</body>
</html>
