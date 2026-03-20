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
        Schema::create('stock_news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->string('title')->comment('ニュースタイトル');
            $table->string('url')->comment('記事URL');
            $table->string('source')->nullable()->comment('メディア名');
            $table->timestamp('published_at')->nullable()->comment('公開日時');
            $table->unique(['stock_id', 'url']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_news');
    }
};
