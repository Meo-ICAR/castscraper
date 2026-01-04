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
        Schema::table('scraping_keywords', function (Blueprint $table) {
            if (!Schema::hasColumn('scraping_keywords', 'category')) {
                $table->string('category')->after('id')->index();
            }
            if (!Schema::hasColumn('scraping_keywords', 'priority')) {
                $table->integer('priority')->default(0)->after('keyword');
            }
            if (!Schema::hasColumn('scraping_keywords', 'technical_notes')) {
                $table->text('technical_notes')->nullable()->after('priority');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scraping_keywords', function (Blueprint $table) {
            $table->dropColumn(['category', 'priority', 'technical_notes']);
        });
    }
};
