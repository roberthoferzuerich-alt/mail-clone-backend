<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SentEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailSubject;
    public $emailBody;
    public $attachmentPaths;

    /**
     * Create a new message instance.
     */
    public function __construct($emailSubject, $emailBody, $attachmentPaths = [])
    {
        $this->emailSubject = $emailSubject;
        $this->emailBody = $emailBody;
        $this->attachmentPaths = $attachmentPaths;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.sent',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        foreach ($this->attachmentPaths as $file) {
            // $file['path'] contains the relative path in the 'public' disk
            $absolutePath = Storage::disk('public')->path($file['path']);
            $attachments[] = Attachment::fromPath($absolutePath)
                                ->as($file['name']);
        }

        return $attachments;
    }
}
