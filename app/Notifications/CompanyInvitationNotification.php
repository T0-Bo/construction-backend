<?php

namespace App\Notifications;

use App\Models\CompanyInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public CompanyInvitation $invitation,
        public string $token
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = url(
            '/invitations/accept?token='.urlencode($this->token)
        );

        return (new MailMessage)
            ->subject('Invitación a '.$this->invitation->company->name)
            ->greeting('¡Hola!')
            ->line(
                'Has recibido una invitación para unirte a '
                .$this->invitation->company->name.'.'
            )
            ->line('Rol asignado: '.$this->invitation->role)
            ->line('La invitación vence el '.$this->invitation->expires_at->format('d/m/Y H:i'))
            ->action('Aceptar invitación', $acceptUrl)
            ->line('Si no esperabas esta invitación, podés ignorar este correo.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'company_id' => $this->invitation->company_id,
            'role' => $this->invitation->role,
        ];
    }
}
