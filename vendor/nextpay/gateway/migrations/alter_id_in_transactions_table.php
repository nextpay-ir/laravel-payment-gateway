<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class AlterIdInTransactionsTable extends Migration
{

	function getTable()
	{
		return config('gateway.table', 'nextpay_gateway_transactions');
	}

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		try {		
			DB::statement("update `" . $this->getTable() . "` set `payment_date`=null WHERE  `payment_date`=0;");
			DB::statement("ALTER TABLE `" . $this->getTable() . "` CHANGE `id` `id` BIGINT UNSIGNED NOT NULL;");
		} catch (Exception $e) {
			
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE `" . $this->getTable() . "` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL;");
	}
}
