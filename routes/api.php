<?php

use App\Http\Controllers\Api\__BranchController;
use App\Http\Controllers\Api\__CategoryController;
use App\Http\Controllers\Api\__EventBranchController;
use App\Http\Controllers\Api\__EventCategoryController;
use App\Http\Controllers\API\__EventFieldComponentController;
use App\Http\Controllers\Api\__EventGroupController;
use App\Http\Controllers\Api\__GroupController;
use App\Http\Controllers\Api\__ListFieldController;
use App\Http\Controllers\Api\__MasterBranchController;
use App\Http\Controllers\Api\__MasterCategoryController;
use App\Http\Controllers\Api\__MasterFieldComponentController;
use App\Http\Controllers\API\__MasterGroupController;
use App\Http\Controllers\API\__StageController;
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
use App\Http\Controllers\API\EventParticipantReRegistrationController;
use App\Http\Controllers\API\ParticipantController;
use App\Http\Controllers\API\LocationController;
use App\Http\Controllers\API\ParticipantVerificationController;
use App\Http\Controllers\API\PublicEventController;
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

Route::prefix('v1')->group(function () {
    Route::get('/public-events', [PublicEventController::class, 'index']);
});

Route::middleware(['auth:sanctum']) // kalau belum pakai sanctum, boleh dihapus dulu
    ->prefix('v1')
    ->group(function () {


        
        // MASTER CABANG GOLONGAN
        Route::apiResource('branches', __BranchController::class)->except(['show']);
        Route::apiResource('groups', __GroupController::class)->except(['show']);
        Route::apiResource('categories', __CategoryController::class)->except(['show']);

        // MASTER BIDANG PENILAIAN
        Route::apiResource('list-fields', __ListFieldController::class)->except(['show']);
        
        // MASTER STAGES
        Route::apiResource('stages', __StageController::class)->middleware('permission:master.manage.stage');

        Route::apiResource('master-branches', __MasterBranchController::class)->except(['show']);

        Route::get('/master-groups', [__MasterGroupController::class, 'index']);
        Route::post('/master-groups', [__MasterGroupController::class, 'store']);
        Route::put('/master-groups/{id}', [__MasterGroupController::class, 'update']);
        Route::delete('/master-groups/{id}', [__MasterGroupController::class, 'destroy']);

        Route::apiResource('master-categories', __MasterCategoryController::class)->except(['show']);

        Route::apiResource('master-field-components', __MasterFieldComponentController::class)
        ->except(['show']);


        Route::get('event-branches', [__EventBranchController::class, 'index']);
        Route::post('event-branches', [__EventBranchController::class, 'store']);
        Route::put('event-branches/{eventBranch}', [__EventBranchController::class, 'update']);
        Route::delete('event-branches/{eventBranch}', [__EventBranchController::class, 'destroy']);

        // tombol "Generate dari Template"
        Route::post('event-branches/generate-from-template', [__EventBranchController::class, 'generateFromTemplate']);

        Route::get('event-groups', [__EventGroupController::class, 'index']);
        Route::post('event-groups', [__EventGroupController::class, 'store']);
        Route::put('event-groups/{eventGroup}', [__EventGroupController::class, 'update']);
        Route::delete('event-groups/{eventGroup}', [__EventGroupController::class, 'destroy']);

        // Generate dari master_groups
        Route::post('event-groups/generate-from-template', [__EventGroupController::class, 'generateFromTemplate']);
        
        Route::get('event-categories', [__EventCategoryController::class, 'index']);
        Route::post('event-categories', [__EventCategoryController::class, 'store']);
        Route::put('event-categories/{eventCategory}', [__EventCategoryController::class, 'update']);
        Route::delete('event-categories/{eventCategory}', [__EventCategoryController::class, 'destroy']);

        Route::post('event-categories/generate-from-template', [__EventCategoryController::class, 'generateFromTemplate']);

        Route::get('event-field-components', [__EventFieldComponentController::class, 'index']);
        Route::post('event-field-components', [__EventFieldComponentController::class, 'store']);
        Route::put('event-field-components/{id}', [__EventFieldComponentController::class, 'update']);
        Route::delete('event-field-components/{id}', [__EventFieldComponentController::class, 'destroy']);

        Route::post(
            'event-field-components/generate-from-template',
            [__EventFieldComponentController::class, 'generateFromTemplate']
        );


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

        // LIST / CREATE / UPDATE / DELETE EVENT_PARTICIPANTS
        Route::get('participants', [ParticipantController::class, 'index']);
        Route::post('participants', [ParticipantController::class, 'store']);
        Route::get('participants/{eventParticipant}', [ParticipantController::class, 'show']);
        Route::put('participants/{eventParticipant}', [ParticipantController::class, 'update']);
        Route::get('check-nik', [ParticipantController::class, 'checkNik']);


        // Wilayah
        Route::get('get/provinces', [LocationController::class, 'provinces']);
        Route::get('get/regencies', [LocationController::class, 'regencies']);
        Route::get('get/districts', [LocationController::class, 'districts']);
        Route::get('get/villages', [LocationController::class, 'villages']);

         // Cabang / Golongan per Event (untuk dropdown peserta)
        Route::get('get/event-competition-branches', [LocationController::class, 'eventBranches']);

        Route::post('participants/{eventParticipant}/mutasi-wilayah', [
            ParticipantController::class, 'mutasiWilayah'
        ]);

        Route::get('get/participants/search-by-nik', [ParticipantController::class, 'searchByNik']);
        Route::post('participants/bulk-register', [ParticipantController::class, 'bulkRegister']);
    
        Route::get('get/participants/status-counts', [ParticipantController::class, 'statusCounts']);

        Route::get('get/participants/{eventParticipant}/biodata-pdf', [ParticipantController::class, 'biodataPdf'])
            ->name('participants.biodata-pdf');


        Route::get('participants/{participant}/verifications', [ParticipantVerificationController::class, 'index']);
        Route::post('participants/{participant}/verifications', [ParticipantVerificationController::class, 'store']);
        Route::get('participants/{participant}/verifications/{verification}', [ParticipantVerificationController::class, 'show']);
    
        Route::post(
            'event-participants/{eventParticipant}/re-registration',
            [EventParticipantReRegistrationController::class, 'store']
        );
    });