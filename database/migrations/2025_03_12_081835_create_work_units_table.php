<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWorkUnitsTable extends Migration {

	public function up()
	{
		Schema::create('work_units', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->softDeletes();
			$table->string('unit_name', 255)->unique();
			$table->string('unit_code', 25)->unique();
			$table->integer('parent_unit')->unsigned()->nullable();
		});
	}

	public function down()
	{
		Schema::drop('work_units');
	}
}