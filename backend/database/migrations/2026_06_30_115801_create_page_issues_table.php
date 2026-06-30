<?php

use App\Models\CrawlError;
use App\Models\CrawlRun;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_issues', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(CrawlRun::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Page::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignIdFor(CrawlError::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->text('url');

            $table->string('code')->index();
            $table->string('severity')->index();
            $table->text('message');

            $table->json('context')->nullable();
            $table->string('analyzer_version')->nullable();

            $table->timestamps();

            $table->index(['crawl_run_id', 'severity']);
            $table->index(['crawl_run_id', 'code']);
            $table->index(['page_id', 'code']);
            $table->index(['crawl_error_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_issues');
    }
};