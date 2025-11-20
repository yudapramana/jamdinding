<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEmpDocumentsTable extends Migration {

	public function up()
	{
		Schema::create('emp_documents', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->softDeletes();
			$table->integer('id_doc_type')->unsigned();
			$table->string('doc_number', 50)->nullable();
			$table->date('doc_date')->nullable();
			$table->string('file_name', 255)->unique();
			$table->string('file_path', 255);
			$table->string('parameter')->nullable();
			$table->integer('id_employee')->unsigned();
			$table->enum('status', array('Pending', 'Approved', 'Rejected'))->default('Pending');
			$table->text('verif_notes')->nullable();
		});
	}

	public function down()
	{
		Schema::drop('emp_documents');
	}
}