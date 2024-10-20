<?php

namespace App\Broadcasting;

use App\Notifications\SmsNotification;
use Illuminate\Support\Facades\Notification;
use Twilio\Rest\Client;

class SMSChannel
{

    protected $client;

    /**
     * Create a new channel instance.
     */
    public function __construct(Client $client)
    {

        $this->client = $client;
    }


    /**
     * @param mixed $notifiable
     * @param SmsNotification $notification
     *
     * @return [type]
     */
    public function send($notifiable, SmsNotification $notification)
    {
        $message = $notification->toSms($notifiable);
        $this->client->messages->create(
            $message['phone'],
            [
                'from' => config('twilio.sender'),
                'body' => $message['body'],
            ]
        );
    }
}
