<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmpDocumentController;
use App\Http\Controllers\Admin\MasterController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\PrintController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VervalLogController;
use App\Http\Controllers\API\DocumentLogController;
use App\Http\Controllers\API\WorkUnitController;
use App\Http\Controllers\API\EventController as ApiEventController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\OauthController;
use App\Http\Controllers\PublicDocController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\User\DocumentController;
use App\Http\Controllers\User\EmployeeDocumentController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\Auth\PasswordResetWhatsappController;
use App\Http\Controllers\Auth\CaptchaController;
use App\Http\Controllers\Guest\EventVenueController;
use App\Http\Controllers\MandateDocController;
use App\Http\Controllers\PublicParticipantController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\PageController;

/*
|--------------------------------------------------------------------------
| Web Routes (Statis & Views)
|--------------------------------------------------------------------------
*/
Route::redirect('/', '/landing');
Route::get('/landing', [PageController::class, 'landing'])->name('landing');
Route::view('/terms-and-conditions', 'tnc')->name('tnc');
Route::view('/terms-of-service', 'tos')->name('tos');
Route::view('/privacy-policy', 'privacy_policy')->name('privacy');

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/captcha', [CaptchaController::class, 'generate'])->name('captcha');

// OAuth & Auth
Route::get('oauth/google', [OauthController::class, 'redirectToProvider'])->name('oauth.google');  
Route::get('oauth/google/callback', [OauthController::class, 'handleProviderCallback'])->name('oauth.google.callback');
Route::get('/login/google/redirect', [SocialiteController::class, 'redirect'])->name('google.redirect');
Route::get('/login/google/callback', [SocialiteController::class, 'callback'])->name('google.callback');

// Password Reset via WhatsApp
Route::controller(PasswordResetWhatsappController::class)->group(function () {
    Route::get('/password/wa/request', 'showRequestForm')->name('password.wa.request');
    Route::get('/password/wa/reset', 'showResetForm')->name('password.wa.reset');
    Route::post('/password/wa/reset', 'resetPassword')->name('password.wa.reset.submit');
});

// WA Testing
Route::post('/wa/send', [WhatsAppController::class, 'send']);
Route::get('/wa/sendget', [WhatsAppController::class, 'sendGet']);

/*
|--------------------------------------------------------------------------
| Event & Venues
|--------------------------------------------------------------------------
*/
Route::controller(EventVenueController::class)->group(function () {
    Route::get('/event-venues', 'index')->name('event.venues');
    Route::get('/event-schedules', 'schedules');
    Route::get('/event-venues-json', 'venuesJson');
    Route::get('/event-venues-details', 'venuesDetails');
});

Route::get('/api/events/by-key/{key}', [ApiEventController::class, 'byKey']);

/*
|--------------------------------------------------------------------------
| Participants (Public Access)
|--------------------------------------------------------------------------
*/
Route::middleware(['throttle:kokarde'])->get('/participant/{eventParticipant:uuid}/kokarde', [PublicParticipantController::class, 'printKokarde'])->name('public.participants.kokarde');
Route::middleware(['throttle:participant-public'])->get('/participant/{eventParticipant:uuid}', [PublicParticipantController::class, 'show'])->name('public.participant.show');

