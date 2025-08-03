<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendRegistrationLink extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $name;
    public $tracking_id;
    public $time;
    public $mail_body;

    public function __construct($name, $tracking_id, $time, $mail_body)
    {
        $this->name        = $name;
        $this->tracking_id = $tracking_id;
        $this->time        = $time;
        $this->mail_body   = $mail_body;
    }
    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account Activation Link',
        );
    }
    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.registration.activate',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
