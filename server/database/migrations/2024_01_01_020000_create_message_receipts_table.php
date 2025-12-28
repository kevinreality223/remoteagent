<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class {
    public function up(): void
    {
        Schema::create('message_receipts', function (Blueprint $table) {
            $table->uuid('client_id')->primary();
            $table->unsignedBigInteger('last_acked_message_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_receipts');
    }
};
