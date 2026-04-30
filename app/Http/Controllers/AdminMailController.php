<?php

namespace App\Http\Controllers;

use App\Models\AdminMailTemplate;
use App\Models\AdminMailLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class AdminMailController extends Controller
{
    public function page()
    {
        $user = auth()->user();

        return view('pages.back.admin.mail', [
            'active' => 'mail',
            'user'   => $user,
            'routes' => [
                'users'             => route('admin.mail.users'),
                'templates_index'   => route('admin.mail.templates.index'),
                'templates_store'   => route('admin.mail.templates.store'),
                'templates_update'  => url('/admin/mail/templates/{template}'),
                'templates_destroy' => url('/admin/mail/templates/{template}'),
                'history'           => route('admin.mail.history'),
                'upload_image'      => route('admin.mail.upload-image'),
                'send'              => route('admin.mail.send'),
                'notifications'     => route('notifications.index'),
                'notifications_all' => route('notifications.read-all'),
            ],
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->whereIn('role', ['client', 'contractor', 'talent'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name', 'email', 'role', 'status'])
            ->map(fn (User $user) => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'role'   => $user->role,
                'status' => $user->status,
            ]);

        return response()->json($users);
    }

    public function templates(): JsonResponse
    {
        return response()->json(
            AdminMailTemplate::orderByDesc('is_default')
                ->orderBy('name')
                ->get()
        );
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $data = $this->validateTemplate($request);
        $template = AdminMailTemplate::create($data);

        return response()->json($template, 201);
    }

    public function updateTemplate(Request $request, AdminMailTemplate $template): JsonResponse
    {
        $template->update($this->validateTemplate($request));

        return response()->json($template->fresh());
    }

    public function destroyTemplate(AdminMailTemplate $template): JsonResponse
    {
        $template->delete();

        return response()->json(['message' => 'Template supprime.']);
    }

    public function history(): JsonResponse
    {
        $logs = AdminMailLog::with('sender:id,name,email')
            ->latest()
            ->limit(80)
            ->get()
            ->map(fn (AdminMailLog $log) => [
                'id'               => $log->id,
                'subject'          => $log->subject,
                'recipient_mode'   => $log->recipient_mode,
                'roles'            => $log->roles ?? [],
                'recipients_count' => $log->recipients_count,
                'sent_count'       => $log->sent_count,
                'failed_count'     => count($log->failed ?? []),
                'attachments'      => $log->attachments ?? [],
                'sender'           => $log->sender?->name,
                'created_at'       => $log->created_at?->format('d/m/Y H:i'),
            ]);

        return response()->json($logs);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:4096',
        ]);

        $file = $data['image'];
        $directory = public_path('uploads/admin-mails');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return response()->json([
            'url' => asset('uploads/admin-mails/' . $filename),
        ], 201);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject'        => 'required|string|max:180',
            'body'           => 'required|string|max:20000',
            'recipient_mode' => 'required|in:all,roles,selected',
            'roles'          => 'array',
            'roles.*'        => 'in:client,contractor,talent',
            'user_ids'       => 'array',
            'user_ids.*'     => 'integer|exists:users,id',
            'attachments'    => 'array',
            'attachments.*'  => 'file|max:10240',
        ]);

        $recipients = $this->recipientQuery($data)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'name', 'email', 'role']);

        if ($recipients->isEmpty()) {
            return response()->json(['message' => 'Aucun destinataire trouve.'], 422);
        }

        $sent = 0;
        $failed = [];
        $attachments = $this->storeAttachments($request);

        foreach ($recipients as $recipient) {
            try {
                $subject = $this->replacePlaceholders($data['subject'], $recipient);
                $body = $this->replacePlaceholders($data['body'], $recipient);
                $body = $this->sanitizeHtml($body);
                $html = view('emails.admin-mail', [
                    'body' => $body,
                ])->render();

                $this->sendWithPhpMailer($recipient, $subject, $html, $attachments);

                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $failed[] = [
                    'id'    => $recipient->id,
                    'email' => $recipient->email,
                ];
            }
        }

        AdminMailLog::create([
            'sent_by'          => auth()->id(),
            'subject'          => $data['subject'],
            'body'             => $this->sanitizeHtml($data['body']),
            'recipient_mode'   => $data['recipient_mode'],
            'roles'            => $data['roles'] ?? [],
            'user_ids'         => $data['user_ids'] ?? [],
            'recipients_count' => $recipients->count(),
            'sent_count'       => $sent,
            'failed'           => $failed,
            'attachments'      => collect($attachments)->map(fn ($file) => [
                'name' => $file['name'],
                'url'  => $file['url'],
                'mime' => $file['mime'],
            ])->values()->all(),
        ]);

        if ($sent === 0) {
            return response()->json([
                'message' => "Aucun mail n'a pu etre envoye. Verifiez les identifiants SMTP.",
                'sent'    => 0,
                'failed'  => $failed,
            ], 422);
        }

        return response()->json([
            'message' => "{$sent} mail(s) envoye(s).",
            'sent'    => $sent,
            'failed'  => $failed,
        ]);
    }

    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'name'       => 'required|string|max:120',
            'subject'    => 'required|string|max:180',
            'body'       => 'required|string|max:20000',
            'is_default' => 'boolean',
        ]);
    }

    private function recipientQuery(array $data)
    {
        $query = User::query()->whereIn('role', ['client', 'contractor', 'talent']);

        if ($data['recipient_mode'] === 'roles') {
            $roles = $data['roles'] ?? [];
            $query->whereIn('role', $roles ?: ['client', 'contractor', 'talent']);
        }

        if ($data['recipient_mode'] === 'selected') {
            $query->whereIn('id', $data['user_ids'] ?? []);
        }

        return $query->orderBy('name');
    }

    private function replacePlaceholders(string $text, User $user): string
    {
        return Str::of($text)
            ->replace('{name}', $user->name ?? '')
            ->replace('{email}', $user->email ?? '')
            ->replace('{role}', $user->role ?? '')
            ->toString();
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

    private function storeAttachments(Request $request): array
    {
        if (!$request->hasFile('attachments')) {
            return [];
        }

        $directory = public_path('uploads/admin-mails/attachments');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $stored = [];
        foreach ($request->file('attachments', []) as $file) {
            $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
            $file->move($directory, $filename);
            $stored[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $directory . DIRECTORY_SEPARATOR . $filename,
                'url'  => asset('uploads/admin-mails/attachments/' . $filename),
                'mime' => $file->getClientMimeType() ?: 'application/octet-stream',
            ];
        }

        return $stored;
    }

    /**
     * Envoi SMTP direct via PHPMailer.
     */
    private function sendWithPhpMailer(User $recipient, string $subject, string $html, array $attachments): void
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

            $fromAddress = (string) $this->smtpValue('FROM_ADDRESS', config('mail.from.address'));
            $fromName = (string) $this->smtpValue('FROM_NAME', config('mail.from.name'));

            $mailer->setFrom($fromAddress, $fromName);
            $mailer->addAddress($recipient->email, $recipient->name);
            $mailer->Subject = $subject;
            $mailer->isHTML(true);
            $mailer->Body = $html;
            $mailer->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)));

            foreach ($attachments as $attachment) {
                $mailer->addAttachment($attachment['path'], $attachment['name']);
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
