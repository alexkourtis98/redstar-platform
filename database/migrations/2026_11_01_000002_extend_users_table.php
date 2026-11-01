<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('super_admin')->default(false)->after('password');
            $table->string('phone')->nullable()->after('email');
            $table->foreignId('last_entity_id')->nullable()->constrained('entities')->nullOnDelete()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['last_entity_id']);
            $table->dropColumn(['super_admin', 'phone', 'last_entity_id']);
        });
    }
};
