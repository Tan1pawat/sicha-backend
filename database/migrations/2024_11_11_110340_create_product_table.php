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
        Schema::create('product', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name', 255)->charset('utf8');
            $table->string('code', 255)->nullable()->charset('utf8');
            $table->string('image', 255)->nullable()->charset('utf8');
            $table->decimal('price', 10, 2);
            $table->decimal('value', 10, 2);
            $table->integer('unit_id')->unsigned()->index();
            $table->foreign('unit_id')->references('id')->on('unit')->onDelete('cascade');

            $table->integer('product_type_id')->unsigned()->index();
            $table->foreign('product_type_id')->references('id')->on('product_type')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};
