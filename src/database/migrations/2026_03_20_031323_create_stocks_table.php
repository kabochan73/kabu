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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('ticker')->unique()->comment('銘柄コード（例: 7203.T）');
            $table->string('name')->comment('銘柄名');
            $table->string('market')->default('東証')->comment('市場');
            $table->boolean('is_active')->default(true)->comment('ウォッチリスト有効');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
