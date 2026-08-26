<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactMail extends Mailable
{
    /**
     * @param  array{name: string, email: string, phone: string, commune: string, volume: string, prestation: string, message: string}  $data
     */
    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('amandine@chouchoute-toi.com', 'Chouchoute-toi'),
            replyTo: [new Address($this->data['email'], $this->data['name'])],
            subject: 'Nouvelle demande de '.$this->data['name'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact',
        );
    }
}
