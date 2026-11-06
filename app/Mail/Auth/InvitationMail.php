<?php

declare(strict_types=1);

namespace App\Mail\Auth;

use App\Mail\Auth\Concerns\NamesTheSite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The link a new account opens to choose its password.
 */
final class InvitationMail extends Mailable
{
    use NamesTheSite, Queueable, SerializesModels;

    public function __construct(public string $name, public string $email, public string $link, public int $days) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre accès à l’administration de '.$this->site(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.auth.message',
            with: [
                'heading' => 'Bienvenue, '.$this->name,
                'paragraphs' => [
                    'Un accès à l’administration de '.$this->site().' vient de vous être ouvert.',
                    'Choisissez votre mot de passe : vous vous connecterez ensuite avec l’adresse '.$this->email.'.',
                ],
                'button' => 'Choisir mon mot de passe',
                'link' => $this->link,
                'note' => 'Ce lien est valable '.$this->days.' jours et ne sert qu’une fois. S’il a expiré, demandez-en un nouveau au propriétaire.',
            ],
        );
    }
}
