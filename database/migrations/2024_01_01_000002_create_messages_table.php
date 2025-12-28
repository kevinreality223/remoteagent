<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('from_client_id')->nullable();
            $table->uuid('to_client_id');
            $table->string('type');
            $table->longText('ciphertext');
            $table->string('nonce');
            $table->string('tag');
            $table->timestamps();
            $table->index(['to_client_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
