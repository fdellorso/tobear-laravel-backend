<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactMessage extends Notification
{
    use Queueable;

    public function __construct(
        public ContactMessage $contactMessage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuovo messaggio dal form di contatto')
            ->greeting('Hai ricevuto un nuovo messaggio.')
            ->line('Da: '.($this->contactMessage->name ?? 'Anonimo').' ('.$this->contactMessage->email.')')
            ->line('Messaggio:')
            ->line($this->contactMessage->message);
    }
}
