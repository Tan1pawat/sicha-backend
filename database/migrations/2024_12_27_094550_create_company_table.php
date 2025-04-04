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
        Schema::create('company', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name', 255)->charset('utf8');
            $table->string('code', 255)->charset('utf8');
            $table->text('sender_address')->nullable()->charset('utf8');
            $table->text('sender_tax_number')->nullable()->charset('utf8');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company');
    }
};
