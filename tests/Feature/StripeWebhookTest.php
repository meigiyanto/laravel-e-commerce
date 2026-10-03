<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use Stripe\WebhookSignature;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test_secret';

    private const ORDER_TOTAL = 100000;

    private const STRIPE_AMOUNT = 10000000;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set(
            'services.stripe.webhook_secret',
            self::WEBHOOK_SECRET
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Happy path
    |--------------------------------------------------------------------------
    */

    public function test_stripe_webhook_completes_pending_payment_and_order(): void
    {
        $payment = $this->createPayment();

        $response = $this->postStripeWebhook(
            'payment_intent.succeeded',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'succeeded',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
            'transaction_status' => 'succeeded',
            'transaction_id' => $payment->stripe_payment_intent_id,
            'reference_id' => $payment->stripe_payment_intent_id,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'completed',
        ]);
    }

    public function test_stripe_webhook_completes_processing_order_when_payment_succeeds(): void
    {
        $payment = $this->createPayment(
            orderStatus: 'processing'
        );

        $response = $this->postStripeWebhook(
            'payment_intent.succeeded',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'succeeded',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'completed',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Signature / webhook envelope
    |--------------------------------------------------------------------------
    */

    public function test_stripe_webhook_rejects_invalid_signature(): void
    {
        $payment = $this->createPayment();

        $payload = $this->makePayload(
            'payment_intent.succeeded',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'succeeded',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 'invalid-signature',
            ],
            $payload
        );

        $response->assertStatus(400);

        $this->assertPaymentState($payment, 'pending');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'pending',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Amount / currency validation
    |--------------------------------------------------------------------------
    */

    public function test_stripe_webhook_completes_order_when_amount_matches(): void
    {
        $payment = $this->createPayment();

        $response = $this->postStripeWebhook(
            'payment_intent.succeeded',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'succeeded',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'gross_amount' => self::ORDER_TOTAL,
            'status' => 'succeeded',
            'transaction_status' => 'succeeded',
            'transaction_id' => $payment->stripe_payment_intent_id,
            'reference_id' => $payment->stripe_payment_intent_id,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'total' => self::ORDER_TOTAL,
            'status' => 'completed',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_order_when_amount_does_not_match(): void
    {
        $payment = $this->createPayment();

        $response = $this->postStripeWebhook(
            'payment_intent.succeeded',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'succeeded',
                'amount' => 9000000,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(422);

        $this->assertPaymentState($payment, 'pending');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_currency_does_not_match(): void
    {
        $payment = $this->createPayment(
            paymentAttributes: [
                'currency' => 'idr',
            ]
        );

        $response = $this->postStripeWebhook(
            'payment_intent.succeeded',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'succeeded',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'usd',
            ]
        );

        $response->assertStatus(422);

        $this->assertPaymentState($payment, 'pending');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'pending',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Unknown / provider isolation
    |--------------------------------------------------------------------------
    */

    public function test_stripe_webhook_ignores_unknown_payment_intent(): void
    {
        $payment = $this->createPayment(
            paymentIntentId: 'pi_known_payment'
        );

        $response = $this->postStripeWebhook(
            'payment_intent.succeeded',
            [
                'id' => 'pi_unknown_payment',
                'status' => 'succeeded',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(200);

        $this->assertPaymentState($payment, 'pending');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_modify_non_stripe_payment(): void
    {
        $payment = $this->createPayment(
            provider: 'midtrans',
            paymentIntentId: null
        );

        $response = $this->postStripeWebhook(
            'payment_intent.succeeded',
            [
                'id' => 'pi_wrong_provider',
                'status' => 'succeeded',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(200);

        $this->assertPaymentState($payment, 'pending');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'pending',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Payment state transitions
    |--------------------------------------------------------------------------
    */

    public function test_stripe_webhook_marks_pending_payment_as_failed(): void
    {
        $payment = $this->createPayment();

        $response = $this->postStripeWebhook(
            'payment_intent.payment_failed',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'requires_payment_method',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
                'last_payment_error' => null,
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'failed',
            'transaction_status' => 'failed',
            'transaction_id' => $payment->stripe_payment_intent_id,
            'reference_id' => $payment->stripe_payment_intent_id,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_marks_pending_payment_as_canceled(): void
    {
        $payment = $this->createPayment();

        $response = $this->postStripeWebhook(
            'payment_intent.canceled',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'canceled',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'canceled',
            'transaction_status' => 'canceled',
            'transaction_id' => $payment->stripe_payment_intent_id,
            'reference_id' => $payment->stripe_payment_intent_id,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_can_complete_failed_payment_when_payment_succeeds(): void
    {
        $payment = $this->createPayment(
            paymentStatus: 'failed'
        );

        $response = $this->postStripeWebhook(
            'payment_intent.succeeded',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'succeeded',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(200);

        $this->assertPaymentState($payment, 'succeeded');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'completed',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_canceled_payment_when_payment_succeeds(): void
    {
        $payment = $this->createPayment(
            paymentStatus: 'canceled'
        );

        $response = $this->postStripeWebhook(
            'payment_intent.succeeded',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'succeeded',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(200);

        $this->assertPaymentState($payment, 'canceled');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_change_failed_payment_when_canceled(): void
    {
        $payment = $this->createPayment(
            paymentStatus: 'failed'
        );

        $response = $this->postStripeWebhook(
            'payment_intent.canceled',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'canceled',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(200);

        $this->assertPaymentState($payment, 'failed');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_downgrade_succeeded_payment_when_failed(): void
    {
        $payment = $this->createPayment(
            orderStatus: 'completed',
            paymentStatus: 'succeeded'
        );

        $response = $this->postStripeWebhook(
            'payment_intent.payment_failed',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'requires_payment_method',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(200);

        $this->assertPaymentState($payment, 'succeeded');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'completed',
        ]);
    }

    public function test_stripe_webhook_does_not_downgrade_succeeded_payment_when_canceled(): void
    {
        $payment = $this->createPayment(
            orderStatus: 'completed',
            paymentStatus: 'succeeded'
        );

        $response = $this->postStripeWebhook(
            'payment_intent.canceled',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'canceled',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(200);

        $this->assertPaymentState($payment, 'succeeded');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'completed',
        ]);
    }

    public function test_stripe_webhook_does_not_downgrade_canceled_payment_when_failed(): void
    {
        $payment = $this->createPayment(
            paymentStatus: 'canceled'
        );

        $response = $this->postStripeWebhook(
            'payment_intent.payment_failed',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'requires_payment_method',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(200);

        $this->assertPaymentState($payment, 'canceled');
    }

    /*
    |--------------------------------------------------------------------------
    | Idempotency
    |--------------------------------------------------------------------------
    */

    public function test_stripe_webhook_is_idempotent_for_repeated_succeeded_event(): void
    {
        $payment = $this->createPayment();

        $payload = [
            'id' => $payment->stripe_payment_intent_id,
            'status' => 'succeeded',
            'amount' => self::STRIPE_AMOUNT,
            'currency' => 'idr',
        ];

        $first = $this->postStripeWebhook(
            'payment_intent.succeeded',
            $payload
        );

        $first->assertStatus(200);

        $second = $this->postStripeWebhook(
            'payment_intent.succeeded',
            $payload
        );

        $second->assertStatus(200);

        $this->assertPaymentState($payment, 'succeeded');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'completed',
        ]);
    }

    public function test_stripe_webhook_is_idempotent_for_repeated_failed_event(): void
    {
        $payment = $this->createPayment();

        $payload = [
            'id' => $payment->stripe_payment_intent_id,
            'status' => 'requires_payment_method',
            'amount' => self::STRIPE_AMOUNT,
            'currency' => 'idr',
        ];

        $this->postStripeWebhook(
            'payment_intent.payment_failed',
            $payload
        )->assertStatus(200);

        $this->postStripeWebhook(
            'payment_intent.payment_failed',
            $payload
        )->assertStatus(200);

        $this->assertPaymentState($payment, 'failed');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_is_idempotent_for_repeated_canceled_event(): void
    {
        $payment = $this->createPayment();

        $payload = [
            'id' => $payment->stripe_payment_intent_id,
            'status' => 'canceled',
            'amount' => self::STRIPE_AMOUNT,
            'currency' => 'idr',
        ];

        $this->postStripeWebhook(
            'payment_intent.canceled',
            $payload
        )->assertStatus(200);

        $this->postStripeWebhook(
            'payment_intent.canceled',
            $payload
        )->assertStatus(200);

        $this->assertPaymentState($payment, 'canceled');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => 'pending',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Order state protection
    |--------------------------------------------------------------------------
    */

    #[DataProvider('terminalOrderStatuses')]
    public function test_succeeded_webhook_does_not_complete_terminal_order(
        string $orderStatus
    ): void {
        $payment = $this->createPayment(
            orderStatus: $orderStatus
        );

        $response = $this->postStripeWebhook(
            'payment_intent.succeeded',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'succeeded',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(200);

        $this->assertPaymentState($payment, 'pending');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => $orderStatus,
        ]);
    }

    #[DataProvider('terminalOrderStatuses')]
    public function test_failed_webhook_does_not_modify_terminal_order(
        string $orderStatus
    ): void {
        $payment = $this->createPayment(
            orderStatus: $orderStatus
        );

        $response = $this->postStripeWebhook(
            'payment_intent.payment_failed',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'requires_payment_method',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(200);

        $this->assertPaymentState($payment, 'pending');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => $orderStatus,
        ]);
    }

    #[DataProvider('terminalOrderStatuses')]
    public function test_canceled_webhook_does_not_modify_terminal_order(
        string $orderStatus
    ): void {
        $payment = $this->createPayment(
            orderStatus: $orderStatus
        );

        $response = $this->postStripeWebhook(
            'payment_intent.canceled',
            [
                'id' => $payment->stripe_payment_intent_id,
                'status' => 'canceled',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ]
        );

        $response->assertStatus(200);

        $this->assertPaymentState($payment, 'pending');

        $this->assertDatabaseHas('orders', [
            'id' => $payment->order_id,
            'status' => $orderStatus,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Invalid succeeded-event payload
    |--------------------------------------------------------------------------
    */

    #[DataProvider('invalidSucceededStatusPayloads')]
    public function test_succeeded_webhook_rejects_invalid_status(
        array $statusData
    ): void {
        $payment = $this->createProcessingPayment();

        $response = $this->postStripeWebhook(
            'payment_intent.succeeded',
            array_merge(
                [
                    'id' => $payment->stripe_payment_intent_id,
                    'amount' => self::STRIPE_AMOUNT,
                    'currency' => 'idr',
                ],
                $statusData
            )
        );

        $response->assertStatus(422);

        $this->assertPaymentState($payment, 'pending');
    }

    #[DataProvider('invalidSucceededAmountPayloads')]
    public function test_succeeded_webhook_rejects_invalid_amount(
        array $amountData
    ): void {
        $payment = $this->createProcessingPayment();

        $response = $this->postStripeWebhook(
            'payment_intent.succeeded',
            array_merge(
                [
                    'id' => $payment->stripe_payment_intent_id,
                    'status' => 'succeeded',
                    'currency' => 'idr',
                ],
                $amountData
            )
        );

        $response->assertStatus(422);

        $this->assertPaymentState($payment, 'pending');
    }

    #[DataProvider('invalidSucceededCurrencyPayloads')]
    public function test_succeeded_webhook_rejects_invalid_currency(
        array $currencyData
    ): void {
        $payment = $this->createProcessingPayment();

        $response = $this->postStripeWebhook(
            'payment_intent.succeeded',
            array_merge(
                [
                    'id' => $payment->stripe_payment_intent_id,
                    'status' => 'succeeded',
                    'amount' => self::STRIPE_AMOUNT,
                ],
                $currencyData
            )
        );

        $response->assertStatus(422);

        $this->assertPaymentState($payment, 'pending');
    }

    /*
    |--------------------------------------------------------------------------
    | Invalid failed-event payload
    |--------------------------------------------------------------------------
    */

    #[DataProvider('invalidFailedStatusPayloads')]
    public function test_failed_webhook_rejects_invalid_status(
        array $statusData
    ): void {
        $payment = $this->createProcessingPayment();

        $response = $this->postStripeWebhook(
            'payment_intent.payment_failed',
            array_merge(
                [
                    'id' => $payment->stripe_payment_intent_id,
                    'amount' => self::STRIPE_AMOUNT,
                    'currency' => 'idr',
                ],
                $statusData
            )
        );

        $response->assertStatus(422);

        $this->assertPaymentState($payment, 'pending');
    }

    /*
    |--------------------------------------------------------------------------
    | Invalid canceled-event payload
    |--------------------------------------------------------------------------
    */

    #[DataProvider('invalidCanceledStatusPayloads')]
    public function test_canceled_webhook_rejects_invalid_status(
        array $statusData
    ): void {
        $payment = $this->createProcessingPayment();

        $response = $this->postStripeWebhook(
            'payment_intent.canceled',
            array_merge(
                [
                    'id' => $payment->stripe_payment_intent_id,
                    'amount' => self::STRIPE_AMOUNT,
                    'currency' => 'idr',
                ],
                $statusData
            )
        );

        $response->assertStatus(422);

        $this->assertPaymentState($payment, 'pending');
    }

    /*
    |--------------------------------------------------------------------------
    | Payment intent ID validation
    |--------------------------------------------------------------------------
    */

    #[DataProvider('invalidPaymentIntentIds')]
    public function test_webhook_rejects_invalid_payment_intent_id(
        string $eventType,
        array $idData
    ): void {
        $payment = $this->createProcessingPayment();

        $defaults = match ($eventType) {
            'payment_intent.succeeded' => [
                'status' => 'succeeded',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ],

            'payment_intent.payment_failed' => [
                'status' => 'requires_payment_method',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ],

            'payment_intent.canceled' => [
                'status' => 'canceled',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ],
        };

        $response = $this->postStripeWebhook(
            $eventType,
            array_merge($defaults, $idData)
        );

        $response->assertStatus(422);

        $this->assertPaymentState($payment, 'pending');
    }

    /*
    |--------------------------------------------------------------------------
    | Missing required fields
    |--------------------------------------------------------------------------
    */

    #[DataProvider('missingPaymentIntentIds')]
    public function test_webhook_rejects_missing_payment_intent_id(
        string $eventType
    ): void {
        $payment = $this->createProcessingPayment();

        $defaults = match ($eventType) {
            'payment_intent.succeeded' => [
                'status' => 'succeeded',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ],

            'payment_intent.payment_failed' => [
                'status' => 'requires_payment_method',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ],

            'payment_intent.canceled' => [
                'status' => 'canceled',
                'amount' => self::STRIPE_AMOUNT,
                'currency' => 'idr',
            ],
        };

        $response = $this->postStripeWebhook(
            $eventType,
            $defaults
        );

        $response->assertStatus(200);

        $this->assertPaymentState($payment, 'pending');
    }

    public static function missingPaymentIntentIds(): array
    {
        return [
            'succeeded' => ['payment_intent.succeeded'],
            'failed' => ['payment_intent.payment_failed'],
            'canceled' => ['payment_intent.canceled'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Data providers
    |--------------------------------------------------------------------------
    */

    public static function terminalOrderStatuses(): array
    {
        return [
            'canceled' => ['canceled'],
            'failed' => ['failed'],
            'refunded' => ['refunded'],
        ];
    }

    public static function invalidSucceededStatusPayloads(): array
    {
        return [
            'missing status' => [
                [],
            ],

            'integer status' => [
                ['status' => 123],
            ],

            'processing status' => [
                ['status' => 'processing'],
            ],
        ];
    }

    public static function invalidFailedStatusPayloads(): array
    {
        return [
            'missing status' => [
                [],
            ],

            'canceled status' => [
                ['status' => 'canceled'],
            ],

            'processing status' => [
                ['status' => 'processing'],
            ],

            'requires action status' => [
                ['status' => 'requires_action'],
            ],

            'requires capture status' => [
                ['status' => 'requires_capture'],
            ],

            'boolean status' => [
                ['status' => true],
            ],
        ];
    }

    public static function invalidCanceledStatusPayloads(): array
    {
        return [
            'missing status' => [
                [],
            ],

            'requires payment method status' => [
                ['status' => 'requires_payment_method'],
            ],

            'processing status' => [
                ['status' => 'processing'],
            ],

            'requires action status' => [
                ['status' => 'requires_action'],
            ],

            'requires capture status' => [
                ['status' => 'requires_capture'],
            ],

            'boolean status' => [
                ['status' => true],
            ],
        ];
    }

    public static function invalidSucceededAmountPayloads(): array
    {
        return [
            'missing amount' => [
                [],
            ],

            'zero amount' => [
                ['amount' => 0],
            ],

            'negative amount' => [
                ['amount' => -100],
            ],

            'string amount' => [
                ['amount' => '10000000'],
            ],

            'float amount' => [
                ['amount' => 10000000.5],
            ],

            'boolean amount' => [
                ['amount' => true],
            ],

            'array amount' => [
                ['amount' => [10000000]],
            ],

            'object amount' => [
                ['amount' => (object) ['value' => 10000000]],
            ],
        ];
    }

    public static function invalidSucceededCurrencyPayloads(): array
    {
        return [
            'missing currency' => [
                [],
            ],

            'empty currency' => [
                ['currency' => ''],
            ],

            'whitespace currency' => [
                ['currency' => '   '],
            ],

            'malformed currency' => [
                ['currency' => 'idr-invalid'],
            ],

            'boolean currency' => [
                ['currency' => true],
            ],

            'array currency' => [
                ['currency' => ['idr']],
            ],

            'object currency' => [
                ['currency' => (object) ['value' => 'idr']],
            ],
        ];
    }

    public static function invalidPaymentIntentIds(): array
    {
        return [
            'succeeded integer id' => [
                'payment_intent.succeeded',
                ['id' => 123456],
            ],

            'succeeded array id' => [
                'payment_intent.succeeded',
                ['id' => ['invalid-id']],
            ],

            'succeeded boolean id' => [
                'payment_intent.succeeded',
                ['id' => true],
            ],

            'succeeded object id' => [
                'payment_intent.succeeded',
                ['id' => (object) ['value' => 'invalid']],
            ],

            'failed integer id' => [
                'payment_intent.payment_failed',
                ['id' => 123456],
            ],

            'failed array id' => [
                'payment_intent.payment_failed',
                ['id' => ['invalid-id']],
            ],

            'failed boolean id' => [
                'payment_intent.payment_failed',
                ['id' => true],
            ],

            'failed object id' => [
                'payment_intent.payment_failed',
                ['id' => (object) ['value' => 'invalid']],
            ],

            'canceled integer id' => [
                'payment_intent.canceled',
                ['id' => 123456],
            ],

            'canceled array id' => [
                'payment_intent.canceled',
                ['id' => ['invalid-id']],
            ],

            'canceled boolean id' => [
                'payment_intent.canceled',
                ['id' => true],
            ],

            'canceled object id' => [
                'payment_intent.canceled',
                ['id' => (object) ['value' => 'invalid']],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Test helpers
    |--------------------------------------------------------------------------
    */

    private function createPayment(
        string $orderStatus = 'pending',
        string $paymentStatus = 'pending',
        string $provider = 'stripe',
        ?string $paymentIntentId = null,
        array $paymentAttributes = [],
        array $orderAttributes = [],
    ): Payment {
        $user = User::factory()->create();

        $order = Order::factory()->create(
            array_merge(
                [
                    'user_id' => $user->id,
                    'status' => $orderStatus,
                    'total' => self::ORDER_TOTAL,
                ],
                $orderAttributes
            )
        );

        return Payment::factory()->create(
            array_merge(
                [
                    'order_id' => $order->id,
                    'provider' => $provider,
                    'status' => $paymentStatus,
                    'transaction_status' => 'pending',
                    'gross_amount' => self::ORDER_TOTAL,
                    'currency' => 'IDR',
                    'stripe_payment_intent_id' => $paymentIntentId
                        ?? 'pi_test_'.uniqid(),
                ],
                $paymentAttributes
            )
        );
    }

    private function createProcessingPayment(
        array $paymentAttributes = [],
        array $orderAttributes = [],
    ): Payment {
        return $this->createPayment(
            orderStatus: 'processing',
            paymentStatus: 'pending',
            paymentAttributes: $paymentAttributes,
            orderAttributes: $orderAttributes,
        );
    }

    private function postStripeWebhook(
        string $eventType,
        array $paymentIntent,
    ) {
        $payload = $this->makePayload(
            $eventType,
            $paymentIntent
        );

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
            self::WEBHOOK_SECRET
        );

        return $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );
    }

    private function makePayload(
        string $eventType,
        array $paymentIntent,
    ): string {
        $eventId = 'evt_test_'.str_replace(
            ['.', '_'],
            '-',
            $eventType
        ).'_'.uniqid();

        return json_encode([
            'id' => $eventId,
            'object' => 'event',
            'type' => $eventType,
            'data' => [
                'object' => array_merge(
                    [
                        'id' => 'pi_test_default',
                        'object' => 'payment_intent',
                    ],
                    $paymentIntent
                ),
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function assertPaymentState(
        Payment $payment,
        string $status
    ): void {
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => $status,
        ]);
    }
}
