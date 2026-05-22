<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number')->unique();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->decimal('total_amount', 18, 2);
                $table->string('currency', 3)->default('USD');
                $table->string('status')->default('pending');
                $table->string('payment_method')->nullable();
                $table->text('description')->nullable();
                $table->json('items')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index('order_number');
                $table->index('status');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
