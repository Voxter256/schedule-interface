<?php
namespace App\Mail;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;


class EmailVerification extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // return $this->view('emails.verification');

        return $this
            ->subject('Email Verification')
            ->markdown('vendor.notifications.email')->with([
                "level" => "default",
                "greeting" => "Hello!",
                "introLines" => [
                    'You are receiving this email because we need to verify you are the owner of the email address for your account.'
                ],
                "actionText" => 'Verify',
                "actionUrl" => url('register/verify/'. $this->user->email_token),
                "outroLines" => [
                    'If you did not try to register for an account, no further action is required.'
                ]
            ]);
    }
}
