<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVervalLogsTable extends Migration {

	public function up()
	{
		Schema::create('verval_logs', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->integer('id_document')->unsigned();
			$table->integer('verified_by')->unsigned();
			$table->enum('verval_status', array(
				'Uploaded', 
				'Reuploaded', 
				'Approved', 
				'Rejected',
				'Uploaded by Admin',
				'Reuploaded by Admin',
				'Request Change'
			));
			$table->text('verif_notes')->nullable();
		});
	}

	public function down()
	{
		Schema::drop('verval_logs');
	}
}