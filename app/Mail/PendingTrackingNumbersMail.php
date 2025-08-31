<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PendingTrackingNumbersMail extends Mailable
{
    use Queueable, SerializesModels;

    public $trackingNumbers;

    public function __construct($trackingNumbers)
    {
        $this->trackingNumbers = $trackingNumbers;
    }

    public function build()
    {
        return $this->subject('Pending Tracking Numbers - Not Delivered')
            ->view('emails.pending-tracking');
    }
}
