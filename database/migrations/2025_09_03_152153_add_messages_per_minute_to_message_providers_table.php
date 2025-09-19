<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('message_providers', function (Blueprint $table) {
            $table->unsignedInteger('messages_per_minute')->default(60)->after('config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_providers', function (Blueprint $table) {
            $table->dropColumn('messages_per_minute');
        });
    }
};
