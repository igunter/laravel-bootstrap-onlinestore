<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPaid extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Payment received for order {$this->order->order_number}")
            ->greeting("Thanks, {$this->order->contact_name}!")
            ->line("We've received payment for order {$this->order->order_number}.");

        foreach ($this->order->items as $item) {
            $message->line("{$item->quantity} × {$item->product_name}".
                ($item->variant_options_label ? " ({$item->variant_options_label})" : '').
                " — £{$item->line_total}");
        }

        return $message
            ->line("Total: £{$this->order->total}")
            ->line('We\'ll be in touch once your order ships.');
    }
}
