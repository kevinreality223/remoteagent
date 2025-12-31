<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('message_receipts', function (Blueprint $table) {
            $table->unsignedTinyInteger('poll_interval_seconds')->default(3)->after('last_acked_message_id');
            $table->timestamp('last_polled_at')->nullable()->after('poll_interval_seconds');
            $table->timestamp('next_poll_at')->nullable()->after('last_polled_at');
        });
    }

    public function down(): void
    {
        Schema::table('message_receipts', function (Blueprint $table) {
            $table->dropColumn(['poll_interval_seconds', 'last_polled_at', 'next_poll_at']);
        });
    }
};
