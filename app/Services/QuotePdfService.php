<?php

namespace App\Services;

use App\Models\Mission;

class QuotePdfService
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const MESOTRAVO_PHONE = '+229 01 90 00 36 26';
    private const MESOTRAVO_EMAIL = 'contact@mesotravo.com';
    private const MESOTRAVO_IFU = '3202625062491';

    private array $pages = [];
    private array $commands = [];
    private float $y = 0;

    public function make(Mission $mission): string
    {
        $mission->loadMissing(['client.user', 'contractor.user', 'quote.items', 'reservation']);

        $this->pages = [];
        $this->commands = [];
        $this->startPage();

        $quote = $mission->quote;
        $clientName = trim(($mission->client->first_name ?? '') . ' ' . ($mission->client->last_name ?? ''))
            ?: ($mission->client?->user?->name ?? '-');
        $contractorName = trim(($mission->contractor->first_name ?? '') . ' ' . ($mission->contractor->last_name ?? ''))
            ?: ($mission->contractor?->user?->name ?? '-');
        $issuedAt = now()->format('d/m/Y H:i');
        $missionSlot = $mission->reservation
            ? 'Mission prévue le ' . $mission->reservation->day->format('d/m/Y') . ' à ' . substr((string) $mission->reservation->time, 0, 5)
            : null;

        $this->header($mission, $quote, $issuedAt, $missionSlot);
        $this->sectionLabel("Détails de l'intervention");
        $this->infoRow('Destinataire', $clientName);
        $this->infoRow('Adresse', (string) $mission->address);
        $this->infoRow('Prestataire', $contractorName);

        if ($quote?->diagnosis) {
            $this->diagnosisBox((string) $quote->diagnosis);
        }

        $this->sectionLabel('Lignes du devis');
        $this->quoteTable($quote?->items ?? [], (float) ($quote?->amount_incl_tax ?? $mission->total_amount ?? 0));
        $this->noteBox("Ce devis est soumis à approbation via Mesotravo. Aucun paiement hors Mesotravo n'est autorisé ni conseillé.");
        $this->footer();
        $this->finishPage();

        return $this->buildPdf($this->pages);
    }

    private function startPage(): void
    {
        $this->commands = [];
        $this->y = 790;
    }

    private function finishPage(): void
    {
        if ($this->commands !== []) {
            $this->pages[] = implode("\n", $this->commands);
        }
    }

    private function newPage(): void
    {
        $this->finishPage();
        $this->startPage();
    }

    private function ensureSpace(float $needed): void
    {
        if ($this->y < $needed) {
            $this->footer();
            $this->newPage();
        }
    }

    private function header(Mission $mission, mixed $quote, string $issuedAt, ?string $missionSlot): void
    {
        $this->text(50, 790, 'Meso', 24, 'F2', [0.976, 0.451, 0.086]);
        $this->text(112, 790, 'Travo', 24, 'F2', [0.110, 0.078, 0.071]);
        $this->text(50, 770, 'Courtier en travaux', 10, 'F1', [0.486, 0.416, 0.353]);
        $this->text(50, 755, 'IFU : ' . self::MESOTRAVO_IFU, 10, 'F1', [0.486, 0.416, 0.353]);
        $this->text(50, 740, self::MESOTRAVO_PHONE, 10, 'F1', [0.486, 0.416, 0.353]);
        $this->text(50, 725, self::MESOTRAVO_EMAIL, 10, 'F1', [0.486, 0.416, 0.353]);

        $quoteTitle = 'Devis n°' . ($quote?->id ?? str_pad((string) $mission->id, 6, '0', STR_PAD_LEFT));
        if (($quote?->version ?? 1) > 1) {
            $quoteTitle .= ' - Révision v' . $quote->version;
        }

        $this->textRight(545, 790, $quoteTitle, 14, 'F2', [0.110, 0.078, 0.071]);
        $this->textRight(545, 770, 'Mission #' . $mission->id . ' - ' . ucfirst((string) $mission->service), 10, 'F1', [0.486, 0.416, 0.353]);
        $this->textRight(545, 755, 'Émis le ' . $issuedAt, 10, 'F1', [0.486, 0.416, 0.353]);
        if ($missionSlot) {
            $this->textRight(545, 740, $missionSlot, 10, 'F1', [0.486, 0.416, 0.353]);
        }

        $this->line(50, 705, 545, 705, [0.976, 0.451, 0.086], 1.6);
        $this->y = 680;
    }

    private function sectionLabel(string $label): void
    {
        $this->ensureSpace(90);
        $this->text(50, $this->y, strtoupper($label), 9, 'F2', [0.486, 0.416, 0.353]);
        $this->y -= 20;
    }

    private function infoRow(string $label, string $value): void
    {
        $wrapped = $this->wrap($value, 74);
        $this->text(50, $this->y, $label . ' :', 11, 'F1', [0.486, 0.416, 0.353]);
        $this->text(122, $this->y, $wrapped[0], 11, 'F2', [0.110, 0.078, 0.071]);
        $this->y -= 16;

        foreach (array_slice($wrapped, 1) as $line) {
            $this->text(122, $this->y, $line, 10, 'F1', [0.110, 0.078, 0.071]);
            $this->y -= 14;
        }
    }

    private function diagnosisBox(string $diagnosis): void
    {
        $lines = $this->wrap($diagnosis, 88);
        $height = 28 + count($lines) * 13;
        $this->ensureSpace($height + 70);

        $top = $this->y - 2;
        $this->rect(50, $top - $height, 495, $height, [1, 0.969, 0.929], null);
        $this->rect(50, $top - $height, 3, $height, [0.976, 0.451, 0.086], null);
        $this->text(64, $top - 18, 'Diagnostic', 10, 'F2', [0.976, 0.451, 0.086]);

        $lineY = $top - 34;
        foreach ($lines as $line) {
            $this->text(64, $lineY, $line, 10, 'F1', [0.486, 0.416, 0.353]);
            $lineY -= 13;
        }

        $this->y = $top - $height - 18;
    }

    private function quoteTable(iterable $items, float $totalAmount): void
    {
        $this->ensureSpace(145);
        $left = 50;
        $width = 495;
        $rowHeight = 34;

        $this->rect($left, $this->y - 20, $width, 24, [0.973, 0.957, 0.941], null);
        $this->text(62, $this->y - 12, 'DESIGNATION', 9, 'F2', [0.486, 0.416, 0.353]);
        $this->text(330, $this->y - 12, 'QTE', 9, 'F2', [0.486, 0.416, 0.353]);
        $this->textRight(442, $this->y - 12, 'PRIX UNITAIRE', 9, 'F2', [0.486, 0.416, 0.353]);
        $this->textRight(532, $this->y - 12, 'TOTAL', 9, 'F2', [0.486, 0.416, 0.353]);
        $this->y -= 34;

        foreach ($items as $item) {
            $descriptionLines = $this->wrap((string) $item->description, 38);
            $height = max($rowHeight, 20 + count($descriptionLines) * 13);
            $this->ensureSpace($height + 95);

            $lineTotal = (float) $item->quantity * (float) $item->unit_price;
            $rowTop = $this->y;
            $badge = $this->typeBadge((string) $item->type);

            $this->rect(62, $rowTop - 16, 62, 16, $badge['color'], null);
            $this->text(69, $rowTop - 12, $badge['label'], 8, 'F2', [1, 1, 1]);

            $descY = $rowTop - 12;
            foreach ($descriptionLines as $index => $line) {
                $this->text(132, $descY - ($index * 13), $line, 10, 'F1', [0.110, 0.078, 0.071]);
            }

            $this->text(336, $rowTop - 12, $this->formatNumber((float) $item->quantity), 10, 'F1', [0.110, 0.078, 0.071]);
            $this->textRight(442, $rowTop - 12, $this->formatPrice((float) $item->unit_price), 10, 'F1', [0.110, 0.078, 0.071]);
            $this->textRight(532, $rowTop - 12, $this->formatPrice($lineTotal), 10, 'F2', [0.110, 0.078, 0.071]);

            $this->line($left, $rowTop - $height + 6, $left + $width, $rowTop - $height + 6, [0.941, 0.914, 0.894], 0.8);
            $this->y -= $height;
        }

        $this->line($left, $this->y, $left + $width, $this->y, [0.976, 0.451, 0.086], 1.6);
        $this->y -= 22;
        $this->text(62, $this->y, 'Total TTC', 13, 'F2', [0.110, 0.078, 0.071]);
        $this->textRight(532, $this->y, $this->formatPrice($totalAmount), 13, 'F2', [0.976, 0.451, 0.086]);
        $this->y -= 32;
    }

    private function noteBox(string $note): void
    {
        $lines = $this->wrap($note, 95);
        $height = 20 + count($lines) * 13;
        $this->ensureSpace($height + 65);
        $this->rect(50, $this->y - $height, 495, $height, [0.973, 0.957, 0.941], null);
        $lineY = $this->y - 17;
        foreach ($lines as $line) {
            $this->text(64, $lineY, $line, 9, 'F1', [0.486, 0.416, 0.353]);
            $lineY -= 13;
        }
        $this->y -= $height + 24;
    }

    private function footer(): void
    {
        $this->line(50, 50, 545, 50, [0.910, 0.867, 0.831], 0.8);
        $this->textCentered(297.5, 34, 'Mesotravo.com - Courtier en travaux', 8, 'F1', [0.690, 0.627, 0.604]);
        $this->textCentered(297.5, 22, self::MESOTRAVO_PHONE . ' - ' . self::MESOTRAVO_EMAIL . ' - IFU : ' . self::MESOTRAVO_IFU, 8, 'F1', [0.690, 0.627, 0.604]);
    }

    private function typeBadge(string $type): array
    {
        return match ($type) {
            'diagnostic' => ['label' => 'Diagnostic', 'color' => [0.976, 0.451, 0.086]],
            'labor' => ['label' => "Main d'œuvre", 'color' => [0.063, 0.725, 0.506]],
            'part' => ['label' => 'Fourniture', 'color' => [0.231, 0.510, 0.965]],
            'travel' => ['label' => 'Déplacement', 'color' => [0.545, 0.361, 0.965]],
            default => ['label' => 'Autre', 'color' => [0.420, 0.447, 0.502]],
        };
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
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($text === '') {
            return ['-'];
        }

        return explode("\n", wordwrap($text, $length, "\n", true));
    }

    private function formatNumber(float $value): string
    {
        return number_format($value, 0, ',', ' ');
    }

    private function formatPrice(float $value): string
    {
        return $this->formatNumber($value) . ' FCFA';
    }

    private function pdfText(string $text): string
    {
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

        foreach ($pages as $pageContent) {
            $stream = "<< /Length " . strlen($pageContent) . " >>\nstream\n" . $pageContent . "\nendstream";
            $contentObjectNumber = count($objects) + 1;
            $objects[] = $stream;

            $pageObjectNumber = count($objects) + 1;
            $pageObjectNumbers[] = $pageObjectNumber;
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentObjectNumber . ' 0 R >>';
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
