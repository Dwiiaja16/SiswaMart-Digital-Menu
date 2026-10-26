<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;
    public $email;

    public function __construct(User $user, string $token, string $email)
    {
        $this->user = $user;
        $this->token = $token;
        $this->email = $email;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔒 Permintaan Reset Kata Sandi — SiswaMart',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset_password',
            with: [
                'user'     => $this->user,
                'resetUrl' => route('password.reset', [
                    'token' => $this->token,
                    'email' => $this->email,
                ]),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}