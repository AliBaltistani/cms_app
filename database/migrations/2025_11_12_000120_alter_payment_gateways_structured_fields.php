<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payment_gateways', 'gateway_name')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->string('gateway_name')->nullable();
            });
        }
        if (!Schema::hasColumn('payment_gateways', 'gateway_type')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->enum('gateway_type', ['stripe', 'paypal', 'manual'])->nullable();
            });
        }
        if (!Schema::hasColumn('payment_gateways', 'public_key')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->string('public_key')->nullable();
            });
        }
        if (!Schema::hasColumn('payment_gateways', 'secret_key')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->text('secret_key')->nullable();
            });
        }
        if (!Schema::hasColumn('payment_gateways', 'webhook_secret')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->text('webhook_secret')->nullable();
            });
        }
        if (!Schema::hasColumn('payment_gateways', 'account_id')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->string('account_id')->nullable();
            });
        }
        if (!Schema::hasColumn('payment_gateways', 'is_default')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->boolean('is_default')->default(false);
            });
        }
        if (!Schema::hasColumn('payment_gateways', 'commission_rate')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->decimal('commission_rate', 5, 2)->default(10.00);
            });
        }
        if (!Schema::hasColumn('payment_gateways', 'status_bool')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->boolean('status_bool')->default(true);
            });
        }

        try {
            if (Schema::hasColumn('payment_gateways', 'status_bool') && Schema::hasColumn('payment_gateways', 'status')) {
                DB::table('payment_gateways')->update([
                    'status_bool' => DB::raw("CASE WHEN status = 'enabled' THEN 1 ELSE 0 END"),
                ]);
            }

            $rows = DB::table('payment_gateways')->get();
            foreach ($rows as $row) {
                $credentials = [];
                if (isset($row->credentials)) {
                    if (is_string($row->credentials)) {
                        $decoded = json_decode($row->credentials, true);
                        if (is_array($decoded)) {
                            $credentials = $decoded;
                        }
                    } elseif (is_array($row->credentials)) {
                        $credentials = $row->credentials;
                    }
                }

                $public = $credentials['key'] ?? $credentials['client_id'] ?? null;
                $secret = $credentials['secret'] ?? $credentials['client_secret'] ?? null;
                $webhook = $credentials['webhook_secret'] ?? null;

                DB::table('payment_gateways')
                    ->where('id', $row->id)
                    ->update([
                        'gateway_name' => $row->name ?? null,
                        'gateway_type' => $row->type ?? null,
                        'public_key' => $public,
                        'secret_key' => $secret,
                        'webhook_secret' => $webhook,
                    ]);
            }

            if (Schema::hasColumn('payment_gateways', 'status')) {
                Schema::table('payment_gateways', function (Blueprint $table) {
                    $table->dropColumn('status');
                });
            }

            if (Schema::hasColumn('payment_gateways', 'status_bool') && !Schema::hasColumn('payment_gateways', 'status')) {
                try {
                    DB::statement('ALTER TABLE payment_gateways CHANGE COLUMN status_bool status TINYINT(1) NOT NULL DEFAULT 1');
                } catch (\Throwable $e) {
                }
            }

            try {
                Schema::table('payment_gateways', function (Blueprint $table) {
                    if (Schema::hasColumn('payment_gateways', 'gateway_type') && Schema::hasColumn('payment_gateways', 'status')) {
                        $table->index(['gateway_type', 'status']);
                    }
                });
            } catch (\Throwable $e) {
            }

            $hasDefault = DB::table('payment_gateways')->where('is_default', true)->exists();
            if (!$hasDefault) {
                $stripe = DB::table('payment_gateways')->where('gateway_type', 'stripe')->orderBy('id')->first();
                if ($stripe) {
                    DB::table('payment_gateways')->where('id', $stripe->id)->update(['is_default' => true]);
                }
            }
        } catch (\Throwable $e) {
            // swallow migration-time mapping errors to avoid breaking deploy
        }

        Schema::table('payment_gateways', function (Blueprint $table) {
            if (Schema::hasColumn('payment_gateways', 'credentials')) {
                $table->dropColumn('credentials');
            }
            if (Schema::hasColumn('payment_gateways', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('payment_gateways', 'type')) {
                $table->dropColumn('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->enum('type', ['stripe', 'paypal'])->nullable();
            $table->json('credentials')->nullable();
            $table->enum('status', ['enabled', 'disabled'])->default('enabled');
            $table->dropIndex(['gateway_type', 'status']);

            $table->dropColumn(['gateway_name', 'gateway_type', 'public_key', 'secret_key', 'webhook_secret', 'account_id', 'is_default', 'commission_rate']);
        });
    }
};

