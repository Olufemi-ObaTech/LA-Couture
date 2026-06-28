<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('to_id')->constrained('users')->onDelete('cascade');
            $table->string('subject', 200);
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->unsignedBigInteger('replied_to_id')->nullable();
            $table->foreign('replied_to_id')->references('id')->on('staff_messages')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_messages');
    }
};
