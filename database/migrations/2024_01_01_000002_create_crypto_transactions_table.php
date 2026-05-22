<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crypto_gateway_id')->constrained('crypto_gateways')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('transaction_reference')->unique();
            $table->string('gateway_transaction_id')->nullable()->unique();
            $table->string('payment_address');
            $table->string('cryptocurrency');
            $table->decimal('crypto_amount', 18, 8);
            $table->decimal('fiat_amount', 18, 2);
            $table->string('fiat_currency', 3);
            $table->decimal('exchange_rate', 18, 8);
            $table->string('status')->default('pending');
            $table->integer('confirmations')->default(0);
            $table->integer('required_confirmations')->default(1);
            $table->string('transaction_hash')->nullable();
            $table->decimal('amount_received', 18, 8)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->json('gateway_response')->nullable();
            $table->json('metadata')->nullable();
            $table->string('webhook_status')->default('pending');
            $table->timestamp('webhook_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('transaction_reference');
            $table->index('gateway_transaction_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_transactions');
    }
};
