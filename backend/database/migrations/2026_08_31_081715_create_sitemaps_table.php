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
        Schema::create('sitemaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crawl_run_id')->constrained()->cascadeOnDelete();
            $table->text('url'); // Full URL to sitemap (e.g., https://example.com/sitemap.xml)
            $table->integer('status_code')->nullable(); // HTTP status (200, 404, 500, etc.)
            $table->enum('type', ['urlset', 'index', 'unknown'])->default('unknown'); // Sitemap type
            $table->boolean('exists')->default(false); // true if sitemap was successfully fetched
            $table->string('content_type')->nullable(); // Content-Type header
            $table->text('error')->nullable(); // Error message if fetch/parse failed
            $table->foreignId('parent_sitemap_id')->nullable()->constrained('sitemaps')->cascadeOnDelete(); // For nested sitemaps
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->index(['website_id', 'crawl_run_id']);
            $table->index('parent_sitemap_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sitemaps');
    }
};
