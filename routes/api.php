<?php

use App\Http\Controllers\Api\__BranchController;
use App\Http\Controllers\Api\__CategoryController;
use App\Http\Controllers\Api\__EventBranchController;
use App\Http\Controllers\Api\__EventCategoryController;
use App\Http\Controllers\API\__EventCompetitionController;
use App\Http\Controllers\API\__EventCompetitionScoringController;
use App\Http\Controllers\API\__EventController;
use App\Http\Controllers\API\__EventFieldComponentController;
use App\Http\Controllers\Api\__EventGroupController;
use App\Http\Controllers\API\__EventJudgePanelController;
use App\Http\Controllers\API\__EventParticipantController;
use App\Http\Controllers\API\__EventStageController;
use App\Http\Controllers\Api\__GroupController;
use App\Http\Controllers\API\__JudgeUserController;
use App\Http\Controllers\Api\__ListFieldController;
use App\Http\Controllers\Api\__MasterBranchController;
use App\Http\Controllers\Api\__MasterCategoryController;
use App\Http\Controllers\Api\__MasterFieldComponentController;
use App\Http\Controllers\API\__MasterGroupController;
use App\Http\Controllers\API\__ParticipantController;
use App\Http\Controllers\API\__StageController;
use App\Http\Controllers\API\__UserController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\StageController;
use App\Http\Controllers\API\EventStageController;
use App\Http\Controllers\API\MasterCompetitionGroupController;
use App\Http\Controllers\API\MasterCompetitionCategoryController;
use App\Http\Controllers\API\MasterCompetitionBranchController;
use App\Http\Controllers\API\EventCompetitionBranchController;
use App\Http\Controllers\API\RoleController;
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

        /**
         * REFERENCE DATA
         * Data sebagai Referensi untuk master dan transactional data
         */
        // REFERENSI CABANG GOLONGAN
        Route::apiResource('branches', __BranchController::class)->except(['show']);
        Route::apiResource('groups', __GroupController::class)->except(['show']);
        Route::apiResource('categories', __CategoryController::class)->except(['show']);

        // REFERENSI BIDANG PENILAIAN
        Route::apiResource('list-fields', __ListFieldController::class)->except(['show']);
        
        // REFERENSI TAHAPAN
        Route::apiResource('stages', __StageController::class)->middleware('permission:master.manage.stage');

        /**
         * MASTER DATA
         * Data jadi yang bisa digunakan untuk transactional data
         */
        // MASTER CABANG GOLONGAN
        Route::apiResource('master-branches', __MasterBranchController::class)->except(['show']);
        Route::apiResource('master-groups', __MasterGroupController::class)->except(['show']);
        Route::apiResource('master-categories', __MasterCategoryController::class)->except(['show']);

        // MASTER KOMPONEN PENILAIAN
        Route::apiResource('master-field-components', __MasterFieldComponentController::class)->except(['show']);


        /**
         * MANAGED DATA
         * Data yang di manage / dikelola sebagai bagian transactional data
         */
        // EVENT - PENGATURAN DATA TRANSACTIONAL EVENT
        Route::apiResource('events', __EventController::class);

        // EVENT - PENGATURAN TAHAPAN EVENT
        Route::apiResource('event-stages', __EventStageController::class)->except(['index']);
        Route::get('events/{event}/stages', [__EventStageController::class, 'index'])->middleware('permission:event.manage.stage');
        Route::post('events/{event}/stages/generate-default', [__EventStageController::class, 'generateFromMaster'])->middleware('permission:event.manage.stage');

        // EVENT - PENGATURAN CABANG LOMBA
        Route::get('event-branches', [__EventBranchController::class, 'index']);
        Route::post('event-branches', [__EventBranchController::class, 'store']);
        Route::put('event-branches/{eventBranch}', [__EventBranchController::class, 'update']);
        Route::delete('event-branches/{eventBranch}', [__EventBranchController::class, 'destroy']);
        Route::post('event-branches/generate-from-template', [__EventBranchController::class, 'generateFromTemplate']); // tombol "Generate dari Template"

        // EVENT - PENGATURAN GOLONGAN LOMBA
        Route::get('event-groups', [__EventGroupController::class, 'index']);
        Route::post('event-groups', [__EventGroupController::class, 'store']);
        Route::put('event-groups/{eventGroup}', [__EventGroupController::class, 'update']);
        Route::delete('event-groups/{eventGroup}', [__EventGroupController::class, 'destroy']);
        Route::post('event-groups/generate-from-template', [__EventGroupController::class, 'generateFromTemplate']); // Generate dari master_groups
        
        // EVENT - PENGATURAN CABANG GOLONGAN LOMBA
        Route::get('event-categories', [__EventCategoryController::class, 'index']);
        Route::post('event-categories', [__EventCategoryController::class, 'store']);
        Route::put('event-categories/{eventCategory}', [__EventCategoryController::class, 'update']);
        Route::delete('event-categories/{eventCategory}', [__EventCategoryController::class, 'destroy']);
        Route::post('event-categories/generate-from-template', [__EventCategoryController::class, 'generateFromTemplate']);

        // EVENT - PENGATURAN KOMPONEN PENILAIAN LOMBA
        Route::get('event-field-components', [__EventFieldComponentController::class, 'index']);
        Route::post('event-field-components', [__EventFieldComponentController::class, 'store']);
        Route::put('event-field-components/{id}', [__EventFieldComponentController::class, 'update']);
        Route::delete('event-field-components/{id}', [__EventFieldComponentController::class, 'destroy']);
        Route::post('event-field-components/generate-from-template', [__EventFieldComponentController::class, 'generateFromTemplate']);

        // Participants (bank data)
        Route::get('/participants', [__ParticipantController::class, 'index']);
        Route::get('/participants/search-by-nik', [__ParticipantController::class, 'searchByNik']);
        Route::post('/participants', [__ParticipantController::class, 'store']);
        Route::get('/participants/{participant}', [__ParticipantController::class, 'show']);
        Route::put('/participants/{participant}', [__ParticipantController::class, 'update']);
        Route::delete('/participants/{participant}', [__ParticipantController::class, 'destroy']);

        // Event Participants
        Route::get('/events/{event}/participants', [__EventParticipantController::class, 'index']);
        Route::post('/events/{event}/participants', [__EventParticipantController::class, 'store']);

        Route::get('/events/{event}/simple', [__EventParticipantController::class, 'simple']);
        Route::get('/events/{event}/participants/simple', [__EventParticipantController::class, 'simpleParticipant']);

        Route::post('/save-event-participants/', [__EventParticipantController::class, 'eventParticipant']);

        Route::get('/event-participants/{eventParticipant}', [__EventParticipantController::class, 'show']);
        Route::put('/event-participants/{eventParticipant}', [__EventParticipantController::class, 'update']);
        Route::delete('/event-participants/{eventParticipant}', [__EventParticipantController::class, 'destroy']);
        Route::post('event-participants/{eventParticipant}/mutasi-wilayah', [
            __EventParticipantController::class, 'mutasiWilayah'
        ]);
        Route::post('event-participants/bulk-register', [__EventParticipantController::class, 'bulkRegister']);
        Route::get('get/event-participants/status-counts', [__EventParticipantController::class, 'statusCounts']);
        Route::get('get/event-participants/{eventParticipant}/biodata-pdf', [__EventParticipantController::class, 'biodataPdf'])
            ->name('participants.biodata-pdf');

        Route::get('participants/{participant}/verifications', [ParticipantVerificationController::class, 'index']);
        Route::post('participants/{participant}/verifications', [ParticipantVerificationController::class, 'store']);
        Route::get('participants/{participant}/verifications/{verification}', [ParticipantVerificationController::class, 'show']);
    
        Route::get('events/{event}/kafilah-pdf', [__EventParticipantController::class, 'kafilahPdf']);

        //  Judges
        Route::get('/events/{event}/judge-panels', [__EventJudgePanelController::class, 'index']);

        // Default cabang (event_branch_judges)
        Route::get('/event-branches/{eventBranch}/judges', [__EventJudgePanelController::class, 'getBranchJudges']);
        Route::put('/event-branches/{eventBranch}/judges', [__EventJudgePanelController::class, 'syncBranchJudges']);

        // Override golongan (event_group_judges + toggle use_custom_judges)
        Route::get('/event-groups/{eventGroup}/judges', [__EventJudgePanelController::class, 'getGroupJudges']);
        Route::put('/event-groups/{eventGroup}/judges', [__EventJudgePanelController::class, 'syncGroupJudges']);
        Route::patch('/event-groups/{eventGroup}/use-custom-judges', [__EventJudgePanelController::class, 'toggleUseCustom']);


         // list & create (scoped by event)
        Route::get('/events/{event}/judges',  [__JudgeUserController::class, 'index']);
        Route::post('/events/{event}/judges', [__JudgeUserController::class, 'store']);

        // update / delete / toggle active
        Route::put('/judges/{user}',                [__JudgeUserController::class, 'update']);
        Route::delete('/judges/{user}',             [__JudgeUserController::class, 'destroy']);
        Route::patch('/judges/{user}/toggle-active',[__JudgeUserController::class, 'toggleActive']);

        // CRUD permission_role
        Route::apiResource('permission-roles', PermissionRoleController::class);
        Route::post('/roles/{role}/sync-permissions', [PermissionRoleController::class, 'sync']);

        Route::get('/events/{event}/competitions/meta', [__EventCompetitionController::class, 'meta']);
        Route::get('/events/{event}/competitions/tree', [__EventCompetitionController::class, 'tree']);
        Route::post('/events/{event}/competitions', [__EventCompetitionController::class, 'store']);

        Route::get('/event-competitions/{eventCompetition}', [__EventCompetitionController::class, 'show']);
        Route::put('/event-competitions/{eventCompetition}', [__EventCompetitionController::class, 'update']);
        Route::delete('/event-competitions/{eventCompetition}', [__EventCompetitionController::class, 'destroy']);


        Route::get('/event-competitions/{competition}/scoring/form', [__EventCompetitionScoringController::class, 'form']);
        Route::post('/event-competitions/{competition}/scoring/draft', [__EventCompetitionScoringController::class, 'saveDraft']);
        Route::post('/event-competitions/{competition}/scoring/submit', [__EventCompetitionScoringController::class, 'submit']);
        Route::post('/event-competitions/{competition}/scoring/lock', [__EventCompetitionScoringController::class, 'lock']);











        

        // CRUD Users (per event)
        Route::apiResource('users', __UserController::class);
        Route::post('events/{event}/generate-users', [__UserController::class, 'generateUsersByEvent']);


        // Optional: list roles untuk dropdown
        Route::get('roles', [RoleController::class, 'index']);

        

        // Dropdown helper
        Route::get('roles-simple', [SimpleRoleController::class, 'index']);
        Route::get('permissions-simple', [SimplePermissionController::class, 'index']);


        // // LIST / CREATE / UPDATE / DELETE EVENT_PARTICIPANTS
        // Route::get('participants', [ParticipantController::class, 'index']);
        // Route::post('participants', [ParticipantController::class, 'store']);
        // Route::get('participants/{eventParticipant}', [ParticipantController::class, 'show']);
        // Route::put('participants/{eventParticipant}', [ParticipantController::class, 'update']);
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


        
        Route::post(
            'event-participants/{eventParticipant}/re-registration',
            [EventParticipantReRegistrationController::class, 'store']
        );
    });