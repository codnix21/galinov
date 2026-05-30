<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array<string, mixed>  $summary */
    public function __construct(
        public array $summary,
        public string $periodLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Еженедельный отчёт — '.$this->periodLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.weekly-report',
        );
    }
}
