<?php

namespace App\Services;

use App\Models\AdminMailLog;
use App\Models\AdminMailTemplate;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Support\Str;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class AdminTemplateMailService
{
    private const ADMIN_REGISTRATION_EMAIL = 'contact@mesotravo.com';
    private const QUOTE_SUBMISSION_TEMPLATE = 'Soumission devis';
    private const INVOICE_SUBMISSION_TEMPLATE = 'Soumission de la facture';
    private const INVOICE_SUBMISSION_TEMPLATE_FALLBACK = 'Soumission de la fatcure';
    private const CLIENT_RECEIPT_AFTER_MISSION_TEMPLATE = 'Reçu du client après la mission';

    public function sendQuoteSubmissionMail(Mission $mission): void
    {
        $mission->loadMissing(['client.user', 'contractor.user', 'quote.items', 'reservation']);

        $clientUser = $mission->client?->user;
        if (!$clientUser?->email || !$mission->quote) {
            return;
        }

        $contractorName = trim(($mission->contractor->first_name ?? '') . ' ' . ($mission->contractor->last_name ?? '')) ?: ($mission->contractor?->user?->name ?? '');
        $clientName = trim(($mission->client->first_name ?? '') . ' ' . ($mission->client->last_name ?? '')) ?: $clientUser->name;
        $missionUrl = url("/client/missions/{$mission->id}");
        $amount = number_format((float) $mission->quote->amount_incl_tax, 0, ',', ' ');

        $recipient = [
            'id' => $clientUser->id,
            'name' => $clientName,
            'email' => $clientUser->email,
            'role' => $clientUser->role,
            'url' => $missionUrl,
            'mission_id' => $mission->id,
            'service' => ucfirst((string) $mission->service),
            'amount' => $amount,
            'total' => $amount,
            'quote_amount' => $amount,
            'contractor_name' => $contractorName,
            'client_name' => $clientName,
            'quote_version' => $mission->quote->version ?? 1,
        ];

        $template = $this->findMailTemplate(self::QUOTE_SUBMISSION_TEMPLATE);

        if ($template) {
            $subject = $this->replacePlaceholders($template->subject, $recipient);
            $body = $this->sanitizeHtml($this->replacePlaceholders($template->body, $recipient));
        } else {
            [$subject, $body] = $this->defaultQuoteSubmissionContent($recipient);
        }

        $html = view('emails.admin-mail', ['body' => $body])->render();
        $pdfName = 'devis-mesotravo-' . str_pad((string) $mission->id, 6, '0', STR_PAD_LEFT) . '.pdf';
        $attachments = [[
            'name' => $pdfName,
            'data' => app(QuotePdfService::class)->make($mission),
            'mime' => 'application/pdf',
        ]];

        $sent = 0;
        $failed = [];

        try {
            $this->sendWithPhpMailer($clientUser->email, $clientUser->name, $subject, $html, $attachments);
            $sent = 1;
        } catch (\Throwable $e) {
            report($e);
            $failed[] = [
                'id' => $clientUser->id,
                'email' => $clientUser->email,
                'error' => $e->getMessage(),
            ];
        }

        AdminMailLog::create([
            'sent_by'           => null,
            'subject'           => $subject,
            'body'              => $body,
            'recipient_mode'    => 'selected',
            'roles'             => [],
            'user_ids'          => [$clientUser->id],
            'manual_recipients' => [],
            'recipients_count'  => 1,
            'sent_count'        => $sent,
            'failed'            => $failed,
            'attachments'       => [['name' => $pdfName, 'type' => 'application/pdf']],
        ]);
    }

    public function sendInvoiceSubmissionMail(Mission $mission): void
    {
        $mission->loadMissing(['client.user', 'contractor.user', 'quote.items', 'reservation']);

        $clientUser = $mission->client?->user;
        if (!$clientUser?->email) {
            return;
        }

        $contractorName = trim(($mission->contractor->first_name ?? '') . ' ' . ($mission->contractor->last_name ?? '')) ?: ($mission->contractor?->user?->name ?? '');
        $clientName = trim(($mission->client->first_name ?? '') . ' ' . ($mission->client->last_name ?? '')) ?: $clientUser->name;
        $invoiceUrl = route('client.missions.invoice', $mission);
        $amount = number_format((float) ($mission->total_amount ?? $mission->quote?->amount_incl_tax ?? 0), 0, ',', ' ');

        $recipient = [
            'id' => $clientUser->id,
            'name' => $clientName,
            'email' => $clientUser->email,
            'role' => $clientUser->role,
            'url' => $invoiceUrl,
            'mission_id' => $mission->id,
            'service' => ucfirst((string) $mission->service),
            'amount' => $amount,
            'total' => $amount,
            'invoice_amount' => $amount,
            'contractor_name' => $contractorName,
            'client_name' => $clientName,
            'quote_version' => $mission->quote?->version ?? 1,
        ];

        $template = $this->findMailTemplate(self::INVOICE_SUBMISSION_TEMPLATE)
            ?? $this->findMailTemplate(self::INVOICE_SUBMISSION_TEMPLATE_FALLBACK);

        if ($template) {
            $subject = $this->replacePlaceholders($template->subject, $recipient);
            $body = $this->sanitizeHtml($this->replacePlaceholders($template->body, $recipient));
        } else {
            [$subject, $body] = $this->defaultInvoiceSubmissionContent($recipient);
        }

        $html = view('emails.admin-mail', ['body' => $body])->render();
        $pdfName = 'facture-mesotravo-' . str_pad((string) $mission->id, 6, '0', STR_PAD_LEFT) . '.pdf';
        $attachments = [[
            'name' => $pdfName,
            'data' => app(InvoicePdfService::class)->make($mission),
            'mime' => 'application/pdf',
        ]];

        $sent = 0;
        $failed = [];

        try {
            $this->sendWithPhpMailer($clientUser->email, $clientUser->name, $subject, $html, $attachments);
            $sent = 1;
        } catch (\Throwable $e) {
            report($e);
            $failed[] = [
                'id' => $clientUser->id,
                'email' => $clientUser->email,
                'error' => $e->getMessage(),
            ];
        }

        AdminMailLog::create([
            'sent_by'           => null,
            'subject'           => $subject,
            'body'              => $body,
            'recipient_mode'    => 'selected',
            'roles'             => [],
            'user_ids'          => [$clientUser->id],
            'manual_recipients' => [],
            'recipients_count'  => 1,
            'sent_count'        => $sent,
            'failed'            => $failed,
            'attachments'       => [['name' => $pdfName, 'type' => 'application/pdf']],
        ]);
    }

    public function sendClientReceiptAfterMissionMail(Mission $mission): void
    {
        $mission->loadMissing(['client.user', 'contractor.user', 'quote.items', 'reservation']);

        $clientUser = $mission->client?->user;
        if (!$clientUser?->email || !$mission->paid_at) {
            return;
        }

        $contractorName = trim(($mission->contractor->first_name ?? '') . ' ' . ($mission->contractor->last_name ?? '')) ?: ($mission->contractor?->user?->name ?? '');
        $clientName = trim(($mission->client->first_name ?? '') . ' ' . ($mission->client->last_name ?? '')) ?: $clientUser->name;
        $receiptUrl = route('client.missions.receipt', $mission);
        $amount = number_format((float) ($mission->total_amount ?? 0), 0, ',', ' ');

        $recipient = [
            'id' => $clientUser->id,
            'name' => $clientName,
            'email' => $clientUser->email,
            'role' => $clientUser->role,
            'url' => $receiptUrl,
            'mission_id' => $mission->id,
            'service' => ucfirst((string) $mission->service),
            'amount' => $amount,
            'total' => $amount,
            'receipt_amount' => $amount,
            'contractor_name' => $contractorName,
            'client_name' => $clientName,
            'paid_at' => $mission->paid_at?->format('d/m/Y H:i') ?? '',
        ];

        $template = $this->findMailTemplate(self::CLIENT_RECEIPT_AFTER_MISSION_TEMPLATE);

        if ($template) {
            $subject = $this->replacePlaceholders($template->subject, $recipient);
            $body = $this->sanitizeHtml($this->replacePlaceholders($template->body, $recipient));
        } else {
            [$subject, $body] = $this->defaultClientReceiptAfterMissionContent($recipient);
        }

        $html = view('emails.admin-mail', ['body' => $body])->render();
        $pdfName = 'recu-paiement-resotravo-' . str_pad((string) $mission->id, 6, '0', STR_PAD_LEFT) . '.pdf';
        $attachments = [[
            'name' => $pdfName,
            'data' => app(ReceiptPdfService::class)->make($mission),
            'mime' => 'application/pdf',
        ]];

        $sent = 0;
        $failed = [];

        try {
            $this->sendWithPhpMailer($clientUser->email, $clientUser->name, $subject, $html, $attachments);
            $sent = 1;
        } catch (\Throwable $e) {
            report($e);
            $failed[] = [
                'id' => $clientUser->id,
                'email' => $clientUser->email,
                'error' => $e->getMessage(),
            ];
        }

        AdminMailLog::create([
            'sent_by'           => null,
            'subject'           => $subject,
            'body'              => $body,
            'recipient_mode'    => 'selected',
            'roles'             => [],
            'user_ids'          => [$clientUser->id],
            'manual_recipients' => [],
            'recipients_count'  => 1,
            'sent_count'        => $sent,
            'failed'            => $failed,
            'attachments'       => [['name' => $pdfName, 'type' => 'application/pdf']],
        ]);
    }

    public function sendWelcomeMail(User $user, string $templateName, ?string $url = null): void
    {
        if (!$user->email) {
            return;
        }

        $template = $this->findMailTemplate($templateName);

        $recipient = [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
            'url'   => $url ?? '',
        ];

        if ($template) {
            $subject = $this->replacePlaceholders($template->subject, $recipient);
            $body = $this->sanitizeHtml($this->replacePlaceholders($template->body, $recipient));
        } else {
            [$subject, $body] = $this->defaultWelcomeContent($user, $recipient);
        }

        $html = view('emails.admin-mail', ['body' => $body])->render();
        $sent = 0;
        $failed = [];

        try {
            $this->sendWithPhpMailer($user->email, $user->name, $subject, $html);
            $sent = 1;
        } catch (\Throwable $e) {
            report($e);
            $failed[] = [
                'id'    => $user->id,
                'email' => $user->email,
            ];
        }

        AdminMailLog::create([
            'sent_by'           => null,
            'subject'           => $subject,
            'body'              => $body,
            'recipient_mode'    => 'selected',
            'roles'             => [],
            'user_ids'          => [$user->id],
            'manual_recipients' => [],
            'recipients_count'  => 1,
            'sent_count'        => $sent,
            'failed'            => $failed,
            'attachments'       => [],
        ]);
    }

    public function sendPasswordResetMail(User $user, string $token): void
    {
        if (!$user->email) {
            return;
        }

        $url = route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]);

        $recipient = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'url' => $url,
        ];

        $subject = 'Réinitialisation de votre mot de passe Mesotravo';
        $body = $this->sanitizeHtml($this->replacePlaceholders(
            '<p>Bonjour {first_name},</p>'
            . '<p>Vous avez demandé la réinitialisation de votre mot de passe Mesotravo.</p>'
            . '<p>Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe.</p>'
            . '<p><a href="{url}" style="display:inline-block;background:#f97316;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:700;">Réinitialiser mon mot de passe</a></p>'
            . '<p>Ce lien est valable pendant 60 minutes.</p>'
            . "<p>Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email.</p>",
            $recipient
        ));
        $html = view('emails.admin-mail', ['body' => $body])->render();
        $sent = 0;
        $failed = [];

        try {
            $this->sendWithPhpMailer($user->email, $user->name, $subject, $html);
            $sent = 1;
        } catch (\Throwable $e) {
            report($e);
            $failed[] = [
                'id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ];
        }

        AdminMailLog::create([
            'sent_by' => null,
            'subject' => $subject,
            'body' => $body,
            'recipient_mode' => 'selected',
            'roles' => [],
            'user_ids' => [$user->id],
            'manual_recipients' => [],
            'recipients_count' => 1,
            'sent_count' => $sent,
            'failed' => $failed,
            'attachments' => [],
        ]);

        if ($sent !== 1) {
            throw new \RuntimeException("Le mail de réinitialisation n'a pas pu être envoyé.");
        }
    }

    private function findMailTemplate(string $templateName): ?AdminMailTemplate
    {
        $template = AdminMailTemplate::where('name', $templateName)->first();
        if ($template) {
            return $template;
        }

        $normalizedTarget = $this->normalizeTemplateName($templateName);

        return AdminMailTemplate::query()
            ->get()
            ->first(function (AdminMailTemplate $item) use ($normalizedTarget) {
                return $this->normalizeTemplateName((string) $item->name) === $normalizedTarget;
            });
    }

    private function normalizeTemplateName(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }

    private function defaultWelcomeContent(User $user, array $recipient): array
    {
        $isContractor = $user->role === 'contractor';
        $subject = $isContractor
            ? 'Bienvenue sur Mesotravo'
            : 'Bienvenue sur Mesotravo';

        $body = $isContractor
            ? '<p>Bonjour {first_name},</p><p>Votre compte prestataire Mesotravo a bien été créé.</p><p>Vous pouvez maintenant accéder à votre tableau de bord et finaliser votre profil.</p><p><a href="{url}">Accéder à mon tableau de bord</a></p>'
            : '<p>Bonjour {first_name},</p><p>Votre compte client Mesotravo a bien été créé.</p><p>Vous pouvez maintenant accéder à votre tableau de bord et publier vos besoins.</p><p><a href="{url}">Accéder à mon tableau de bord</a></p>';

        return [
            $this->replacePlaceholders($subject, $recipient),
            $this->sanitizeHtml($this->replacePlaceholders($body, $recipient)),
        ];
    }

    private function defaultQuoteSubmissionContent(array $recipient): array
    {
        $subject = 'Nouveau devis Mesotravo - Mission #' . ($recipient['mission_id'] ?? '');
        $body = '<p>Bonjour {first_name},</p>'
            . '<p>Un devis vient d&apos;etre soumis pour votre mission <strong>{service}</strong>.</p>'
            . '<p>Montant total : <strong>{amount} FCFA</strong>.</p>'
            . '<p>Le devis est joint a cet email en PDF. Vous pouvez aussi le consulter et l&apos;approuver depuis votre espace client.</p>'
            . '<p><a href="{url}">Consulter mon devis</a></p>';

        return [
            $this->replacePlaceholders($subject, $recipient),
            $this->sanitizeHtml($this->replacePlaceholders($body, $recipient)),
        ];
    }

    private function defaultInvoiceSubmissionContent(array $recipient): array
    {
        $subject = 'Votre facture Mesotravo - Mission #' . ($recipient['mission_id'] ?? '');
        $body = '<p>Bonjour {first_name},</p>'
            . '<p>Votre devis pour la mission <strong>{service}</strong> a ete accepte.</p>'
            . '<p>Montant de la facture : <strong>{amount} FCFA</strong>.</p>'
            . '<p>La facture exacte de votre mission est jointe a cet email en PDF. Vous pouvez aussi la consulter depuis votre espace client.</p>'
            . '<p><a href="{url}">Consulter ma facture</a></p>';

        return [
            $this->replacePlaceholders($subject, $recipient),
            $this->sanitizeHtml($this->replacePlaceholders($body, $recipient)),
        ];
    }

    private function defaultClientReceiptAfterMissionContent(array $recipient): array
    {
        $subject = 'Votre reçu Mesotravo - Mission #' . ($recipient['mission_id'] ?? '');
        $body = '<p>Bonjour {first_name},</p>'
            . '<p>Votre paiement pour la mission <strong>{service}</strong> a bien ete confirme.</p>'
            . '<p>Montant paye : <strong>{amount} FCFA</strong>.</p>'
            . '<p>Votre recu est joint a cet email en PDF. Vous pouvez aussi le consulter depuis votre espace client.</p>'
            . '<p><a href="{url}">Consulter mon recu</a></p>';

        return [
            $this->replacePlaceholders($subject, $recipient),
            $this->sanitizeHtml($this->replacePlaceholders($body, $recipient)),
        ];
    }

    public function sendAdminRegistrationNotification(User $user): void
    {
        $user->loadMissing(['client', 'contractor.service']);

        $subject = 'Nouvelle inscription utilisateur - ' . $user->name;
        $body = $this->buildAdminRegistrationBody($user);
        $html = view('emails.admin-mail', ['body' => $body])->render();
        $sent = 0;
        $failed = [];

        try {
            $this->sendWithPhpMailer(self::ADMIN_REGISTRATION_EMAIL, 'Mesotravo Admin', $subject, $html);
            $sent = 1;
        } catch (\Throwable $e) {
            report($e);
            $failed[] = [
                'email' => self::ADMIN_REGISTRATION_EMAIL,
                'error' => $e->getMessage(),
            ];
        }

        AdminMailLog::create([
            'sent_by'           => null,
            'subject'           => $subject,
            'body'              => $body,
            'recipient_mode'    => 'manual',
            'roles'             => [],
            'user_ids'          => [$user->id],
            'manual_recipients' => [self::ADMIN_REGISTRATION_EMAIL],
            'recipients_count'  => 1,
            'sent_count'        => $sent,
            'failed'            => $failed,
            'attachments'       => [],
        ]);
    }

    private function buildAdminRegistrationBody(User $user): string
    {
        $profile = $user->role === 'contractor' ? $user->contractor : $user->client;
        $roleLabel = $user->role === 'contractor' ? 'Prestataire' : 'Client';
        $rows = [
            'Nom complet' => $user->name,
            'Email' => $user->email,
            'Rôle' => $roleLabel,
            'Statut' => $user->status,
            "Date d'inscription" => now()->format('d/m/Y à H:i'),
        ];

        if ($profile) {
            $rows['Téléphone'] = $profile->phone ?? '';
            $rows['Ville'] = $profile->city ?? '';
        }

        if ($user->client) {
            $rows['Type de compte'] = $user->client->account_type === 'company' ? 'Entreprise' : 'Particulier';
            $rows['Entreprise'] = $user->client->company_name ?? '';
            $rows['Adresse'] = $user->client->address ?? '';
        }

        if ($user->contractor) {
            $rows['Service'] = $user->contractor->service?->name ?? '';
            $rows['Spécialité'] = $user->contractor->specialty ?? '';
            $rows["Zone d'intervention"] = $user->contractor->intervention_zone ?? '';
            $rows["Années d'expérience"] = (string) ($user->contractor->experience_years ?? '');
            $rows['Bio'] = $user->contractor->bio ?? '';
            $rows['Accréditation'] = $user->contractor->accreditation ?? '';
        }

        $tableRows = '';
        foreach ($rows as $label => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $tableRows .= '<tr>'
                . '<td style="padding:8px 12px;border:1px solid #e8ddd4;font-weight:700;color:#1c1412;">' . e($label) . '</td>'
                . '<td style="padding:8px 12px;border:1px solid #e8ddd4;color:#4b5563;">' . e((string) $value) . '</td>'
                . '</tr>';
        }

        return '<p>Bonjour,</p>'
            . '<p>Un nouvel utilisateur vient de s’inscrire sur Mesotravo. Voici les détails :</p>'
            . '<table style="border-collapse:collapse;width:100%;max-width:680px;">' . $tableRows . '</table>';
    }

    private function replacePlaceholders(string $text, array $recipient): string
    {
        $name = trim((string) ($recipient['name'] ?? ''));
        $nameParts = preg_split('/\s+/', $name) ?: [];
        $firstName = $nameParts[0] ?? '';
        $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

        $result = Str::of($text)
            ->replace('{name}', $name)
            ->replace('{first_name}', $firstName)
            ->replace('{last_name}', $lastName)
            ->replace('{email}', $recipient['email'] ?? '')
            ->replace('{role}', $recipient['role'] ?? '')
            ->replace('{url}', $recipient['url'] ?? '')
            ->replace('{current_date}', $this->frenchCurrentDate())
            ->toString();

        foreach ($recipient as $key => $value) {
            if (is_scalar($value)) {
                $result = str_replace('{' . $key . '}', (string) $value, $result);
            }
        }

        return $result;
    }

    private function frenchCurrentDate(): string
    {
        $date = now();
        $months = [
            1 => 'Janvier',
            2 => 'Fevrier',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Aout',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Decembre',
        ];

        return $date->format('d') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
    }

    private function sanitizeHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $allowedTags = [
            'a', 'b', 'blockquote', 'br', 'div', 'em', 'font', 'h1', 'h2', 'h3',
            'hr', 'i', 'img', 'li', 'ol', 'p', 'span', 'strong', 'table', 'tbody',
            'td', 'th', 'thead', 'tr', 'u', 'ul',
        ];
        $allowedAttributes = ['href', 'src', 'alt', 'title', 'style', 'target', 'rel', 'face', 'size', 'color'];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $nodes = iterator_to_array($dom->getElementsByTagName('*'));
        foreach ($nodes as $node) {
            if ($node->tagName === 'body') {
                continue;
            }

            if (!in_array(strtolower($node->tagName), $allowedTags, true)) {
                $node->parentNode?->removeChild($node);
                continue;
            }

            foreach (iterator_to_array($node->attributes ?? []) as $attribute) {
                $name = strtolower($attribute->name);
                $value = trim($attribute->value);

                if (!in_array($name, $allowedAttributes, true)) {
                    $node->removeAttribute($attribute->name);
                    continue;
                }

                if (in_array($name, ['href', 'src'], true) && !$this->isSafeMailUrl($value)) {
                    $node->removeAttribute($attribute->name);
                    continue;
                }

                if ($name === 'style') {
                    $style = preg_replace('/expression\s*\(|javascript:|url\s*\(/i', '', $value);
                    $node->setAttribute('style', $style ?? '');
                }
            }

            if ($node->tagName === 'a') {
                $node->setAttribute('target', '_blank');
                $node->setAttribute('rel', 'noopener');
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $clean = '';
        foreach ($body?->childNodes ?? [] as $child) {
            $clean .= $dom->saveHTML($child);
        }

        return $clean;
    }

    private function isSafeMailUrl(string $url): bool
    {
        return preg_match('/^(https?:\/\/|mailto:|tel:)/i', $url) === 1;
    }

    private function sendWithPhpMailer(string $recipientEmail, ?string $recipientName, string $subject, string $html, array $attachments = []): void
    {
        $mailer = new PHPMailer(true);

        try {
            $mailer->isSMTP();
            $mailer->Host = (string) $this->smtpValue('HOST', config('mail.mailers.smtp.host'));
            $mailer->SMTPAuth = true;
            $mailer->Username = (string) $this->smtpValue('USERNAME', config('mail.mailers.smtp.username'));
            $mailer->Password = (string) $this->smtpValue('PASSWORD', config('mail.mailers.smtp.password'));
            $mailer->Port = (int) $this->smtpValue('PORT', config('mail.mailers.smtp.port'));
            $mailer->CharSet = 'UTF-8';

            $secure = (string) $this->smtpValue('SECURE', config('mail.mailers.smtp.scheme'));
            if ($secure === 'smtps' || $secure === 'ssl' || $mailer->Port === 465) {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls' || $secure === 'starttls' || $mailer->Port === 587) {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $localDomain = $this->smtpValue('EHLO_DOMAIN', config('mail.mailers.smtp.local_domain'));
            if ($localDomain) {
                $mailer->Hostname = (string) $localDomain;
            }

            $mailer->setFrom(
                (string) $this->smtpValue('FROM_ADDRESS', config('mail.from.address')),
                (string) $this->smtpValue('FROM_NAME', config('mail.from.name'))
            );
            $mailer->addAddress($recipientEmail, $recipientName ?? '');
            $mailer->Subject = $subject;
            $mailer->isHTML(true);
            $mailer->Body = $html;
            $mailer->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)));

            foreach ($attachments as $attachment) {
                if (isset($attachment['data'])) {
                    $mailer->addStringAttachment(
                        $attachment['data'],
                        $attachment['name'] ?? 'piece-jointe.pdf',
                        PHPMailer::ENCODING_BASE64,
                        $attachment['mime'] ?? 'application/octet-stream'
                    );
                } elseif (isset($attachment['path'])) {
                    $mailer->addAttachment($attachment['path'], $attachment['name'] ?? '');
                }
            }

            $mailer->send();
        } catch (PHPMailerException $e) {
            throw new \RuntimeException($mailer->ErrorInfo ?: $e->getMessage(), previous: $e);
        }
    }

    private function smtpValue(string $key, mixed $default = null): mixed
    {
        $phpmailerValue = env("PHPMAILER_{$key}");
        if ($phpmailerValue !== null && $phpmailerValue !== '') {
            return $phpmailerValue;
        }

        $smtpValue = env("SMTP_{$key}");
        if ($smtpValue !== null && $smtpValue !== '') {
            return $smtpValue;
        }

        return $default;
    }
}
