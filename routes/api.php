<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Cashier\CashierController;
use App\Http\Controllers\Credential\CredentialController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Document\DocumentController;
use App\Http\Controllers\Registrar\RegistrarController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', [UserController::class, 'user']);

    Route::get('/dashboard/get-user-count', [DashboardController::class, 'getUserCount']);
    Route::get('/dashboard/get-record-count', [DashboardController::class, 'getRecordCount']);
    Route::get('/dashboard/get-request-count', [DashboardController::class, 'getRequestCount']);
    Route::get('/dashboard/get-paid-count', [DashboardController::class, 'getPaidCount']);
    Route::get('/dashboard/get-sales-count', [DashboardController::class, 'getSalesCount']);

    Route::post('/registrar/import-student', [RegistrarController::class, 'importStudent']);
    Route::get('/registrar/export-student', [RegistrarController::class, 'exportStudent']);
    Route::get('/registrar/get-student', [RegistrarController::class, 'getStudent']);
    Route::get('/registrar/get-student-information', [RegistrarController::class, 'getStudentInformation']);
    Route::post('/registrar/edit-email-address', [RegistrarController::class, 'editEmailAddress']);
    Route::post('/registrar/add-staff', [RegistrarController::class, 'addStaff']);
    Route::get('/registrar/get-staff', [RegistrarController::class, 'getStaff']);
    Route::get('/registrar/get-staff-information', [RegistrarController::class, 'getStaffInformation']);
    Route::post('/registrar/change-staff-status', [RegistrarController::class, 'changeStaffStatus']);
    Route::get('/registrar/get-record', [RegistrarController::class, 'getRecord']);
    Route::get('/registrar/get-requirement', [RegistrarController::class, 'getRequirement']);
    Route::get('/registrar/get-softcopy', [RegistrarController::class, 'getSoftCopy']);
    Route::post('/registrar/confirm-submit', [RegistrarController::class, 'confirmSubmit']);
    Route::post('/registrar/decline-submit', [RegistrarController::class, 'declineSubmit']);
    Route::get('/registrar/get-credential-request', [RegistrarController::class, 'getCredentialRequest']);
    Route::get('/registrar/get-request-detail', [RegistrarController::class, 'getRequestDetail']);
    Route::post('/registrar/edit-page', [RegistrarController::class, 'editPage']);
    Route::post('/registrar/request-confirm', [RegistrarController::class, 'requestConfirm']);
    Route::post('/registrar/request-decline', [RegistrarController::class, 'requestDecline']);
    Route::post('/registrar/request-process', [RegistrarController::class, 'requestProcess']);
    Route::post('/registrar/request-finish', [RegistrarController::class, 'requestFinish']);
    Route::post('/registrar/request-release', [RegistrarController::class, 'requestRelease']);
    Route::get('/registrar/get-complete-report', [RegistrarController::class, 'getCompleteReport']);
    Route::post('/registrar/cancel-request', [RegistrarController::class, 'cancelRequest']);
    Route::get('/registrar/get-request-notif', [RegistrarController::class, 'getRequestNotif']);
    Route::get('/registrar/get-document-notif', [RegistrarController::class, 'getDocumentNotif']);
    Route::post('/registrar/read-document-notif', [RegistrarController::class, 'readDocumentNotif']);
    Route::get('/registrar/get-credential-notif', [RegistrarController::class, 'getCredentialNotif']);
    Route::post('/registrar/read-credential-notif', [RegistrarController::class, 'readCredentialNotif']);

    Route::get('/cashier/get-credential-request', [CashierController::class, 'getCredentialRequest']);
    Route::get('/cashier/get-request-detail', [CashierController::class, 'getRequestDetail']);
    Route::post('/cashier/request-confirm', [CashierController::class, 'requestConfirm']);
    Route::get('/cashier/get-paid-report', [CashierController::class, 'getPaidReport']);
    Route::get('/cashier/get-pay-notif', [CashierController::class, 'getPayNotif']);

    Route::get('/document/get-student-type', [DocumentController::class, 'getStudentType']);
    Route::get('/document/get-document', [DocumentController::class, 'getDocument']);
    Route::post('/document/create-document', [DocumentController::class, 'createDocument']);
    Route::post('/document/update-document', [DocumentController::class, 'updateDocument']);
    Route::post('/document/remove-document', [DocumentController::class, 'removeDocument']);

    Route::get('/credential/get-credential', [CredentialController::class, 'getCredential']);
    Route::post('/credential/create-credential', [CredentialController::class, 'createCredential']);
    Route::post('/credential/update-credential', [CredentialController::class, 'updateCredential']);
    Route::get('/credential/get-purpose', [CredentialController::class, 'getPurpose']);
    Route::post('/credential/create-purpose', [CredentialController::class, 'createPurpose']);
    Route::post('/credential/update-purpose', [CredentialController::class, 'updatePurpose']);
    Route::get('/credential/get-link', [CredentialController::class, 'getLink']);
    Route::get('/credential/get-student-link', [CredentialController::class, 'getStudentLink']);

    Route::get('/student/get-requirement', [StudentController::class, 'getRequirement']);
    Route::post('/student/submit-requirement', [StudentController::class, 'submitRequirement']);
    Route::post('/student/resubmit-requirement', [StudentController::class, 'resubmitRequirement']);
    Route::get('/student/get-softcopy', [StudentController::class, 'getSoftCopy']);
    Route::get('/student/get-record-status', [StudentController::class, 'getRecordStatus']);
    Route::post('/student/request-credential', [StudentController::class, 'requestCredential']);
    Route::post('/student/request-again-credential', [StudentController::class, 'requestAgainCredential']);
    Route::get('/student/get-request-count', [StudentController::class, 'getRequestCount']);
    Route::get('/student/get-request-status', [StudentController::class, 'getRequestStatus']);
    Route::get('/student/get-request-detail', [StudentController::class, 'getRequestDetail']);
    Route::post('/student/cancel-request', [StudentController::class, 'cancelRequest']);
    Route::post('/student/request-claim', [StudentController::class, 'requestClaim']);
    Route::get('/student/get-payment-status', [StudentController::class, 'getPaymentStatus']);
    Route::get('/student/get-document-notif', [StudentController::class, 'getDocumentNotif']);
    Route::post('/student/read-document-notif', [StudentController::class, 'readDocumentNotif']);
    Route::get('/student/get-credential-notif', [StudentController::class, 'getCredentialNotif']);
    Route::post('/student/read-credential-notif', [StudentController::class, 'readCredentialNotif']);
    Route::get('/student/notif-count', [StudentController::class, 'notifCount']);

    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/logout', [AuthController::class, 'logout']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/create-new-password', [AuthController::class, 'createNewPassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);