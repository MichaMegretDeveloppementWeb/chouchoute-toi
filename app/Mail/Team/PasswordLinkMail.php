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
 * The link to choose a new password, for an account that asked for one.
 */
final class PasswordLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    /** The name the site signs with. */
    private const SITE = 'Chouchoute-toi';

    public function __construct(public string $name, public string $link, public int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), self::SITE),
            subject: 'Votre nouveau mot de passe',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.team.message',
            with: [
                'heading' => 'Bonjour '.$this->name,
                'paragraphs' => [
                    'Vous avez demandé à changer votre mot de passe pour l’administration de '.self::SITE.'.',
                ],
                'button' => 'Choisir un nouveau mot de passe',
                'link' => $this->link,
                'note' => 'Ce lien est valable '.$this->minutes.' minutes. Si vous n’avez rien demandé, ignorez ce message : votre mot de passe ne change pas.',
            ],
        );
    }
}
