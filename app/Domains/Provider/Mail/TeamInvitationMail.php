<?php

namespace App\Domains\Provider\Mail;

use App\Domains\Provider\Models\TeamMember;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public TeamMember $teamMember
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve been invited to join '.$this->teamMember->provider->business_name,
        );
    }

    public function content(): Content
    {
        $acceptUrl = route('team.invite.show', ['token' => $this->teamMember->invitation_token]);

        return new Content(
            view: 'emails.team.invitation',
            with: [
                'teamMember' => $this->teamMember,
                'provider' => $this->teamMember->provider,
                'acceptUrl' => $acceptUrl,
                'expiresIn' => '7 days',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
