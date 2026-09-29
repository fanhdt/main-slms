<?php

declare(strict_types=1);

namespace App\Domain\Report\Mail;

use App\Domain\Lab\Models\Lab;
use App\Domain\Report\Models\Report;
use App\Domain\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ReportGeneratedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Report $report,
        public readonly Lab $lab,
        public readonly User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Rekap {$this->report->type->label()} Booking — {$this->lab->name} ({$this->report->period_start->format('d M Y')})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'reports.mail.recap',
            with: [
                'report' => $this->report,
                'lab'    => $this->lab,
                'name'   => $this->recipient->name,
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if ($this->report->excel_path && Storage::disk($this->report->disk)->exists($this->report->excel_path)) {
            $attachments[] = Attachment::fromStorageDisk($this->report->disk, $this->report->excel_path)
                ->as("rekap-{$this->report->type->value}.xlsx");
        }

        if ($this->report->pdf_path && Storage::disk($this->report->disk)->exists($this->report->pdf_path)) {
            $attachments[] = Attachment::fromStorageDisk($this->report->disk, $this->report->pdf_path)
                ->as("rekap-{$this->report->type->value}.pdf");
        }

        return $attachments;
    }
}