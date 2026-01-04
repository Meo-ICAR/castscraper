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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('sources')->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('company')->nullable();
            $table->string('location')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->default('Italy');
            $table->dateTime('date_posted')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->string('url')->nullable();
            $table->string('content_hash')->nullable()->index();
            $table->boolean('canonical')->default(false);
            $table->timestamp('scraped_at')->nullable();
            $table->timestamp('parsed_at')->nullable();
            $table->longText('raw_html')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();

            $table->unique(['source_id', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
