<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crypto_gateway_id')->constrained('crypto_gateways')->onDelete('cascade');
            $table->string('event_type');
            $table->string('gateway_webhook_id')->nullable();
            $table->json('payload');
            $table->string('signature')->nullable();
            $table->boolean('signature_verified')->default(false);
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index('crypto_gateway_id');
            $table->index('event_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
