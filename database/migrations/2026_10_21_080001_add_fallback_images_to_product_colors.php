<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_colors', function (Blueprint $table): void {
            $table->string('image_path')->nullable()->after('hex');
            $table->string('image_alt')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('product_colors', function (Blueprint $table): void {
            $table->dropColumn(['image_path', 'image_alt']);
        });
    }
};
