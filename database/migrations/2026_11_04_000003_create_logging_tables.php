<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('gateway', ['stripe', 'viva']);
            $table->string('event_type');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->enum('status', ['success', 'error'])->default('success');
            $table->text('error_message')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamp('created_at');

            $table->index(['payment_id', 'created_at']);
            $table->index(['gateway', 'event_type']);
        });

        Schema::create('aade_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('operation', ['SendInvoices', 'CancelInvoice', 'RequestDocs', 'RequestTransmittedDocs']);
            $table->string('invoice_type')->nullable();
            $table->string('mark', 18)->nullable();
            $table->string('uid')->nullable();
            $table->json('request_payload');
            $table->json('response_payload')->nullable();
            $table->enum('status', ['success', 'error', 'validation_error'])->default('success');
            $table->json('errors')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamp('created_at');

            $table->index(['invoice_id', 'created_at']);
            $table->index(['operation', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aade_logs');
        Schema::dropIfExists('payment_gateway_logs');
    }
};
