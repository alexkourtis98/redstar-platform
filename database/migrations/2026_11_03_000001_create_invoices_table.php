<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('series_code')->nullable();
            $table->integer('sequence_number');
            $table->integer('fiscal_year');
            $table->date('issue_date');
            $table->string('document_type')->default('2.1');
            $table->string('uid')->nullable()->unique();
            $table->string('mark', 18)->nullable()->unique();
            $table->string('qr_url')->nullable();
            $table->string('cancellation_mark', 18)->nullable();
            $table->timestamp('transmitted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('line_items');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('vat_amount', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('pdf_path')->nullable();
            $table->enum('status', ['draft', 'pending_aade', 'issued', 'failed', 'cancelled'])->default('draft');
            $table->text('failure_message')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['entity_id', 'status', 'issue_date']);
            $table->index(['mark']);
            $table->index(['invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
