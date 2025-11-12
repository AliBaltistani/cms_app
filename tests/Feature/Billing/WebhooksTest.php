<?php

namespace Tests\Feature\Billing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhooksTest extends TestCase
{
    public function test_paypal_webhook_receives_payload(): void
    {
        $resp = $this->postJson('/api/webhook/paypal', [
            'event_type' => 'PAYMENT.CAPTURE.DENIED',
            'resource' => ['id' => 'test-id'],
        ]);
        $resp->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_stripe_webhook_invalid_signature(): void
    {
        $resp = $this->withHeaders(['Stripe-Signature' => 'invalid'])
            ->post('/api/webhook/stripe', ['type' => 'payment_intent.succeeded']);
        $resp->assertStatus(400);
    }
}

