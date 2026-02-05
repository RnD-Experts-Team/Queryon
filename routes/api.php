<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\NVT\EmployeesDataController;
use App\Http\Controllers\NVT\RDO_Data_Controller;
use App\Http\Controllers\NVT\LateEarlyController;
use Illuminate\Http\Request;

use App\Http\Controllers\Pizza\Health_Plan_Controller;
use App\Http\Controllers\Pizza\DepositDeliveryController;
use App\Http\Controllers\Pizza\LittleCaesarsHrDepartmentController;
use App\Http\Controllers\PizzaScheduleController;
use App\Http\Controllers\PizzaScheduleWHController;
use App\Http\Controllers\Pizza\DSQR_Controller;
use App\Http\Controllers\HealthPlan\HealthPlanController;
use App\Http\Controllers\Hiring\HiringRequestsController;
use App\Http\Controllers\Hiring\HiringRequestExportController;

use App\Http\Controllers\Hiring\HiringSeparationController;

use App\Http\Controllers\PizzaPayController;
use App\Http\Controllers\Pizza\PizzaCAPController;

use App\Http\Controllers\PizzaInventory\InventoryWebhookController;
use App\Http\Controllers\Pizza\ApprovalController;

use App\Http\Controllers\ReimbursementRequestController;

/**************************  NVT  **********************/
//********** Employees Data Form **************//
//create
Route::post('/employees-data', [EmployeesDataController::class, 'create']);
//update
Route::post('/employees-data/update', [EmployeesDataController::class, 'update']);
//delete
Route::post('/employees-data/delete', [EmployeesDataController::class, 'destroy']);

//********** RDO Data Form **************//
//create
Route::post('/rdo_data/create', [RDO_Data_Controller::class, 'create']);
//update
Route::post('/rdo_data/update', [RDO_Data_Controller::class, 'update']);
//delete
Route::post('/rdo_data/destroy', [RDO_Data_Controller::class, 'destroy']);

//********** Late_Early Data Form **************//
Route::post('/store-late-early', [LateEarlyController::class, 'store']);
Route::post('/update-late-early', [LateEarlyController::class, 'update']);
Route::post('/delete-late-early', [LateEarlyController::class, 'destroy']);


/**************************  PIZZA  **********************/

/**********HealthPlan************/
Route::post('/pizza/healthplan/create', [Health_Plan_Controller::class, 'create']);
Route::post('/pizza/healthplan/update', [Health_Plan_Controller::class, 'update']);
Route::post('/pizza/healthplan/delete', [Health_Plan_Controller::class, 'delete']);
Route::get('/pizza/healthplan/csv', [Health_Plan_Controller::class, 'exportToCsv']);

/****LITTLECAESARSHRDEPARTMENT*****/
Route::post('/pizza/littlecaesars/create', [LittleCaesarsHrDepartmentController::class, 'store']);
Route::post('/pizza/littlecaesars/update', [LittleCaesarsHrDepartmentController::class, 'update']);
Route::post('/pizza/littlecaesars/delete', [LittleCaesarsHrDepartmentController::class, 'destroy']);




/************* deposit delivery ************/

Route::post('pizza/deposit-delivery-data', [DepositDeliveryController::class, 'create']);
Route::post('/deposit-delivery/update', [DepositDeliveryController::class, 'update']);
Route::post('/deposit-delivery/delete', [DepositDeliveryController::class, 'destroy']);


//**************Exporters************/
//Csvs And excel endpoints
Route::middleware('check.secret')->group(function () {

    Route::get('/export', [EmployeesDataController::class, 'export']);
    Route::get('/rdo_data/export', [RDO_Data_Controller::class, 'export']);
    Route::get('/export-late-early', [LateEarlyController::class, 'export']);
    Route::get('/pizza/littlecaesars/export', [LittleCaesarsHrDepartmentController::class, 'export']);
    Route::get('/deposit-delivery/export', [DepositDeliveryController::class, 'export']);
    Route::get('/deposit-delivery/export/{start_date?}/{end_date?}/{franchisee_num?}', [DepositDeliveryController::class, 'export']);
    Route::get('/deposit-delivery/export-excel', [DepositDeliveryController::class, 'exportToExcel']);
    Route::get('/deposit-delivery/export-excel/{start_date?}/{end_date?}/{franchisee_num?}', [DepositDeliveryController::class, 'exportToExcel']);

    Route::get('hiring/export/requests-csv', [HiringRequestExportController::class, 'exportRequests']);
    Route::get('hiring/export/hires-csv', [HiringRequestExportController::class, 'exportHires']);
    Route::get('/pizza-pay/export', [PizzaPayController::class, 'exportCsv']);

    Route::get('hiring/separations/export', [HiringSeparationController::class, 'exportCsv']);

    Route::get('/pizza-cap-export-action-plans', [PizzaCAPController::class, 'exportActionPlans'])->name('pizza.cap.export.plans');
    Route::get('/pizza-cap-export-actions', [PizzaCAPController::class, 'exportActions'])->name('pizza.cap.export.actions');

    Route::get('/inventory/export', [InventoryWebhookController::class, 'exportCsv']);

    // Approvals Export
    Route::get('/approvals/export', [ApprovalController::class, 'exportCsv']);

    Route::get('/reimbursement-requests/export', [ReimbursementRequestController::class, 'exportCsv']);
});

