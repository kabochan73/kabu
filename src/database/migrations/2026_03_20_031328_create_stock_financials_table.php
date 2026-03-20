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
        Schema::create('stock_financials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->string('fiscal_year')->nullable()->comment('会計年度');
            $table->bigInteger('revenue')->nullable()->comment('売上高');
            $table->bigInteger('operating_income')->nullable()->comment('営業利益');
            $table->bigInteger('net_income')->nullable()->comment('純利益');
            $table->decimal('eps', 10, 2)->nullable()->comment('一株当たり利益');
            $table->decimal('per', 8, 2)->nullable()->comment('株価収益率');
            $table->decimal('pbr', 8, 2)->nullable()->comment('株価純資産倍率');
            $table->decimal('dividend_yield', 6, 2)->nullable()->comment('配当利回り（%）');
            $table->bigInteger('market_cap')->nullable()->comment('時価総額');
            $table->date('fetched_at')->comment('取得日');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_financials');
    }
};
