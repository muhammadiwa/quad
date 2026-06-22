<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type_task')->default('Work');
            $table->string('id_project')->default('17');
            $table->time('start_at')->default('07:30:00');
            $table->time('end_at')->default('16:30:00');
            $table->string('location')->default('On Site');
            $table->string('skills')->default('70');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_templates');
    }
};
