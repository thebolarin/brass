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
            $table->foreignUuid('user_id')->index()->constrained(); 
            $table->foreignUuid('wallet_id')->index()->constrained(); 
            $table->enum('status',['success','failed','processing']);
            $table->string('provider')->nullable();
            $table->enum('type',['Inwards','Outwards','Withdrawal']);
            $table->integer('amount');
            $table->string('narration');
            $table->string('payment_reference');
            
            $table->timestamps();
        });

        DB::statement("ALTER TABLE fund_transfers ADD COLUMN searchtext TSVECTOR");
        DB::statement("UPDATE fund_transfers SET searchtext = to_tsvector(COALESCE('english', status, provider, type, payment_reference))");
        DB::statement("CREATE INDEX searchtext_gin ON fund_transfers USING GIN(searchtext)");
        DB::statement("CREATE TRIGGER ts_searchtext BEFORE INSERT OR UPDATE ON fund_transfers FOR EACH ROW EXECUTE PROCEDURE tsvector_update_trigger('searchtext', 'pg_catalog.english', 'status', 'provider', 'type', 'payment_reference')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP TRIGGER IF EXISTS tsvector_update_trigger ON fund_transfers");
        DB::statement("DROP INDEX IF EXISTS searchtext_gin");
        DB::statement("ALTER TABLE fund_transfers DROP COLUMN searchtext");
        Schema::dropIfExists('fund_transfers');
    }
};
