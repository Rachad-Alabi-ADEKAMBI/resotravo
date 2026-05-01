<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture #{{ str_pad($mission->id, 6, '0', STR_PAD_LEFT) }} - Mesotravo</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0 }
        body {
            font-family: 'Arial', Helvetica, sans-serif;
            font-size: 14px;
            background: #f8f4f0;
            color: #1C1412;
            padding: 32px 16px;
            line-height: 1.6;
        }
        .doc { max-width: 680px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 32px rgba(0,0,0,.08); overflow: hidden }
        /* Header */
        .doc-header { background: linear-gradient(135deg, #F97316, #EA580C); color: #fff; padding: 32px 40px 24px; display: flex; justify-content: space-between; align-items: flex-start }
        .doc-brand img { height: 44px; width: auto; display: block }
        .doc-subtitle { font-size: 13px; opacity: .85; margin-top: 6px }
        .doc-badge { background: rgba(255,255,255,.2); border: 1.5px solid rgba(255,255,255,.4); border-radius: 8px; padding: 8px 16px; text-align: right }
        .doc-badge-num { font-size: 18px; font-weight: 800 }
        .doc-badge-label { font-size: 11px; opacity: .8; margin-top: 2px }
        /* Body */
        .doc-body { padding: 32px 40px }
        .doc-status { display: flex; align-items: center; gap: 10px; background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 10px; padding: 12px 16px; margin-bottom: 28px; font-weight: 700; color: #1d4ed8; font-size: 14px }
        .doc-status-icon { font-size: 22px }
        /* Section */
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #8A7D78; border-bottom: 1px solid #E8DDD4; padding-bottom: 6px; margin-bottom: 14px; margin-top: 28px }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 24px }
        .info-item label { font-size: 11.5px; color: #8A7D78; display: block; margin-bottom: 2px }
        .info-item strong { font-size: 13.5px; font-weight: 700; color: #1C1412 }
        /* Table */
        .quote-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 13px }
        .quote-table th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #8A7D78; padding: 8px 10px; border-bottom: 1px solid #E8DDD4 }
        .quote-table td { padding: 10px; border-bottom: 1px solid #f5f0ec; vertical-align: top }
        .quote-table tr:last-child td { border-bottom: none }
        .quote-table .amount { text-align: right; font-weight: 600 }
        .type-chip { font-size: 10.5px; padding: 2px 7px; border-radius: 99px; font-weight: 600 }
        .type-chip.diagnostic { background: #fef3c7; color: #92400e }
        .type-chip.labor       { background: #dbeafe; color: #1e40af }
        .type-chip.other, .type-chip.material { background: #f3f4f6; color: #374151 }
        /* Totals */
        .totals-block { margin-top: 16px; border-top: 2px solid #E8DDD4; padding-top: 16px }
        .totals-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; font-size: 13.5px }
        .totals-row.total-final { font-size: 17px; font-weight: 800; color: #F97316; border-top: 1px solid #E8DDD4; margin-top: 8px; padding-top: 12px }
        .totals-row.commission-row { font-size: 12.5px; color: #8A7D78 }
        /* QR */
        .qr-block { margin-top: 24px; border: 1.5px solid #E8DDD4; border-radius: 12px; padding: 14px; display: flex; gap: 16px; align-items: center; background: #fffaf6 }
        .qr-block img { width: 118px; height: 118px; background: #fff; border: 1px solid #E8DDD4; border-radius: 8px; padding: 6px }
        .qr-title { font-size: 13.5px; font-weight: 800; color: #1C1412; margin-bottom: 4px }
        .qr-text { font-size: 12.5px; color: #5f524d; line-height: 1.5 }
        /* Notice */
        .notice-block { background: #FFF7ED; border: 1.5px solid #FCD34D; border-radius: 10px; padding: 14px 18px; margin-top: 24px; font-size: 13px; color: #92400e; line-height: 1.6 }
        /* Footer */
        .doc-footer { background: #f8f4f0; border-top: 1px solid #E8DDD4; padding: 18px 40px; font-size: 12px; color: #8A7D78; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px }
        .doc-footer a { color: #F97316; text-decoration: none }
        /* Print bar */
        .print-bar { max-width: 680px; margin: 0 auto 16px; display: flex; justify-content: flex-end; gap: 10px }
        .btn-print, .btn-close { padding: 10px 20px; border-radius: 10px; border: none; cursor: pointer; font-family: Arial, sans-serif; font-size: 13px; font-weight: 700 }
        .btn-print { background: #F97316; color: #fff }
        .btn-print:hover { background: #EA580C }
        .btn-close { background: #E8DDD4; color: #1C1412 }
        @media print { body { background: white; padding: 0 } .print-bar { display: none } .doc { box-shadow: none; border-radius: 0 } }
        @media (max-width: 540px) { .doc-header { flex-direction: column; gap: 16px } .info-grid { grid-template-columns: 1fr } .doc-body { padding: 24px 20px } .doc-footer { padding: 16px 20px } }
    </style>
</head>
<body>
@php
    $logoPath = public_path('images/logo_mesotravo.png');
    $logoSrc = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : asset('images/logo_mesotravo.png');
    $isContractorInvoice = auth()->user()?->role === 'contractor';
    $commissionDiagnosticRate = ((float) \App\Models\Setting::get('commission_diagnostic', 10)) / 100;
    $commissionMainOeuvreRate = ((float) \App\Models\Setting::get('commission_main_oeuvre', 10)) / 100;
    $diagnosticTotal = 0;
    $mainOeuvreTotal = 0;

    foreach (($mission->quote?->items ?? collect()) as $line) {
        $lineTotal = (float) $line->quantity * (float) $line->unit_price;

        if ($line->type === 'diagnostic') {
            $diagnosticTotal += $lineTotal;
        } elseif ($line->type === 'labor') {
            $mainOeuvreTotal += $lineTotal;
        }
    }

    if (($mission->quote?->items?->count() ?? 0) === 0) {
        $mainOeuvreTotal = (float) $mission->total_amount;
    }

    $contractorAmount = max(0, round(
        ($diagnosticTotal * (1 - $commissionDiagnosticRate))
        + ($mainOeuvreTotal * (1 - $commissionMainOeuvreRate)),
        2
    ));

    $verificationUrl = \Illuminate\Support\Facades\URL::signedRoute('invoices.verify', [
        'mission' => $mission->id,
    ]);
    $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($verificationUrl);
@endphp

<div class="print-bar">
    <button class="btn-close" onclick="window.history.back()">Retour</button>
    <button class="btn-print" onclick="window.print()">Imprimer / Enregistrer PDF</button>
</div>

<div class="doc">

    {{-- Header --}}
    <div class="doc-header">
        <div>
            <div class="doc-brand">
                <img src="{{ $logoSrc }}" alt="Mesotravo">
            </div>
            <div class="doc-subtitle">Facture payée</div>
        </div>
        <div class="doc-badge">
            <div class="doc-badge-num">N° {{ str_pad($mission->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div class="doc-badge-label">{{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="doc-body">

        {{-- Parties --}}
        <div class="section-title">Parties</div>
        <div class="info-grid">
            <div class="info-item">
                <label>Client</label>
                <strong>
                    {{ trim(($mission->client->first_name ?? '') . ' ' . ($mission->client->last_name ?? '')) ?: '-' }}
                    @if($mission->client->account_type === 'business' && $mission->client->company_name)
                        <br><small style="font-weight:400;color:#8A7D78">{{ $mission->client->company_name }}</small>
                    @endif
                </strong>
            </div>
            <div class="info-item">
                <label>Prestataire</label>
                <strong>
                    {{ trim(($mission->contractor->first_name ?? '') . ' ' . ($mission->contractor->last_name ?? '')) ?: '-' }}
                    @if($mission->contractor->specialty ?? null)
                        <br><small style="font-weight:400;color:#8A7D78">{{ $mission->contractor->specialty }}</small>
                    @endif
                </strong>
            </div>
        </div>

        {{-- Détails mission --}}
        <div class="section-title">Détails de la mission</div>
        <div class="info-grid">
            <div class="info-item">
                <label>Service</label>
                <strong>{{ ucfirst($mission->service) }}</strong>
            </div>
            <div class="info-item">
                <label>Type</label>
                <strong>{{ $mission->location_type === 'business' ? 'Entreprise' : 'Domicile' }}</strong>
            </div>
            <div class="info-item" style="grid-column:1/-1">
                <label>Adresse d'intervention</label>
                <strong>{{ $mission->address }}</strong>
            </div>
            @if($mission->reservation)
            <div class="info-item">
                <label>Date planifiée</label>
                <strong>{{ \Carbon\Carbon::parse($mission->reservation->day)->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</strong>
            </div>
            <div class="info-item">
                <label>Heure</label>
                <strong>{{ substr($mission->reservation->time ?? '', 0, 5) ?: '-' }}</strong>
            </div>
            @endif
        </div>

        {{-- Détail de la facture --}}
        @if(!$isContractorInvoice && $mission->quote && $mission->quote->items->count())
        <div class="section-title">Détail des prestations</div>
        <table class="quote-table">
            <thead>
                <tr>
                    <th>Prestation</th>
                    <th>Qté</th>
                    <th>PU (FCFA)</th>
                    <th class="amount">Total (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mission->quote->items as $item)
                <tr>
                    <td>
                        {{ $item->description }}
                        <br>
                        <span class="type-chip {{ $item->type }}">
                            {{ match($item->type) {
                                'diagnostic' => 'Diagnostic',
                                'labor'      => 'Main d’œuvre',
                                'material'   => 'Matériel',
                                default      => ucfirst($item->type),
                            } }}
                        </span>
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 0, ',', ' ') }}</td>
                    <td class="amount">{{ number_format($item->quantity * $item->unit_price, 0, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Totaux --}}
        <div class="totals-block">
            <div class="totals-row total-final">
                <span>{{ $isContractorInvoice ? 'Montant reçu par le prestataire' : 'Montant payé' }}</span>
                <span>{{ number_format($isContractorInvoice ? $contractorAmount : $mission->total_amount, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>

        <div class="qr-block">
            <img src="{{ $qrImageUrl }}" alt="QR code de vérification de la facture Mesotravo">
            <div>
                <div class="qr-title">Vérification Mesotravo</div>
                <div class="qr-text">
                    Scannez ce QR code pour afficher les détails de la facture et confirmer qu'elle a bien été émise par Mesotravo.
                </div>
            </div>
        </div>

    </div>

    {{-- Footer --}}
    <div class="doc-footer">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <img src="{{ $logoSrc }}" alt="Mesotravo" style="height:28px;width:auto;opacity:.7">
            <span>Plateforme de mise en relation artisans &amp; particuliers</span>
        </div>
        <div style="text-align:right">
            <a href="https://mesotravo.com">mesotravo.com</a>
        </div>
    </div>

</div>
</body>
</html>

