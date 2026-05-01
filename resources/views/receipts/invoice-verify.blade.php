<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification facture Mesotravo</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0 }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f8f4f0;
            color: #1C1412;
            padding: 28px 16px;
            line-height: 1.6;
        }
        .verify-card {
            max-width: 620px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 32px rgba(0,0,0,.08);
            overflow: hidden;
        }
        .verify-header {
            background: linear-gradient(135deg, #16A34A, #0F8A3B);
            color: #fff;
            padding: 28px 32px;
        }
        .verify-header img { height: 42px; width: auto; display: block; margin-bottom: 16px }
        .verify-header h1 { font-size: 24px; margin-bottom: 6px }
        .verify-header p { font-size: 14px; opacity: .9 }
        .verify-body { padding: 28px 32px }
        .status {
            background: #ECFDF5;
            border: 1.5px solid #86EFAC;
            color: #15803D;
            border-radius: 12px;
            padding: 14px 16px;
            font-weight: 800;
            margin-bottom: 22px;
        }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 24px }
        .item label { display: block; color: #8A7D78; font-size: 12px; margin-bottom: 3px }
        .item strong { font-size: 14px; color: #1C1412 }
        .item.full { grid-column: 1 / -1 }
        .amount {
            margin-top: 24px;
            border-top: 2px solid #E8DDD4;
            padding-top: 16px;
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 900;
            color: #F97316;
        }
        @media (max-width: 560px) {
            .grid { grid-template-columns: 1fr }
            .verify-header, .verify-body { padding: 24px 20px }
        }
    </style>
</head>
<body>
@php
    $logoPath = public_path('images/logo_mesotravo.png');
    $logoSrc = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : asset('images/logo_mesotravo.png');
@endphp

<main class="verify-card">
    <header class="verify-header">
        <img src="{{ $logoSrc }}" alt="Mesotravo">
        <h1>Facture Mesotravo vérifiée</h1>
        <p>Ce lien signé confirme que cette facture a bien été émise par Mesotravo.</p>
    </header>

    <section class="verify-body">
        <div class="status">Facture authentique et payée</div>

        <div class="grid">
            <div class="item">
                <label>Numéro de facture</label>
                <strong>N° {{ str_pad($mission->id, 6, '0', STR_PAD_LEFT) }}</strong>
            </div>
            <div class="item">
                <label>Date de vérification</label>
                <strong>{{ now()->format('d/m/Y H:i') }}</strong>
            </div>
            <div class="item">
                <label>Client</label>
                <strong>{{ trim(($mission->client->first_name ?? '') . ' ' . ($mission->client->last_name ?? '')) ?: '-' }}</strong>
            </div>
            <div class="item">
                <label>Prestataire</label>
                <strong>{{ trim(($mission->contractor->first_name ?? '') . ' ' . ($mission->contractor->last_name ?? '')) ?: '-' }}</strong>
            </div>
            <div class="item">
                <label>Service</label>
                <strong>{{ ucfirst($mission->service) }}</strong>
            </div>
            <div class="item">
                <label>Type de mission</label>
                <strong>{{ $mission->location_type === 'business' ? 'Entreprise' : 'Domicile' }}</strong>
            </div>
            <div class="item full">
                <label>Adresse d'intervention</label>
                <strong>{{ $mission->address }}</strong>
            </div>
            @if($mission->reservation)
            <div class="item">
                <label>Date planifiée</label>
                <strong>{{ \Carbon\Carbon::parse($mission->reservation->day)->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</strong>
            </div>
            <div class="item">
                <label>Heure</label>
                <strong>{{ substr($mission->reservation->time ?? '', 0, 5) ?: '-' }}</strong>
            </div>
            @endif
        </div>

        <div class="amount">
            <span>Montant payé</span>
            <span>{{ number_format($mission->total_amount, 0, ',', ' ') }} FCFA</span>
        </div>
    </section>
</main>
</body>
</html>