/*
|--------------------------------------------------------------------------
| Utility & Debugging (Sebaiknya diproteksi jika untuk production)
|--------------------------------------------------------------------------
*/
Route::controller(UtilityController::class)->group(function () {
    Route::get('/hierarki-mtq', 'hierarkiMtq');
    Route::get('/test-run', 'testRun');
    Route::get('/test-log', 'testLog'); // Digabung dari 2 route yang sama
    Route::get('/raw-log', 'rawLog');
    Route::get('/health', 'health');
    Route::get('/log-test', 'logTest');
    Route::get('/env-check', 'envCheck');
    Route::get('/testgrup', 'testGrup');
    Route::get('/get-gdrive-file', 'getGdriveFile');
    Route::get('/show-duplicates', 'showDuplicates');
    Route::get('/delete-duplicates', 'deleteDuplicates'); // Dihapus duplikatnya, cukup 1
    Route::get('/checkfile', 'checkFile');
    Route::get('/getfiles', 'getFiles');
    Route::get('/checkfiles', 'checkFiles');
    Route::get('/set-admin', 'setAdmin');
    Route::get('/logout_all', 'logoutAll');
    Route::get('/get-password', 'getPassword');
    Route::get('/all-users', 'allUsers');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    
    // Auto Verval
    Route::get('/auto-verval', [EmpDocumentController::class, 'autoVerval']);

    // Monitoring / SIGARDA
    Route::controller(MonitoringController::class)->group(function () {
        Route::get('/sigarda-employees-status', 'sigardaEmployeesStatus');
        Route::get('/count-unique-employees', 'countUniqueEmployees');
        Route::get('/update-progress-dokumen', 'updateProgressDokumen');
        Route::get('/show-verval-champion-hide', 'showVervalChampionHide');
        Route::get('/show-pending-documents', 'showPendingDocuments');
    });

    // Secure Documents (Dokumen Peserta) dengan filename opsional
    Route::get('/secure/documents/{uuid}/{filename?}', [PublicDocController::class, 'stream'])
        ->where([
            'uuid'     => '[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}',
            'filename' => '[^/]+',
        ])
        ->name('secure.docs.stream');

    // Catch-all untuk format URL salah di bawah /secure/documents/
    Route::get('/secure/documents/{any?}', [PublicDocController::class, 'invalidFormat'])
        ->where('any', '.*');

    // Secure Mandates
    Route::get('/secure/mandates/{event}/{filename}', [MandateDocController::class, 'stream'])
        ->where(['event' => '[0-9]+', 'filename' => '[^/]+'])
        ->name('secure.mandates.stream');

    // API & Admin Routes
    Route::get('api/preview/pdf', [EmpDocumentController::class, 'show'])->name('pdf.preview');
    Route::get('/api/verval-logs', [VervalLogController::class, 'index']);
    Route::get('/api/reports', [ReportController::class, 'index']);
    Route::get('/api/stats/all', [DashboardController::class, 'all']);
    Route::get('/api/stats/reports', [DashboardController::class, 'reports']);
    Route::get('/api/fusers', [UserController::class, 'fetch']);

    // Organizations
    Route::controller(OrganizationController::class)->group(function () {
        Route::get('/api/fetch-orgs', 'fetch');
        Route::get('/api/orgs', 'index');
        Route::post('api/orgs', 'store');
        Route::put('api/orgs/{org}', 'update');
        Route::delete('/api/orgs/{org}', 'destroy');
        Route::delete('/api/orgs', 'bulkDelete');
    });

    // Users
    Route::controller(UserController::class)->group(function () {
        Route::get('/api/users', 'index');
        Route::post('api/users', 'store');
        Route::put('api/users/{user}', 'update');
        Route::delete('/api/users/{user}', 'destroy');
        Route::patch('/api/users/{user}/change-role', 'changeRole');
        Route::delete('/api/users', 'bulkDelete');
    });

    // Reports
    Route::controller(ReportController::class)->group(function () {
        Route::get('/api/reports', 'index');
        Route::post('/api/reports/create', 'store');
        Route::get('/api/reports/{work}/edit', 'edit');
        Route::put('/api/reports/{work}/edit', 'update');
        Route::delete('/api/reports/{work}', 'destroy');
        Route::delete('/api/parent-reports/{report}', 'destroyParent');
    });

    // Settings
    Route::get('/api/settings', [SettingController::class, 'index']);
    Route::post('/api/settings', [SettingController::class, 'update']);

    // Profiles
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/api/profile', 'index');
        Route::get('/api/docs-update-state', 'docsUpdateState');
        Route::put('/api/profile', 'update');
        Route::post('/api/upload-profile-image', 'uploadImage');
        Route::post('/api/change-user-password', 'changePassword');
    });

    // Employee Documents
    Route::controller(EmployeeDocumentController::class)->group(function () {
        Route::get('/api/employee/documents', 'index');
        Route::post('/api/employee/documents', 'store');
        Route::patch('/employee/documents/{id}/status', 'updateStatus');
        Route::delete('/employee/documents/{id}', 'destroy');
    });

    // My Documents
    Route::controller(DocumentController::class)->group(function () {
        Route::get('/api/my-documents', 'myDocuments');
        Route::post('/api/upload-document', 'uploadDocument');
        Route::post('/api/reupload-document/{id}', 'reupload');
        Route::get('/api/user-documents/{userId}', 'documentsByUserId');
        Route::get('/api/sync-files', 'syncFiles');
        Route::post('api/documents/{id}/request-change', 'requestChange');
    });

    // Work Units
    Route::prefix('/api/work-units')->controller(WorkUnitController::class)->group(function () {
        Route::get('{id}/employees', 'fetchEmployee');
        Route::get('/tree', 'tree');
        Route::get('/monitor', 'monitor');
        Route::get('/self-monitor', 'selfMonitor');
        Route::get('/fetch', 'fetch');
        Route::get('/', 'index');          
        Route::post('/', 'store');         
        Route::get('/{id}', 'show');       
        Route::put('/{id}', 'update');     
        Route::delete('/{id}', 'destroy'); 
    });

    // Emp Documents (Admin/Verval)
    Route::controller(EmpDocumentController::class)->group(function () {
        Route::get('/api/emp-documents', 'index');
        Route::post('api/emp-documents/claim', 'claim');
        Route::put('/api/emp-documents/{id}/verify', 'verify');
        Route::post('api/emp-documents/{empDocument}/release', 'release');
        Route::get('/api/emp-documents/remaining', 'remaining');
    });

    Route::get('/api/document-log/{id}', [DocumentLogController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Vue Router Catch-All (HARUS PALING BAWAH)
|--------------------------------------------------------------------------
*/
Route::get('{view}', [ApplicationController::class, '__invoke']) // <-- Pastikan diarahkan ke method, jangan closure
    ->where('view', '^(?!api|storage|build|assets|_debugbar|secure).*$') 
    ->middleware('auth');