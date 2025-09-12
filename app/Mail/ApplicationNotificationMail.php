<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $title;
    public $messageContent;
    public $data;
    public $actionUrl;

    public function __construct($title, $messageContent, $data = null, $actionUrl = null)
    {
        $this->title = $title;
        $this->messageContent = $messageContent;
        $this->data = $data;
        $this->actionUrl = $actionUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}