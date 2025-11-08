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
        Schema::create('service_question_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->foreignId('service_question_id')->constrained('service_questions')->onDelete('cascade');
            $table->text('answer_value'); // Store answer as text (can be JSON for select_multiple)
            $table->timestamps();

            $table->index('appointment_id');
            $table->index('service_question_id');
            $table->unique(['appointment_id', 'service_question_id']); // One answer per question per appointment
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_question_answers');
    }
};
