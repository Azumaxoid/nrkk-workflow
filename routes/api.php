<?php

use App\Http\Controllers\ApprovalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Bulk Approval API endpoints
    Route::post('/approvals/bulk-approve', [ApprovalController::class, 'bulkApprove']);
    Route::post('/approvals/bulk-reject', [ApprovalController::class, 'bulkReject']);

    // Approve All API endpoints
    Route::post('/approvals/approve-all', [ApprovalController::class, 'approveAll']);
    Route::post('/approvals/reject-all', [ApprovalController::class, 'rejectAll']);
});