<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('crypto_transaction_id')->nullable()->constrained('crypto_transactions')->onDelete('set null');
            $table->string('payment_method');
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
