<?php

declare(strict_types=1);

namespace App\Mail;

use App\Data\ContactData;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class ContactMessage extends Mailable
{
    public function __construct(public ContactData $contact) {}

    public function envelope(): Envelope
    {
        return new Envelope(replyTo: [new Address($this->contact->email, $this->contact->name)], subject: __('starter.contact_subject'));
    }

    public function content(): Content
    {
        return new Content(text: 'mail.contact');
    }
}
