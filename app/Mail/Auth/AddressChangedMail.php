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
 * Told to both addresses when the one an account signs in with changes.
 */
final class AddressChangedMail extends Mailable
{
    use NamesTheSite, Queueable, SerializesModels;

    public function __construct(public string $name, public string $email) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre adresse de connexion a changé',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.auth.message',
            with: [
                'heading' => 'Bonjour '.$this->name,
                'paragraphs' => [
                    'Vous vous connectez désormais à l’administration de '.$this->site().' avec l’adresse '.$this->email.'.',
                    'Votre mot de passe ne change pas.',
                ],
                'button' => null,
                'link' => null,
                'note' => 'Si ce changement vous surprend, prévenez le propriétaire.',
            ],
        );
    }
}
