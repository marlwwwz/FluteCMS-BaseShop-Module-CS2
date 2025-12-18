<?php

namespace App\Modules\Shop\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['percentage', 'fixed', 'category'])->default('percentage');
            $table->decimal('value', 10, 2);
            $table->foreignId('category_id')->nullable()->constrained('shop_categories')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('shop_products')->onDelete('cascade');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('shop_product_discount', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->foreignId('discount_id')->constrained('shop_discounts')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['product_id', 'discount_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_product_discount');
        Schema::dropIfExists('shop_discounts');
    }
};