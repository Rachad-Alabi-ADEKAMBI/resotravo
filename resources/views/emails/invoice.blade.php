<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Votre facture Mesotravo</title>
</head>
<body style="margin:0;padding:0;background:#f8f4f0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1C1412">
<div style="max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)">

    <div style="background:linear-gradient(135deg,#F97316,#EA580C);padding:28px 36px">
        <img src="{{ $logoUrl }}" alt="Mesotravo" style="height:40px;width:auto;filter:brightness(0) invert(1)">
        <p style="margin:8px 0 0;color:rgba(255,255,255,.85);font-size:13px">Facture — Devis approuvé</p>
    </div>

    <div style="padding:32px 36px">
        <p style="font-size:16px;font-weight:700;margin-bottom:8px">Bonjour {{ $clientName }},</p>
        <p style="color:#4A3F3A;line-height:1.7;margin-bottom:20px">
            Votre devis pour la mission <strong>« {{ ucfirst($mission->service) }} »</strong> a bien été approuvé.
            Retrouvez ci-dessous votre facture ainsi que le lien pour la télécharger.
        </p>

        <table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:20px">
            <tr style="background:#f8f4f0">
                <td style="padding:10px 14px;font-weight:700;color:#8A7D78;width:40%">N° Facture</td>
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
                <td style="padding:10px 14px;font-weight:700;color:#8A7D78">Montant total</td>
                <td style="padding:10px 14px;font-weight:800;color:#F97316;font-size:15px">{{ number_format($mission->total_amount, 0, ',', ' ') }} FCFA</td>
            </tr>
        </table>

        <div style="text-align:center;margin:28px 0">
            <a href="{{ $invoiceUrl }}" style="display:inline-block;background:linear-gradient(135deg,#F97316,#EA580C);color:#fff;padding:13px 28px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px">
                📄 Télécharger ma facture
            </a>
        </div>

        <div style="background:#FFF7ED;border:1.5px solid #FCD34D;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400e;line-height:1.6">
            ⏳ Le paiement s'effectue exclusivement via <strong>MTN Mobile Money</strong> sur la plateforme Mesotravo. Connectez-vous pour procéder au paiement.
        </div>
    </div>

    <div style="background:#f8f4f0;border-top:1px solid #E8DDD4;padding:16px 36px;font-size:12px;color:#8A7D78;display:flex;justify-content:space-between">
        <span>© {{ date('Y') }} Mesotravo — mesotravo.com</span>
        <span>contact@mesotravo.com</span>
    </div>
</div>
</body>
</html>
