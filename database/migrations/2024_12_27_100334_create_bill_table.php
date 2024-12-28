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
        Schema::create('bill', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('prison_id')->unsigned()->index();
            $table->foreign('prison_id')->references('id')->on('prison')->onDelete('cascade');

            $table->integer('company_id')->unsigned()->index();
            $table->foreign('company_id')->references('id')->on('company')->onDelete('cascade');
            
            $table->string('code', 255)->nullable()->charset('utf8');
            $table->enum('bill_type', [1,2])->default(1);
            $table->string('date', 250)->charset('utf8')->nullable();
            $table->decimal('sum_income', 10, 2);
            $table->decimal('sum_expense', 10, 2);
            $table->decimal('sum_total', 10, 2);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill');
    }
};
