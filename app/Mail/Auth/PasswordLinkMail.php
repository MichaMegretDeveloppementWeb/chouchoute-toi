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
 * The link to choose a new password, for an account that asked for one.
 */
final class PasswordLinkMail extends Mailable
{
    use NamesTheSite, Queueable, SerializesModels;

    public function __construct(public string $name, public string $link, public int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre nouveau mot de passe',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.auth.message',
            with: [
                'heading' => 'Bonjour '.$this->name,
                'paragraphs' => [
                    'Vous avez demandé à changer votre mot de passe pour l’administration de '.$this->site().'.',
                ],
                'button' => 'Choisir un nouveau mot de passe',
                'link' => $this->link,
                'note' => 'Ce lien est valable '.$this->minutes.' minutes. Si vous n’avez rien demandé, ignorez ce message : votre mot de passe ne change pas.',
            ],
        );
    }
}
