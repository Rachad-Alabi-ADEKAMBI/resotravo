<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\Payment;

class ReceiptPdfService
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const MESOTRAVO_PHONE = '+229 01 90 00 36 26';
    private const MESOTRAVO_EMAIL = 'contact@mesotravo.com';
    private const MESOTRAVO_IFU = '3202625062491';
    private const ORANGE = [0.976, 0.451, 0.086];
    private const ORANGE_LIGHT = [1, 0.969, 0.929];
    private const ORANGE_BORDER = [0.996, 0.843, 0.667];
    private const DARK = [0.110, 0.078, 0.071];
    private const MUTED = [0.486, 0.416, 0.353];

    private array $commands = [];
    private ?string $logoJpeg = null;
    private int $logoWidth = 0;
    private int $logoHeight = 0;

    public function make(Mission $mission): string
    {
        $mission->loadMissing(['client.user', 'contractor.user', 'quote.items', 'reservation']);

        $this->commands = [];
        $this->loadLogo();

        $clientName = trim(($mission->client->first_name ?? '') . ' ' . ($mission->client->last_name ?? ''))
            ?: ($mission->client?->user?->name ?? '-');
        $contractorName = trim(($mission->contractor->first_name ?? '') . ' ' . ($mission->contractor->last_name ?? ''))
            ?: ($mission->contractor?->user?->name ?? '-');
        $payment = Payment::where('mission_id', $mission->id)->latest()->first();
        $paidAt = $mission->paid_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');
        $amount = (float) ($mission->total_amount ?? $mission->quote?->amount_incl_tax ?? 0);

        $this->header($mission, $paidAt);
        $this->paidBox($amount);
        $this->sectionLabel("Informations de la mission", 585);
        $this->infoRow('Client', $clientName, 562);
        if ($mission->client?->ifu) {
            $this->infoRow('IFU client', (string) $mission->client->ifu, 542);
        }
        $this->infoRow('Prestataire', $contractorName, $mission->client?->ifu ? 522 : 542);
        $this->infoRow('Service', ucfirst((string) $mission->service), $mission->client?->ifu ? 502 : 522);
        $this->wrappedInfoRow('Adresse', (string) $mission->address, $mission->client?->ifu ? 482 : 502);

        $paymentTop = $mission->client?->ifu ? 410 : 430;
        $this->sectionLabel('Détail du paiement', $paymentTop);
        $this->paymentTable($paymentTop - 28, [
            ['Montant payé', $this->formatPrice($amount)],
            ['Moyen de paiement', 'Mobile Money'],
            ['Téléphone de paiement', $payment?->phone ?: '-'],
            ['Réseau', strtoupper((string) ($payment?->network ?: 'MTN'))],
            ['Référence paiement', (string) ($mission->momo_transaction_id ?? $payment?->reference_id ?? '-')],
            ['Date de paiement', $paidAt],
        ]);

        $this->noteBox(205, 'Ce reçu de paiement émis par Mesotravo atteste du paiement de la mission #' . $mission->id . '. Conservez-le pour vos dossiers.');
        $this->footer();

        return $this->buildPdf([implode("\n", $this->commands)]);
    }

    private function header(Mission $mission, string $paidAt): void
    {
        if ($this->logoJpeg) {
            $this->image('Im1', 50, 747, 150, 48);
        } else {
            $this->text(50, 790, 'Meso', 24, 'F2', self::ORANGE);
            $this->text(112, 790, 'Travo', 24, 'F2', self::DARK);
        }
        $this->text(50, 732, 'Courtier en travaux', 10, 'F1', self::MUTED);
        $this->text(50, 717, 'IFU : ' . self::MESOTRAVO_IFU, 10, 'F1', self::MUTED);
        $this->text(50, 702, self::MESOTRAVO_PHONE . ' - ' . self::MESOTRAVO_EMAIL, 10, 'F1', self::MUTED);

        $receiptNumber = str_pad((string) $mission->id, 6, '0', STR_PAD_LEFT);
        $this->textRight(545, 790, 'REÇU DE PAIEMENT', 18, 'F2', self::DARK);
        $this->textRight(545, 766, 'Reçu n°' . $receiptNumber, 12, 'F2', self::ORANGE);
        $this->textRight(545, 748, 'Mission #' . $mission->id . ' - ' . ucfirst((string) $mission->service), 10, 'F1', self::MUTED);
        $this->textRight(545, 733, 'Paiement confirmé le ' . $paidAt, 10, 'F1', self::MUTED);

        $this->line(50, 680, 545, 680, self::ORANGE, 1.8);
    }

    private function paidBox(float $amount): void
    {
        $this->rect(50, 625, 495, 52, self::ORANGE_LIGHT, self::ORANGE_BORDER);
        $this->textCentered(297.5, 655, 'Paiement confirmé', 14, 'F2', self::ORANGE);
        $this->textCentered(297.5, 636, 'Montant payé : ' . $this->formatPrice($amount), 12, 'F2', self::DARK);
    }

    private function sectionLabel(string $label, float $y): void
    {
        $this->text(50, $y, strtoupper($label), 9, 'F2', self::MUTED);
    }

    private function infoRow(string $label, string $value, float $y): void
    {
        $this->text(50, $y, $label, 10, 'F1', self::MUTED);
        $this->textRight(545, $y, $value, 10, 'F2', self::DARK);
        $this->line(50, $y - 9, 545, $y - 9, [0.910, 0.867, 0.831], 0.6);
    }

    private function wrappedInfoRow(string $label, string $value, float $y): void
    {
        $this->text(50, $y, $label, 10, 'F1', self::MUTED);
        $lines = $this->wrap($value, 54);
        foreach ($lines as $index => $line) {
            $this->textRight(545, $y - ($index * 13), $line, 10, $index === 0 ? 'F2' : 'F1', self::DARK);
        }
        $this->line(50, $y - 9 - (max(0, count($lines) - 1) * 13), 545, $y - 9 - (max(0, count($lines) - 1) * 13), [0.910, 0.867, 0.831], 0.6);
    }

    private function paymentTable(float $top, array $rows): void
    {
        $rowHeight = 26;
        $height = count($rows) * $rowHeight;
        $this->rect(50, $top - $height, 495, $height, [1, 1, 1], [0.910, 0.867, 0.831]);

        foreach ($rows as $index => [$label, $value]) {
            $y = $top - 17 - ($index * $rowHeight);
            if ($index % 2 === 0) {
                $this->rect(50, $top - (($index + 1) * $rowHeight), 495, $rowHeight, [1, 0.984, 0.969], null);
            }
            $this->text(64, $y, $label, 10, 'F1', self::MUTED);
            $this->textRight(530, $y, $value, 10, 'F2', self::DARK);
        }
    }

    private function noteBox(float $y, string $note): void
    {
        $lines = $this->wrap($note, 94);
        $height = 22 + count($lines) * 13;
        $this->rect(50, $y - $height, 495, $height, self::ORANGE_LIGHT, null);
        foreach ($lines as $index => $line) {
            $this->text(64, $y - 18 - ($index * 13), $line, 9, 'F1', self::MUTED);
        }
    }

    private function footer(): void
    {
        $this->line(50, 50, 545, 50, [0.910, 0.867, 0.831], 0.8);
        $this->textCentered(297.5, 34, 'Mesotravo - Courtier en travaux', 8, 'F1', [0.690, 0.627, 0.604]);
        $this->textCentered(297.5, 22, self::MESOTRAVO_PHONE . ' - ' . self::MESOTRAVO_EMAIL . ' - IFU : ' . self::MESOTRAVO_IFU, 8, 'F1', [0.690, 0.627, 0.604]);
    }

    private function loadLogo(): void
    {
        $path = public_path('images/logo_mesotravo.png');
        if (!is_file($path) || !extension_loaded('gd')) {
            return;
        }

        $source = @imagecreatefrompng($path);
        if (!$source) {
            return;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $this->logoWidth = 420;
        $this->logoHeight = max(1, (int) round($this->logoWidth * $sourceHeight / $sourceWidth));

        $target = imagecreatetruecolor($this->logoWidth, $this->logoHeight);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefilledrectangle($target, 0, 0, $this->logoWidth, $this->logoHeight, $white);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $this->logoWidth, $this->logoHeight, $sourceWidth, $sourceHeight);

        ob_start();
        imagejpeg($target, null, 90);
        $jpeg = ob_get_clean();

        imagedestroy($source);
        imagedestroy($target);

        $this->logoJpeg = $jpeg !== false ? $jpeg : null;
    }

    private function image(string $name, float $x, float $y, float $w, float $h): void
    {
        $this->commands[] = 'q ' . $w . ' 0 0 ' . $h . ' ' . $x . ' ' . $y . ' cm /' . $name . ' Do Q';
    }

    private function rect(float $x, float $y, float $w, float $h, ?array $fill, ?array $stroke): void
    {
        $cmd = 'q ';
        if ($fill) {
            $cmd .= $this->rgb($fill) . ' rg ';
        }
        if ($stroke) {
            $cmd .= $this->rgb($stroke) . ' RG ';
        }
        $cmd .= $x . ' ' . $y . ' ' . $w . ' ' . $h . ' re ' . ($fill && $stroke ? 'B' : ($fill ? 'f' : 'S')) . ' Q';
        $this->commands[] = $cmd;
    }

    private function line(float $x1, float $y1, float $x2, float $y2, array $color, float $width): void
    {
        $this->commands[] = 'q ' . $this->rgb($color) . ' RG ' . $width . ' w ' . $x1 . ' ' . $y1 . ' m ' . $x2 . ' ' . $y2 . ' l S Q';
    }

    private function text(float $x, float $y, string $value, float $size, string $font = 'F1', array $color = [0, 0, 0]): void
    {
        $this->commands[] = $this->rgb($color) . ' rg BT /' . $font . ' ' . $size . ' Tf ' . $x . ' ' . $y . ' Td ' . $this->pdfText($value) . ' Tj ET';
    }

    private function textRight(float $rightX, float $y, string $value, float $size, string $font = 'F1', array $color = [0, 0, 0]): void
    {
        $this->text($rightX - $this->textWidth($value, $size), $y, $value, $size, $font, $color);
    }

    private function textCentered(float $centerX, float $y, string $value, float $size, string $font = 'F1', array $color = [0, 0, 0]): void
    {
        $this->text($centerX - ($this->textWidth($value, $size) / 2), $y, $value, $size, $font, $color);
    }

    private function textWidth(string $value, float $size): float
    {
        return mb_strlen($value) * $size * 0.48;
    }

    private function rgb(array $color): string
    {
        return implode(' ', array_map(fn (float $v) => rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.'), $color));
    }

    private function wrap(string $text, int $length): array
    {
        $text = $this->cleanText($text);
        if ($text === '') {
            return ['-'];
        }

        return explode("\n", wordwrap($text, $length, "\n", true));
    }

    private function cleanText(string $text): string
    {
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private function formatPrice(float $value): string
    {
        return number_format($value, 0, ',', ' ') . ' FCFA';
    }

    private function pdfText(string $text): string
    {
        $text = $this->cleanText($text);
        $encoded = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);

        return '(' . $escaped . ')';
    }

    private function buildPdf(array $pages): string
    {
        $objects = [];
        $pageObjectNumbers = [];

        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $logoObjectNumber = null;
        if ($this->logoJpeg) {
            $logoObjectNumber = count($objects) + 1;
            $objects[] = "<< /Type /XObject /Subtype /Image /Width {$this->logoWidth} /Height {$this->logoHeight} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($this->logoJpeg) . " >>\nstream\n" . $this->logoJpeg . "\nendstream";
        }

        foreach ($pages as $pageContent) {
            $stream = "<< /Length " . strlen($pageContent) . " >>\nstream\n" . $pageContent . "\nendstream";
            $contentObjectNumber = count($objects) + 1;
            $objects[] = $stream;

            $pageObjectNumber = count($objects) + 1;
            $pageObjectNumbers[] = $pageObjectNumber;
            $xObject = $logoObjectNumber ? ' /XObject << /Im1 ' . $logoObjectNumber . ' 0 R >>' : '';
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >>' . $xObject . ' >> /Contents ' . $contentObjectNumber . ' 0 R >>';
        }

        $objects[1] = '<< /Type /Pages /Kids [' . implode(' ', array_map(fn (int $number) => $number . ' 0 R', $pageObjectNumbers)) . '] /Count ' . count($pageObjectNumbers) . ' >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }
}

