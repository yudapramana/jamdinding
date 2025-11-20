<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDocTypesTable extends Migration {

	public function up()
	{
		Schema::create('doc_types', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->softDeletes();
			$table->enum('status', array('PNS', 'PPPK'));
			$table->text('type_name');
			$table->string('label', 20);
			$table->boolean('mandatory')->default(true);
			$table->boolean('multiple')->default(false);

		});
	}

	public function down()
	{
		Schema::drop('doc_types');
	}
}