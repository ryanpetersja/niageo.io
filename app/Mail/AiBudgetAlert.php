<?php

namespace App\Mail;

use App\Models\AiBudget;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AiBudgetAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $threshold,
        public float $spent,
        public float $budget,
        public float $projected,
        public string $action,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "AI usage has reached {$this->threshold}% of this month's budget");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.ai-budget-alert', with: [
            'actionLabel' => AiBudget::ACTIONS[$this->action] ?? $this->action,
            'dashboardUrl' => route('settings.ai-usage'),
        ]);
    }
}
