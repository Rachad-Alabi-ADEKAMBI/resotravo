<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Classe unique pour toutes les notifications Mesotravo.
 *
 * On ne peut pas nommer cette classe "Notification" car ce nom
 * est réservé par Laravel. "AppNotification" est la convention.
 *
 * ──────────────────────────────────────────────────────────────
 * USAGE UNIVERSEL — depuis n'importe quel controller :
 *
 *   use App\Notifications\AppNotification;
 *
 *   // Mission assignée → notifier le client
 *   $mission->client->user->notify(new AppNotification(
 *       event : 'mission.assigned',
 *       title : 'Mission assignée',
 *       body  : "Votre mission « Plomberie » a été assignée.",
 *       url   : "/client/missions/{$mission->id}",
 *       extra : ['mission_id' => $mission->id, 'service' => $mission->service],
 *   ));
 *
 *   // Devis soumis → notifier le client
 *   $mission->client->user->notify(new AppNotification(
 *       event : 'quote.submitted',
 *       title : 'Nouveau devis à approuver',
 *       body  : "Un devis de 25 000 FCFA attend votre approbation.",
 *       url   : "/client/missions/{$mission->id}",
 *       extra : ['mission_id' => $mission->id, 'amount' => 25000],
 *   ));
 *
 *   // Document refusé → notifier le contractor
 *   $contractorUser->notify(new AppNotification(
 *       event : 'document.rejected',
 *       title : 'Document refusé',
 *       body  : "Votre casier judiciaire a été refusé. Motif : illisible.",
 *       url   : '/documents',
 *   ));
 *
 *   // Litige → notifier tous les admins
 *   User::where('role', 'admin')->each(fn($admin) =>
 *       $admin->notify(new AppNotification(
 *           event : 'mission.dispute',
 *           title : 'Litige signalé',
 *           body  : "Un problème a été signalé sur la mission #12.",
 *           url   : "/admin/missions?id=12",
 *           extra : ['mission_id' => 12],
 *       ))
 *   );
 * ──────────────────────────────────────────────────────────────
 *
 * Conventions d'événements (pour filtrer côté frontend) :
 *
 *   mission.*   : assigned, accepted, status_changed, completed, closed, dispute
 *   quote.*     : submitted, approved, rejected, revised
 *   payment.*   : received, commission
 *   document.*  : approved, rejected
 */
class AppNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string  $event,
        public readonly string  $title,
        public readonly string  $body,
        public readonly ?string $url   = null,
        public readonly string  $icon  = 'bell',
        public readonly array   $extra = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return array_merge([
            'event' => $this->event,
            'title' => $this->title,
            'body'  => $this->body,
            'url'   => $this->url,
            'icon'  => $this->icon,
        ], $this->extra);
    }
}