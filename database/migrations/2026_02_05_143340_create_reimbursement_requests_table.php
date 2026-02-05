<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReimbursementRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('reimbursement_requests', function (Blueprint $table) {
            $table->id();
            $table->string('form_id');
            $table->string('store_manager_full_name');
            $table->string('manager_consulted_full_name');
            $table->string('employee_full_name');
            $table->string('store_label');
            $table->date('expense_date');
            $table->text('expense_description');
            $table->decimal('expenses_amount', 10, 2);
            $table->string('group_manager_full_name')->nullable();
            $table->string('approve')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('bi_full_name')->nullable();
            $table->string('bi_approve')->nullable();
            $table->text('bi_notes')->nullable();
            $table->text('bi_rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reimbursement_requests');
    }
}
