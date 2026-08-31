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
        Schema::create('sitemap_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sitemap_id')->constrained()->cascadeOnDelete();
            $table->text('url'); // Original URL from sitemap
            $table->text('normalized_url'); // Normalized URL via UrlNormalizer
            $table->timestamp('lastmod')->nullable(); // Last modification date from sitemap
            $table->string('changefreq')->nullable(); // Change frequency (always, hourly, daily, etc.)
            $table->decimal('priority', 3, 2)->nullable(); // Priority 0.0-1.0
            $table->timestamps();

            $table->index('sitemap_id');
            $table->index('normalized_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sitemap_urls');
    }
};
