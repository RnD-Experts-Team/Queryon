<?php

namespace App\Http\Controllers;

use App\Models\ReimbursementRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class ReimbursementRequestController extends Controller
{
    /**
     * Store a new reimbursement request
     */
    public function create(Request $request)
    {
        return $this->upsertReimbursementRequest($request, 'store');
    }

    /**
     * Update an existing reimbursement request
     */
    public function update(Request $request)
    {
        return $this->upsertReimbursementRequest($request, 'update');
    }

    /**
     * Delete a reimbursement request
     */
    public function delete(Request $request)
    {
        $data = $request->json()->all();

        $formId = data_get($data, 'Id');
        if (!$formId) {
            return response()->json(['status' => 'error', 'message' => 'Missing Id'], 400);
        }

        ReimbursementRequest::where('form_id', $formId)->delete();


        return response()->json(['status' => 'deleted']);
    }

    /**
     * Export CSV of all reimbursement requests
     */
    public function exportCsv()
    {
        $requests = ReimbursementRequest::all();

        $headers = [
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

        $filename = 'employee_reimbursement_requests_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return Response::streamDownload(function () use ($requests, $headers) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM for Excel/Arabic correctness
            fprintf($out, "\xEF\xBB\xBF");

            fputcsv($out, $headers);

            foreach ($requests as $request) {
                fputcsv($out, [
                    $request->form_id,
                    $request->store_manager_full_name,
                    $request->manager_consulted_full_name,
                    $request->employee_full_name,
                    $request->store_label,
                    $request->expense_date,
                    $request->expense_description,
                    $request->expenses_amount,
                    $request->group_manager_full_name,
                    $request->approve,
                    $request->notes,
                    $request->rejection_reason,
                    $request->bi_full_name,
                    $request->bi_approve,
                    $request->bi_notes,
                    $request->bi_rejection_reason,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Core upsert logic for store and update
     */
    private function upsertReimbursementRequest(Request $request, string $endpointType)
    {
        $data = $request->json()->all();


        // Validate required pieces
        $validator = Validator::make($data, [
            'Id' => 'required|string',
            'SM.ExpenseDetails.ExpenseDate' => 'required|date',
            'SM.ExpenseDetails.ExpenseDescription' => 'required|string',
            'SM.ExpenseDetails.ExpensesAmount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid payload',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = ReimbursementRequest::updateOrCreate(
                ['form_id' => data_get($data, 'Id')],
                [
                    'form_id' => data_get($data, 'Id'),
                    'store_manager_full_name' => data_get($data, 'SM.StoreManagerName.FirstAndLast'),
                    'manager_consulted_full_name' => data_get($data, 'SM.NameTheManagerWhoYouConsulted.FirstAndLast'),
                    'employee_full_name' => data_get($data, 'SM.EmployeeInfo.EmployeeFullName.FirstAndLast'),
                    'store_label' => data_get($data, 'SM.EmployeeInfo.Store2.Label'),
                    'expense_date' => data_get($data, 'SM.ExpenseDetails.ExpenseDate'),
                    'expense_description' => data_get($data, 'SM.ExpenseDetails.ExpenseDescription'),
                    'expenses_amount' => data_get($data, 'SM.ExpenseDetails.ExpensesAmount'),
                    'group_manager_full_name' => data_get($data, 'GM.Name.FirstAndLast'),
                    'approve' => data_get($data, 'GM.Approve'),
                    'notes' => data_get($data, 'GM.Notes'),
                    'rejection_reason' => data_get($data, 'GM.RejectionReason'),
                    'bi_full_name' => data_get($data, 'BI.Name.FirstAndLast'),
                    'bi_approve' => data_get($data, 'BI.Approve'),
                    'bi_notes' => data_get($data, 'BI.Notes'),
                    'bi_rejection_reason' => data_get($data, 'BI.RejectionReason'),
                ]
            );

            return response()->json([
                'status' => $endpointType === 'store' ? 'created' : 'updated',
                'form_id' => $result->form_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Reimbursement request webhook failed', [
                'endpoint' => $endpointType,
                'form_id' => data_get($data, 'Id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Webhook processing failed'], 500);
        }
    }
}
