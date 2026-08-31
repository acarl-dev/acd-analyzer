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
        Schema::create('robots_txt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crawl_run_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('url'); // Full URL to robots.txt
            $table->integer('status_code')->nullable(); // HTTP status (200, 404, 500, etc.)
            $table->boolean('exists')->default(false); // true if robots.txt was successfully fetched
            $table->text('content')->nullable(); // Raw robots.txt content
            $table->json('sitemaps')->nullable(); // Array of sitemap URLs found in robots.txt
            $table->json('rules')->nullable(); // Parsed rules per user-agent
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->index(['website_id', 'crawl_run_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('robots_txt');
    }
};
