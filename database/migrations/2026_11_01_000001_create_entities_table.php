<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('primary_color')->default('#2563EB');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('tax_office')->nullable();
            $table->boolean('aade_enabled')->default(false);
            $table->string('aade_user_id')->nullable();
            $table->string('aade_subscription_key')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('entity_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['admin', 'manager', 'member'])->default('member');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['entity_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_user');
        Schema::dropIfExists('entities');
    }
};
