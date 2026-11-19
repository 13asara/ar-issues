<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('title');
            $table->string('status', 20)->default('draft');
            $table->text('compile_log')->nullable();
            $table->timestamp('compiled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('target_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->string('disk_path', 512);
            $table->unsignedTinyInteger('target_index');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('original_name', 255)->nullable();
            $table->timestamps();

            $table->unique(['issue_id', 'target_index']);
        });

        Schema::create('target_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_image_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 12)->default('local');
            $table->string('disk_path', 512)->nullable();
            $table->string('youtube_id', 32)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->timestamps();

            $table->unique('target_image_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_videos');
        Schema::dropIfExists('target_images');
        Schema::dropIfExists('issues');
    }
};
