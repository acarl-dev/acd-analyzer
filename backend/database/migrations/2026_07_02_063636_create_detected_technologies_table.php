<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detected_technologies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('website_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('crawl_run_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('page_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('type');
            $table->string('name');
            $table->decimal('confidence', 3, 2);
            $table->text('evidence');

            $table->timestamps();

            $table->index(['crawl_run_id', 'type']);
            $table->index(['website_id', 'type']);
            $table->unique([
                'crawl_run_id',
                'page_id',
                'type',
                'name',
            ], 'detected_technologies_unique_detection');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detected_technologies');
    }
};