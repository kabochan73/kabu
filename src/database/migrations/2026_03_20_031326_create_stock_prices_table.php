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
        Schema::create('stock_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->date('date')->comment('取得日');
            $table->decimal('close', 10, 2)->nullable()->comment('終値');
            $table->decimal('open', 10, 2)->nullable()->comment('始値');
            $table->decimal('high', 10, 2)->nullable()->comment('高値');
            $table->decimal('low', 10, 2)->nullable()->comment('安値');
            $table->bigInteger('volume')->nullable()->comment('出来高');
            $table->decimal('previous_close', 10, 2)->nullable()->comment('前日終値');
            $table->decimal('change', 10, 2)->nullable()->comment('前日比（円）');
            $table->decimal('change_percent', 6, 2)->nullable()->comment('前日比（%）');
            $table->decimal('week52_high', 10, 2)->nullable()->comment('52週高値');
            $table->decimal('week52_low', 10, 2)->nullable()->comment('52週安値');
            $table->decimal('ma5', 10, 2)->nullable()->comment('5日移動平均');
            $table->decimal('ma25', 10, 2)->nullable()->comment('25日移動平均');
            $table->decimal('ma75', 10, 2)->nullable()->comment('75日移動平均');
            $table->unique(['stock_id', 'date']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_prices');
    }
};
