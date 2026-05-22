<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crypto_gateways')) {
            Schema::create('crypto_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('api_endpoint');
            $table->string('webhook_endpoint')->nullable();
            $table->json('supported_currencies')->default('[]');
            $table->json('config')->default('{}');
            $table->boolean('is_active')->default(false);
            $table->integer('priority')->default(0);
            $table->decimal('transaction_fee_percentage', 8, 4)->default(0);
            $table->decimal('min_transaction_amount', 18, 8)->default(0);
            $table->decimal('max_transaction_amount', 18, 8)->nullable();
            $table->integer('confirmation_required')->default(1);
            $table->timestamps();
            $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_gateways');
    }
};
