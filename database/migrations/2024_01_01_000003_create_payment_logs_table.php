<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crypto_transaction_id')->constrained('crypto_transactions')->onDelete('cascade');
            $table->string('action');
            $table->string('status');
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            $table->index('crypto_transaction_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
