<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPayPalWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $payload;
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $event = $this->payload['event_type'] ?? null;
        $resource = $this->payload['resource'] ?? [];
        if ($event === 'PAYMENT.CAPTURE.COMPLETED') {
            $id = $resource['supplementary_data']['related_ids']['order_id'] ?? ($resource['id'] ?? null);
            if ($id) {
                $txn = Transaction::where('transaction_id', $id)->first();
                if ($txn) {
                    $txn->status = 'paid';
                    $txn->response = $resource;
                    $txn->save();
                    $invoice = Invoice::find($txn->invoice_id);
                    if ($invoice && $invoice->status !== 'paid') {
                        $invoice->status = 'paid';
                        $invoice->save();
                    }
                }
            }
        } elseif ($event === 'PAYMENT.CAPTURE.DENIED') {
            $id = $resource['supplementary_data']['related_ids']['order_id'] ?? ($resource['id'] ?? null);
            $txn = $id ? Transaction::where('transaction_id', $id)->first() : null;
            if ($txn) {
                $txn->status = 'failed';
                $txn->response = $resource;
                $txn->save();
                $invoice = Invoice::find($txn->invoice_id);
                if ($invoice && $invoice->status !== 'failed') {
                    $invoice->status = 'failed';
                    $invoice->save();
                }
            }
        }
    }
}