// Json
Route::middleware('auth.verify')->group(function () {

    Route::get('/export-late-early/data', [LateEarlyController::class, 'getData']);
    Route::get('/get-data', [EmployeesDataController::class, 'getData']);
    Route::get('/rdo_data/data', [RDO_Data_Controller::class, 'getData']);
    Route::get('/pizza/healthplan/data', [Health_Plan_Controller::class, 'getData']);
    Route::get('/pizza/littlecaesars/data', [LittleCaesarsHrDepartmentController::class, 'getData']);
    Route::get('/deposit-delivery/get-data/{start_date?}/{end_date?}/{franchisee_num?}', [DepositDeliveryController::class, 'getData']);
    Route::get('/deposit-delivery/get-data', [DepositDeliveryController::class, 'getData']);

    // Approvals Data
    Route::get('/approvals/data', [ApprovalController::class, 'getData']);
});

Route::get('/deposit-delivery-dsqr/{store}/{date}', [DSQR_Controller::class, 'daily']);
Route::get('/deposit-delivery-dsqr-weekly/{store}/{startdate}/{enddate}', [DSQR_Controller::class, 'weekly']);

Route::post('/pizza-schedule', [PizzaScheduleController::class, 'store']);

Route::get('/pizza-schedule/{date?}', [PizzaScheduleController::class, 'exportCsv']);

Route::get('/pizza-schedule-wh/{date}', [PizzaScheduleWHController::class, 'exportCsv']);
Route::get('/pizza-schedule-wh/range/{start_date}/{end_date}', [PizzaScheduleWHController::class, 'exportCsvRange']);


Route::post('health-plan/applications', [HealthPlanController::class, 'create']);
Route::post('health-plan/applications-update', [HealthPlanController::class, 'update']);
Route::post('health-plan/applications-delete', [HealthPlanController::class, 'delete']);

Route::get('health-plan/applications-info-csv', [HealthPlanController::class, 'exportApplicationsInfo']);
Route::get('health-plan/applications-dependents-info-csv', [HealthPlanController::class, 'exportdependentsInfo']);

Route::post('hiring/applications-create', [HiringRequestsController::class, 'create']);
Route::post('hiring/applications-update', [HiringRequestsController::class, 'update']);
Route::post('hiring/applications-delete', [HiringRequestsController::class, 'delete']);

Route::post('/pizza-pay', [PizzaPayController::class, 'store']);

Route::prefix('hiring/separations')->group(function () {
    Route::post('/', [HiringSeparationController::class, 'create']);
    Route::post('/update', [HiringSeparationController::class, 'update']);
    Route::post('/delete', [HiringSeparationController::class, 'delete']);
});

Route::post('/pizza/cap/create', [PizzaCAPController::class, 'create'])->name('pizza.cap.create');
Route::post('/pizza/cap/update', [PizzaCAPController::class, 'update'])->name('pizza.cap.update');
Route::post('/pizza/cap/delete', [PizzaCAPController::class, 'delete'])->name('pizza.cap.delete');


/******************** HR Department ****************/

// Store Management Routes
use App\Http\Controllers\Pizza\HR_Department\StoreController;

Route::post('/stores/create', [StoreController::class, 'create']);
Route::post('/stores/update', [StoreController::class, 'update']);
Route::post('/stores/delete', [StoreController::class, 'delete']);

use App\Http\Controllers\Pizza\HR_Department\HrDepartmentController;

Route::post('/hr-department/create', [HrDepartmentController::class, 'create']);
Route::post('/hr-department/update', [HrDepartmentController::class, 'update']);
Route::post('/hr-department/delete', [HrDepartmentController::class, 'delete']);


/**************** Pizza Inventory Webhooks ****************/
Route::post('/inventory/create', [InventoryWebhookController::class, 'create']);
Route::post('/inventory/update', [InventoryWebhookController::class, 'update']);
Route::post('/inventory/delete', [InventoryWebhookController::class, 'delete']);


/**************** Approvals Cognito Webhooks ****************/

Route::post('/approvals/create', [ApprovalController::class, 'create']);
Route::post('/approvals/update', [ApprovalController::class, 'update']);
Route::post('/approvals/delete', [ApprovalController::class, 'delete']);


/**************** Reimbursement Requests ****************/

Route::post('/reimbursement-requests/create', [ReimbursementRequestController::class, 'create']);
Route::post('/reimbursement-requests/update', [ReimbursementRequestController::class, 'update']);
Route::post('/reimbursement-requests/delete', [ReimbursementRequestController::class, 'delete']);
