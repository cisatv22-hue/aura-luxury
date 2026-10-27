<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('coupon_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
        });

        Schema::create('abandoned_carts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('email')->index();
            $table->json('items');
            $table->timestamp('consented_at');
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
            $table->index(['reminder_sent_at', 'recovered_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
        Schema::table('orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('coupon_id'));
    }
};
