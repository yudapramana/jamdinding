<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;

class CreateForeignKeys extends Migration {

	public function up()
	{
		// Schema::table('users', function(Blueprint $table) {
		// 	$table->foreign('id_employee')->references('id')->on('employees')
		// 				->onDelete('restrict')
		// 				->onUpdate('restrict');
		// });
		// Schema::table('employees', function(Blueprint $table) {
		// 	$table->foreign('id_work_unit')->references('id')->on('work_units')
		// 				->onDelete('restrict')
		// 				->onUpdate('restrict');
		// });
		// Schema::table('emp_documents', function(Blueprint $table) {
		// 	$table->foreign('id_doc_type')->references('id')->on('doc_types')
		// 				->onDelete('restrict')
		// 				->onUpdate('restrict');
		// });
		// Schema::table('emp_documents', function(Blueprint $table) {
		// 	$table->foreign('id_employee')->references('id')->on('employees')
		// 				->onDelete('restrict')
		// 				->onUpdate('restrict');
		// });
		// Schema::table('verval_logs', function(Blueprint $table) {
		// 	$table->foreign('id_document')->references('id')->on('emp_documents')
		// 				->onDelete('restrict')
		// 				->onUpdate('restrict');
		// });
		// Schema::table('verval_logs', function(Blueprint $table) {
		// 	$table->foreign('verified_by')->references('id')->on('users')
		// 				->onDelete('restrict')
		// 				->onUpdate('restrict');
		// });
	}

	public function down()
	{
		Schema::table('users', function(Blueprint $table) {
			$table->dropForeign('users_id_employee_foreign');
		});
		Schema::table('employees', function(Blueprint $table) {
			$table->dropForeign('employees_id_work_unit_foreign');
		});
		Schema::table('emp_documents', function(Blueprint $table) {
			$table->dropForeign('emp_documents_id_doc_type_foreign');
		});
		Schema::table('emp_documents', function(Blueprint $table) {
			$table->dropForeign('emp_documents_id_employee_foreign');
		});
		Schema::table('verval_logs', function(Blueprint $table) {
			$table->dropForeign('verval_logs_id_document_foreign');
		});
		Schema::table('verval_logs', function(Blueprint $table) {
			$table->dropForeign('verval_logs_verified_by_foreign');
		});
	}
}