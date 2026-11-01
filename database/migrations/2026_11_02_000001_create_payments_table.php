<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['deposit', 'full', 'package', 'subscription'])->default('full');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('gateway', ['stripe', 'viva'])->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->string('gateway_customer_id')->nullable();
            $table->enum('status', ['pending', 'processing', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_link_token')->nullable()->unique();
            $table->timestamp('link_expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['entity_id', 'status', 'paid_at']);
            $table->index(['gateway', 'status']);
            $table->index('payment_link_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
