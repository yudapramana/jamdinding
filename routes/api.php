<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\StageController;
use App\Http\Controllers\API\EventStageController;
use App\Http\Controllers\API\MasterCompetitionGroupController;
use App\Http\Controllers\API\MasterCompetitionCategoryController;
use App\Http\Controllers\API\MasterCompetitionBranchController;
use App\Http\Controllers\API\EventCompetitionBranchController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\PermissionRoleController;
use App\Http\Controllers\API\SimpleRoleController;
use App\Http\Controllers\API\SimplePermissionController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\ParticipantController;
use App\Http\Controllers\API\LocationController;

use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\Auth\PasswordResetWhatsappController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    // return $request->user();

    $user = $request->user()->load('role.permissions');

    return response()->json([
        'user'        => $user,
        'role'        => $user->role?->slug,
        'permissions' => $user->permissions, // dari accessor
    ]);
});



Route::get('/wa/send', [WhatsAppController::class, 'sendFastGet']);

Route::post('/auth/wa/request-reset', [PasswordResetWhatsappController::class, 'requestReset'])
    ->middleware('throttle:5,1')
    ->name('api.password.wa.request');


Route::middleware(['auth:sanctum']) // kalau belum pakai sanctum, boleh dihapus dulu
    ->prefix('v1')
    ->group(function () {

        // MASTER STAGES
        Route::apiResource('stages', StageController::class)->middleware('permission:master.manage.stage');

        // EVENT STAGES
        Route::get('events/{event}/stages', [EventStageController::class, 'index'])->middleware('permission:event.manage.stage');
        Route::post('events/{event}/stages/generate-default', [EventStageController::class, 'generateFromMaster'])->middleware('permission:event.manage.stage');

        // Kalau mau akses langsung by ID
        Route::apiResource('event-stages', EventStageController::class)->except(['index']);

        Route::apiResource(
            'master-competition-groups',
            MasterCompetitionGroupController::class
        );

        Route::apiResource(
            'master-competition-categories',
            MasterCompetitionCategoryController::class
        );

        Route::apiResource(
            'master-competition-branches',
            MasterCompetitionBranchController::class
        );

        Route::apiResource(
            'event-competition-branches',
            EventCompetitionBranchController::class
        );

        // 🔥 Generate event competition branches dari master untuk event tertentu
        Route::post(
            'events/{event}/competition-branches/generate-from-master',
            [EventCompetitionBranchController::class, 'generateFromMaster']
        );

        // CRUD Users (per event)
        Route::apiResource('users', UserController::class);
        Route::post('events/{event}/generate-users', [UserController::class, 'generateUsersByEvent']);


        // Optional: list roles untuk dropdown
        Route::get('roles', [RoleController::class, 'index']);

        // CRUD permission_role
        Route::apiResource('permission-roles', PermissionRoleController::class);

        // Dropdown helper
        Route::get('roles-simple', [SimpleRoleController::class, 'index']);
        Route::get('permissions-simple', [SimplePermissionController::class, 'index']);

        Route::apiResource('events', EventController::class);

        Route::apiResource('participants', ParticipantController::class);
        Route::get('check-nik', [ParticipantController::class, 'checkNik']);


        // Wilayah
        Route::get('get/provinces', [LocationController::class, 'provinces']);
        Route::get('get/regencies', [LocationController::class, 'regencies']);
        Route::get('get/districts', [LocationController::class, 'districts']);
        Route::get('get/villages', [LocationController::class, 'villages']);

         // Cabang / Golongan per Event (untuk dropdown peserta)
        Route::get('get/event-competition-branches', [LocationController::class, 'eventBranches']);

        Route::post('participants/{participant}/mutasi-wilayah', [
            ParticipantController::class, 'mutasiWilayah'
        ]);
    });