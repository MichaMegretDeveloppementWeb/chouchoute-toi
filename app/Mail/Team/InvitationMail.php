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
 * The link a new member of the team opens to choose a password.
 */
final class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /** The name the site signs with. */
    private const SITE = 'Chouchoute-toi';

    public function __construct(public string $name, public string $email, public string $link, public int $days) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), self::SITE),
            subject: 'Votre accès à l’administration de '.self::SITE,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.team.message',
            with: [
                'heading' => 'Bienvenue, '.$this->name,
                'paragraphs' => [
                    'Un accès à l’administration de '.self::SITE.' vient de vous être ouvert.',
                    'Choisissez votre mot de passe : vous vous connecterez ensuite avec l’adresse '.$this->email.'.',
                ],
                'button' => 'Choisir mon mot de passe',
                'link' => $this->link,
                'note' => 'Ce lien est valable '.$this->days.' jours et ne sert qu’une fois. S’il a expiré, demandez-en un nouveau au propriétaire.',
            ],
        );
    }
}
