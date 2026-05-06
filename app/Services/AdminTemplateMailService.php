<?php

namespace App\Services;

use App\Models\AdminMailLog;
use App\Models\AdminMailTemplate;
use App\Models\User;
use Illuminate\Support\Str;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class AdminTemplateMailService
{
    private const ADMIN_REGISTRATION_EMAIL = 'contact@mesotravo.com';

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

        return Str::of($text)
            ->replace('{name}', $name)
            ->replace('{first_name}', $firstName)
            ->replace('{last_name}', $lastName)
            ->replace('{email}', $recipient['email'] ?? '')
            ->replace('{role}', $recipient['role'] ?? '')
            ->replace('{url}', $recipient['url'] ?? '')
            ->replace('{current_date}', $this->frenchCurrentDate())
            ->toString();
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

    private function sendWithPhpMailer(string $recipientEmail, ?string $recipientName, string $subject, string $html): void
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
