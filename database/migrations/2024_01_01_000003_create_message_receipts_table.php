<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_receipts', function (Blueprint $table) {
            $table->uuid('client_id')->primary();
            $table->uuid('last_acked_message_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_receipts');
    }
};
