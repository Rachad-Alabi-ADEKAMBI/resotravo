<?php

namespace App\Services;

use App\Models\Mission;

class InvoicePdfService
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const MESOTRAVO_PHONE = '+229 01 90 00 36 26';
    private const MESOTRAVO_EMAIL = 'contact@mesotravo.com';
    private const MESOTRAVO_IFU = '3202625062491';
    private const ORANGE = [0.976, 0.451, 0.086];
    private const ORANGE_DARK = [0.918, 0.345, 0];
    private const ORANGE_LIGHT = [1, 0.969, 0.929];
    private const BORDER = [0.910, 0.867, 0.831];
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
        $invoiceTotal = (float) ($mission->total_amount ?? $mission->quote?->amount_incl_tax ?? 0);
        $issuedAt = now()->format('d/m/Y à H:i');

        $this->header($mission, $issuedAt);
        $this->sectionLabel('Intervenants', 585);
        $this->twoColumnInfo(50, 560, 'Client', $clientName, 320, 'Prestataire', $contractorName);
        if ($mission->contractor?->specialty) {
            $this->infoRow('Spécialité', (string) $mission->contractor->specialty, 532);
        }

        $this->sectionLabel('Détails de la mission', 492);
        $this->twoColumnInfo(
            50,
            467,
            'Service',
            ucfirst((string) $mission->service),
            320,
            'Type',
            $mission->location_type === 'business' ? 'Entreprise' : 'Domicile'
        );
        $this->wrappedInfoRow("Adresse d'intervention", (string) $mission->address, 438);

        $tableTop = 382;
        if ($mission->reservation) {
            $this->twoColumnInfo(
                50,
                403,
                'Date planifiée',
                $mission->reservation->day->format('d/m/Y'),
                320,
                'Heure',
                substr((string) $mission->reservation->time, 0, 5) ?: '-'
            );
            $tableTop = 348;
        }

        $afterTableY = $this->invoiceTable($mission, $tableTop);
        $this->totalBox(max(120, $afterTableY - 70), $invoiceTotal);
        $this->verificationBox(84, $mission);
        $this->footer();

        return $this->buildPdf([implode("\n", $this->commands)]);
    }

    private function header(Mission $mission, string $issuedAt): void
    {
        $this->rect(40, 660, 515, 142, self::ORANGE, null);
        $this->rect(400, 735, 120, 44, null, [1, 0.78, 0.62]);

        if ($this->logoJpeg) {
            $this->image('Im1', 66, 750, 138, 44);
        } else {
            $this->text(66, 770, 'MESO', 18, 'F2', [1, 1, 1]);
            $this->text(118, 770, 'TRAVO', 18, 'F2', self::DARK);
        }

        $this->text(66, 726, 'Courtier en travaux', 10, 'F1', [1, 1, 1]);
        $this->text(66, 710, 'IFU : ' . self::MESOTRAVO_IFU, 10, 'F1', [1, 1, 1]);
        $this->text(66, 694, 'Tél : ' . self::MESOTRAVO_PHONE, 10, 'F1', [1, 1, 1]);
        $this->text(66, 678, 'Email : ' . self::MESOTRAVO_EMAIL, 10, 'F1', [1, 1, 1]);

        $number = str_pad((string) $mission->id, 6, '0', STR_PAD_LEFT);
        $this->textCentered(460, 759, 'N° ' . $number, 15, 'F2', [1, 1, 1]);
        $this->textCentered(460, 743, $issuedAt, 8, 'F2', [1, 1, 1]);
        $this->text(50, 624, 'FACTURE', 18, 'F2', self::DARK);
        $this->textRight(545, 624, 'Mission #' . $mission->id, 10, 'F2', self::MUTED);
    }

    private function invoiceTable(Mission $mission, float $top): float
    {
        $this->sectionLabel('Détail des prestations', $top + 24);
        $this->rect(50, $top - 24, 495, 24, self::ORANGE_LIGHT, self::BORDER);
        $this->text(62, $top - 16, 'Prestation', 9, 'F2', self::MUTED);
        $this->text(312, $top - 16, 'Qté', 9, 'F2', self::MUTED);
        $this->text(360, $top - 16, 'PU', 9, 'F2', self::MUTED);
        $this->textRight(530, $top - 16, 'Total', 9, 'F2', self::MUTED);

        $y = $top - 44;
        $items = $mission->quote?->items ?? collect();

        if ($items->count() === 0) {
            $this->tableRow($y, 'Mission ' . ucfirst((string) $mission->service), 1, (float) $mission->total_amount);
            return $y - 36;
        }

        foreach ($items as $item) {
            if ($y < 150) {
                $this->text(62, $y, 'Suite des lignes disponible dans votre espace Mesotravo.', 9, 'F1', self::MUTED);
                return $y - 28;
            }

            $description = $this->typeLabel((string) $item->type) . ' - ' . (string) $item->description;
            $lineTotal = (float) $item->quantity * (float) $item->unit_price;
            $this->tableRow($y, $description, (float) $item->quantity, (float) $item->unit_price, $lineTotal);
            $y -= 34;
        }

        return $y;
    }

    private function tableRow(float $y, string $description, float $quantity, float $unitPrice, ?float $lineTotal = null): void
    {
        $lineTotal ??= $quantity * $unitPrice;
        $this->line(50, $y - 12, 545, $y - 12, self::BORDER, 0.5);
        $this->text(62, $y, $this->shorten($description, 48), 9, 'F1', self::DARK);
        $this->text(314, $y, $this->formatNumber($quantity), 9, 'F1', self::DARK);
        $this->textRight(430, $y, $this->formatNumber($unitPrice), 9, 'F1', self::DARK);
        $this->textRight(530, $y, $this->formatNumber($lineTotal), 9, 'F2', self::DARK);
    }

    private function totalBox(float $y, float $amount): void
    {
        $this->rect(50, $y, 495, 54, self::ORANGE_LIGHT, [0.996, 0.843, 0.667]);
        $this->text(70, $y + 30, 'Montant à payer', 14, 'F2', self::ORANGE_DARK);
        $this->textRight(525, $y + 30, $this->formatPrice($amount), 15, 'F2', self::DARK);
    }

    private function verificationBox(float $y, Mission $mission): void
    {
        $verificationUrl = \Illuminate\Support\Facades\URL::signedRoute('invoices.verify', [
            'mission' => $mission->id,
        ]);
        $this->rect(50, $y, 495, 44, [1, 0.984, 0.969], self::BORDER);
        $this->text(66, $y + 26, 'Vérification Mesotravo', 11, 'F2', self::DARK);
        $this->text(66, $y + 11, 'Facture émise par Mesotravo. Vérification : ' . $verificationUrl, 8, 'F1', self::MUTED);
    }

    private function sectionLabel(string $label, float $y): void
    {
        $this->text(50, $y, strtoupper($label), 9, 'F2', self::MUTED);
        $this->line(50, $y - 9, 545, $y - 9, self::BORDER, 0.6);
    }

    private function twoColumnInfo(float $x1, float $y, string $label1, string $value1, float $x2, string $label2, string $value2): void
    {
        $this->text($x1, $y, $label1, 9, 'F1', self::MUTED);
        $this->text($x1, $y - 16, $this->shorten($value1, 30), 10, 'F2', self::DARK);
        $this->text($x2, $y, $label2, 9, 'F1', self::MUTED);
        $this->text($x2, $y - 16, $this->shorten($value2, 30), 10, 'F2', self::DARK);
    }

    private function infoRow(string $label, string $value, float $y): void
    {
        $this->text(50, $y, $label, 9, 'F1', self::MUTED);
        $this->textRight(545, $y, $value, 10, 'F2', self::DARK);
        $this->line(50, $y - 9, 545, $y - 9, self::BORDER, 0.5);
    }

    private function wrappedInfoRow(string $label, string $value, float $y): void
    {
        $this->text(50, $y, $label, 9, 'F1', self::MUTED);
        foreach ($this->wrap($value, 62) as $index => $line) {
            $this->textRight(545, $y - ($index * 13), $line, 9, $index === 0 ? 'F2' : 'F1', self::DARK);
        }
        $this->line(50, $y - 10, 545, $y - 10, self::BORDER, 0.5);
    }

    private function footer(): void
    {
        $this->line(50, 50, 545, 50, self::BORDER, 0.8);
        $this->textCentered(297.5, 34, 'Mesotravo - Courtier en travaux', 8, 'F1', [0.690, 0.627, 0.604]);
        $this->textCentered(297.5, 22, self::MESOTRAVO_PHONE . ' - ' . self::MESOTRAVO_EMAIL . ' - IFU : ' . self::MESOTRAVO_IFU, 8, 'F1', [0.690, 0.627, 0.604]);
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'diagnostic' => 'Diagnostic',
            'labor' => "Main d'œuvre",
            'material', 'part' => 'Pièces et matériaux',
            default => ucfirst($type),
        };
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

    private function shorten(string $text, int $length): string
    {
        $text = $this->cleanText($text);

        return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 1) . '…' : $text;
    }

    private function cleanText(string $text): string
    {
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private function formatPrice(float $value): string
    {
        return $this->formatNumber($value) . ' FCFA';
    }

    private function formatNumber(float $value): string
    {
        return number_format($value, 0, ',', ' ');
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
