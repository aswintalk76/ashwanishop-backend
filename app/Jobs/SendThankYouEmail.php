<?php

namespace App\Jobs;

use App\Mail\ThankYouMail;
use App\Models\EmailLog;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendThankYouEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function handle(): void
    {
        $order = $this->order->load('user');

        $log = EmailLog::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'type' => 'thank_you',
            'recipient' => $order->user->email,
            'subject' => 'Thank you for your order!',
            'status' => 'queued',
        ]);

        try {
            Mail::to($order->user->email)->send(new ThankYouMail($order));
            $log->update(['status' => 'sent']);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }
    }
}
