<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Votre reçu de paiement Mesotravo</title>
</head>
<body style="margin:0;padding:0;background:#f8f4f0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1C1412">
<div style="max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)">

    <div style="background:linear-gradient(135deg,#F97316,#EA580C);padding:28px 36px">
        <img src="{{ $logoUrl }}" alt="Mesotravo" style="height:40px;width:auto;filter:brightness(0) invert(1)">
        <p style="margin:8px 0 0;color:rgba(255,255,255,.85);font-size:13px">Reçu de paiement officiel</p>
    </div>

    <div style="padding:32px 36px">
        <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:24px;font-weight:700;color:#15803d;font-size:14px">
            ✅ Paiement confirmé — Mission clôturée avec succès
        </div>

        <p style="font-size:16px;font-weight:700;margin-bottom:8px">Bonjour {{ $clientName }},</p>
        <p style="color:#4A3F3A;line-height:1.7;margin-bottom:20px">
            Votre paiement pour la mission <strong>« {{ ucfirst($mission->service) }} »</strong> a bien été reçu.
            Voici votre reçu officiel.
        </p>

        <table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:20px">
            <tr style="background:#f8f4f0">
                <td style="padding:10px 14px;font-weight:700;color:#8A7D78;width:40%">N° Reçu</td>
                <td style="padding:10px 14px">#{{ str_pad($mission->id, 6, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <td style="padding:10px 14px;font-weight:700;color:#8A7D78">Service</td>
                <td style="padding:10px 14px">{{ ucfirst($mission->service) }}</td>
            </tr>
            <tr style="background:#f8f4f0">
                <td style="padding:10px 14px;font-weight:700;color:#8A7D78">Prestataire</td>
                <td style="padding:10px 14px">{{ $contractorName }}</td>
            </tr>
            <tr>
                <td style="padding:10px 14px;font-weight:700;color:#8A7D78">Moyen de paiement</td>
                <td style="padding:10px 14px">📱 MTN Mobile Money</td>
            </tr>
            <tr style="background:#f8f4f0">
                <td style="padding:10px 14px;font-weight:700;color:#8A7D78">Date de paiement</td>
                <td style="padding:10px 14px">{{ $mission->paid_at->format('d/m/Y à H:i') }}</td>
            </tr>
            <tr>
                <td style="padding:10px 14px;font-weight:700;color:#8A7D78">Montant payé</td>
                <td style="padding:10px 14px;font-weight:800;color:#F97316;font-size:15px">{{ number_format($mission->total_amount, 0, ',', ' ') }} FCFA</td>
            </tr>
            @if($mission->momo_transaction_id)
            <tr style="background:#f8f4f0">
                <td style="padding:10px 14px;font-weight:700;color:#8A7D78">Référence MoMo</td>
                <td style="padding:10px 14px;font-family:monospace;font-size:12px;color:#6b7280">{{ $mission->momo_transaction_id }}</td>
            </tr>
            @endif
        </table>

        <div style="text-align:center;margin:28px 0">
            <a href="{{ $receiptUrl }}" style="display:inline-block;background:linear-gradient(135deg,#F97316,#EA580C);color:#fff;padding:13px 28px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px">
                🧾 Télécharger mon reçu
            </a>
        </div>

        <p style="font-size:12.5px;color:#8A7D78;text-align:center;line-height:1.6">
            Ce reçu fait foi de paiement. Conservez-le pour vos dossiers.<br>
            Merci de votre confiance — à bientôt sur Mesotravo !
        </p>
    </div>

    <div style="background:#f8f4f0;border-top:1px solid #E8DDD4;padding:16px 36px;font-size:12px;color:#8A7D78">
        <span>© {{ date('Y') }} Mesotravo — mesotravo.com</span>
    </div>
</div>
</body>
</html>
