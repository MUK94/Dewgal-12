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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade'); // User who initiated the appointment request
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade'); // User who received the appointment request
            $table->dateTime('scheduled_at'); // Date and time of the appointment
            $table->string('location')->nullable(); // Where the appointment will take place (e.g., "Cafe Aroma", "Online via Zoom")
            $table->text('notes')->nullable(); // Any additional notes for the appointment
            $table->enum('status', ['pending', 'accepted', 'declined', 'cancelled', 'completed'])->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
