<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendMovingStatusNotification
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $driver_id, $merchant_id, $message, $title;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($driver_id, $merchant_id, $booking, $message, $title)
    {
        //
        $this->driver_id = $driver_id;
        $this->merchant_id = $merchant_id;
        $this->message = $message;
        $this->title = $title;
        $this->booking = $booking;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
