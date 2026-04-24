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
        Schema::table('questions', function (Blueprint $table) {
            $table->text('question_text')->change();
            $table->text('question_hi')->nullable()->change();
            $table->text('solution')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('question_text')->change();
            $table->string('question_hi')->nullable()->change();
            $table->text('solution')->nullable()->change();
        });
    }
};
