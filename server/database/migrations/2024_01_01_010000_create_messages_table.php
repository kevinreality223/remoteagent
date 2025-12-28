<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('from_client_id')->nullable()->index();
            $table->uuid('to_client_id')->index();
            $table->string('type');
            $table->longText('ciphertext');
            $table->string('nonce');
            $table->string('tag');
            $table->timestamp('created_at');

            $table->index(['to_client_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
