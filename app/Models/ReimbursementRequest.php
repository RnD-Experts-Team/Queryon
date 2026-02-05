<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'store_manager_full_name',
        'manager_consulted_full_name',
        'employee_full_name',
        'store_label',
        'expense_date',
        'expense_description',
        'expenses_amount',
        'group_manager_full_name',
        'approve',
        'notes',
        'rejection_reason',
        'bi_full_name',
        'bi_approve',
        'bi_notes',
        'bi_rejection_reason',
    ];
}
