<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        Schema::table('detected_technologies', function (Blueprint $table) {
            // Make type nullable (deprecated, replaced by category)
            $table->string('type')->nullable()->change();
            
            $table->string('slug')->nullable()->after('name')->index();
            $table->string('category')->nullable()->after('slug')->index();
            // Change existing confidence column from decimal to string
            $table->string('confidence')->default('medium')->change();
            $table->string('version')->nullable()->after('confidence');
            $table->json('sources')->nullable()->after('evidence');
            $table->unsignedInteger('detected_on_pages')->nullable()->after('sources')->default(1);
        });
        
        // Convert evidence from text to json - database-specific handling
        if ($driver === 'pgsql') {
            // PostgreSQL requires explicit USING clause
            DB::statement('ALTER TABLE detected_technologies ALTER COLUMN evidence TYPE json USING evidence::json');
            DB::statement('ALTER TABLE detected_technologies ALTER COLUMN evidence DROP NOT NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite: evidence column already exists, we just change its semantics
            // SQLite stores JSON as text anyway, so no migration needed
        } else {
            // MySQL and others: use Laravel's schema builder
            Schema::table('detected_technologies', function (Blueprint $table) {
                $table->json('evidence')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        Schema::table('detected_technologies', function (Blueprint $table) {
            // Drop newly added columns
            $table->dropColumn([
                'slug',
                'category',
                'version',
                'sources',
                'detected_on_pages',
            ]);
            
            // Revert type to NOT NULL
            $table->string('type')->nullable(false)->change();
            // Revert confidence to decimal
            $table->decimal('confidence', 3, 2)->change();
        });
        
        // Convert evidence back from json to text - database-specific handling
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE detected_technologies ALTER COLUMN evidence TYPE text');
            DB::statement('ALTER TABLE detected_technologies ALTER COLUMN evidence SET NOT NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite: no change needed, already text-based
        } else {
            // MySQL and others
            Schema::table('detected_technologies', function (Blueprint $table) {
                $table->text('evidence')->change();
            });
        }
    }
};
