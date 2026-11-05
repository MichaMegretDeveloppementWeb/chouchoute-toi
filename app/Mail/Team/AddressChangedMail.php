<?php

declare(strict_types=1);

namespace App\Mail\Team;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Told to both addresses when the one an account signs in with changes.
 */
final class AddressChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    /** The name the site signs with. */
    private const SITE = 'Chouchoute-toi';

    public function __construct(public string $name, public string $email) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), self::SITE),
            subject: 'Votre adresse de connexion a changé',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.team.message',
            with: [
                'heading' => 'Bonjour '.$this->name,
                'paragraphs' => [
                    'Vous vous connectez désormais à l’administration de '.self::SITE.' avec l’adresse '.$this->email.'.',
                    'Votre mot de passe ne change pas.',
                ],
                'button' => null,
                'link' => null,
                'note' => 'Si ce changement vous surprend, prévenez le propriétaire.',
            ],
        );
    }
}
