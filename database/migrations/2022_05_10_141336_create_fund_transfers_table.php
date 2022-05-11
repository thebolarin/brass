<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('wallet_id')->index();
            $table->enum('type',['Inwards','Outwards','Withdrawal'])->index();
            $table->decimal('amount', 10, 2);
            $table->string('narration');
            $table->enum('status',['success','failed','processing'])->index();
            $table->string('payment_reference')->index();
            $table->string('provider')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fund_transfers');
    }
};
