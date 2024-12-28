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
        Schema::create('bill_order', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('bill_id')->unsigned()->index();
            $table->foreign('bill_id')->references('id')->on('bill')->onDelete('cascade');

            $table->integer('product_id')->unsigned()->index();
            $table->foreign('product_id')->references('id')->on('product')->onDelete('cascade');

            $table->decimal('price', 10, 2);
            $table->decimal('value', 10, 2);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_order');
    }
};
