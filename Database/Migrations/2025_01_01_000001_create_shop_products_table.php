<?php

namespace App\Modules\Shop\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('category_id')->constrained('shop_categories')->onDelete('cascade');
            $table->enum('driver_type', ['vip', 'rcon', 'sourcebans', 'adminsystem', 'other'])->default('vip');
            $table->string('vip_group')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('apply_discount_forever')->default(false);
            $table->string('image')->nullable();
            $table->enum('server_mode', ['all', 'specific'])->default('specific');
            $table->json('features')->nullable();
            $table->text('description')->nullable();
            $table->integer('priority')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shop_product_server', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->foreignId('server_id')->constrained('servers')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['product_id', 'server_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_product_server');
        Schema::dropIfExists('shop_products');
        Schema::dropIfExists('shop_categories');
    }
};