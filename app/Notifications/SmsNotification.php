<?php

namespace App\Notifications;

use App\Broadcasting\SMSChannel;
use Twilio\Http\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SmsNotification extends Notification
{
    use Queueable;

    protected $message;
    protected $phoneNumber;

    /**
     * Create a new notification instance.
     */
    public function __construct($message, $phoneNumber)
    {
        $this->message = $message;
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [SMSChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toSms(object $notifiable)
    {
        return [
            'phone' => $this->phoneNumber,
            'body' => $this->message,
        ];
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
