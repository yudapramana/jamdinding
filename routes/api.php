<?php

use App\Http\Controllers\Admin\MasterController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\PermissionRoleController;
use App\Http\Controllers\API\SimpleRoleController;
use App\Http\Controllers\API\SimplePermissionController;
// use App\Http\Controllers\API\EventParticipantReRegistrationController;
use App\Http\Controllers\API\LocationController;
use App\Http\Controllers\API\PublicEventController;
use App\Http\Controllers\API\V1\BranchController;
use App\Http\Controllers\API\V1\CategoryController;
use App\Http\Controllers\API\V1\EventBranchController;
use App\Http\Controllers\API\V1\EventCategoryController;
use App\Http\Controllers\API\V1\EventCompetitionController;
use App\Http\Controllers\API\V1\EventCompetitionRankingController;
use App\Http\Controllers\API\V1\EventCompetitionScoresController;
use App\Http\Controllers\API\V1\EventCompetitionScoringController;
use App\Http\Controllers\API\V1\EventContingentStandingsController;
use App\Http\Controllers\API\V1\EventController;
use App\Http\Controllers\API\V1\EventFieldComponentController;
use App\Http\Controllers\API\V1\EventGroupController;
use App\Http\Controllers\API\V1\EventGroupLocationController;
use App\Http\Controllers\API\V1\EventJudgePanelController;
use App\Http\Controllers\API\V1\EventKokardeController;
use App\Http\Controllers\API\V1\EventLocationController;
use App\Http\Controllers\API\V1\EventMedalRuleController;
use App\Http\Controllers\API\V1\EventParticipantController;
use App\Http\Controllers\API\V1\EventParticipantReRegistrationController;
use App\Http\Controllers\API\V1\EventStageController;
use App\Http\Controllers\API\V1\GroupController;
use App\Http\Controllers\API\V1\JudgeUserController;
use App\Http\Controllers\API\V1\ListFieldController;
use App\Http\Controllers\API\V1\MasterBranchController;
use App\Http\Controllers\API\V1\MasterCategoryController;
use App\Http\Controllers\API\V1\MasterFieldComponentController;
use App\Http\Controllers\API\V1\MasterGroupController;
use App\Http\Controllers\API\V1\MedalRuleController;
use App\Http\Controllers\API\V1\ParticipantController;
use App\Http\Controllers\API\V1\ParticipantVerificationController;
use App\Http\Controllers\API\V1\StageController;
use App\Http\Controllers\API\V1\UserController;
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
    Route::middleware(['throttle:60,1'])->get('/public-events', [PublicEventController::class, 'index']);
});

Route::middleware(['auth:sanctum']) // kalau belum pakai sanctum, boleh dihapus dulu
    ->prefix('v1')
    ->group(function () {

        Route::get('master', [MasterController::class, 'index']);

        /**
         * CORE DATA
         * Data sebagai Referensi untuk master dan transactional data
         */
        // REFERENSI CABANG GOLONGAN
        Route::apiResource('branches', BranchController::class)->except(['show']);
        Route::apiResource('groups', GroupController::class)->except(['show']);
        Route::apiResource('categories', CategoryController::class)->except(['show']);

        // REFERENSI BIDANG PENILAIAN
        Route::apiResource('list-fields', ListFieldController::class)->except(['show']);
        
        // REFERENSI TAHAPAN
        Route::apiResource('stages', StageController::class);

        // REFERENSI PERATURAN MEDALI
        Route::apiResource('medal-rules', MedalRuleController::class);

        // REFERENSI PERMISSION ROLES
        Route::apiResource('permission-roles', PermissionRoleController::class);
        Route::post('/roles/{role}/sync-permissions', [PermissionRoleController::class, 'sync']);
        Route::get('roles-simple', [SimpleRoleController::class, 'index']); // HELPER ROLES
        Route::get('permissions-simple', [SimplePermissionController::class, 'index']); // HELPER PERMISSIONS


        /**
         * MASTER DATA
         * Data jadi yang bisa digunakan untuk transactional data
         */
        // MASTER CABANG GOLONGAN
        Route::apiResource('master-branches', MasterBranchController::class)->except(['show']);
        Route::apiResource('master-groups', MasterGroupController::class)->except(['show']);
        Route::apiResource('master-categories', MasterCategoryController::class)->except(['show']);

        // MASTER KOMPONEN PENILAIAN
        Route::apiResource('master-field-components', MasterFieldComponentController::class)->except(['show']);

        // MASTER PARTICIPANTS
        Route::get('/participants', [ParticipantController::class, 'index']);
        Route::get('check-nik', [ParticipantController::class, 'checkNik']);
        Route::get('/participants/search-by-nik', [ParticipantController::class, 'searchByNik']);
        Route::post('/participants', [ParticipantController::class, 'store']);
        Route::get('/participants/{participant}', [ParticipantController::class, 'show']);
        Route::put('/participants/{participant}', [ParticipantController::class, 'update']);
        Route::delete('/participants/{participant}', [ParticipantController::class, 'destroy']);

        /**
         * MANAGED DATA
         * Data yang di manage / dikelola sebagai bagian transactional data
         */
        // EVENT - PENGATURAN DATA TRANSACTIONAL EVENT
        Route::apiResource('events', EventController::class);

        // EVENT - PENGATURAN TAHAPAN EVENT
        Route::apiResource('event-stages', EventStageController::class)->except(['index']);
        Route::get('events/{event}/stages', [EventStageController::class, 'index']);
        Route::post('events/{event}/stages/generate-default', [EventStageController::class, 'generateFromMaster']);

        // EVENT - LOCATIONS
        Route::get('/events/{eventId}/locations', [EventLocationController::class, 'index']);
        Route::get('/events/{eventId}/locations/simple', [EventLocationController::class, 'simple']);
        Route::post('/event-locations', [EventLocationController::class, 'store']);
        Route::put('/event-locations/{id}', [EventLocationController::class, 'update']);
        Route::delete('/event-locations/{id}', [EventLocationController::class, 'destroy']);
        Route::get('/map/search', [EventLocationController::class, 'search']);

        // EVENT - PENGATURAN CABANG LOMBA
        Route::get('event-branches', [EventBranchController::class, 'index']);
        Route::post('event-branches', [EventBranchController::class, 'store']);
        Route::put('event-branches/{eventBranch}', [EventBranchController::class, 'update']);
        Route::delete('event-branches/{eventBranch}', [EventBranchController::class, 'destroy']);
        Route::post('event-branches/generate-from-template', [EventBranchController::class, 'generateFromTemplate']); // tombol "Generate dari Template"

        // EVENT - PENGATURAN GOLONGAN LOMBA
        Route::get('event-groups', [EventGroupController::class, 'index']);
        Route::post('event-groups', [EventGroupController::class, 'store']);
        Route::put('event-groups/{eventGroup}', [EventGroupController::class, 'update']);
        Route::delete('event-groups/{eventGroup}', [EventGroupController::class, 'destroy']);
        Route::post('event-groups/generate-from-template', [EventGroupController::class, 'generateFromTemplate']); // Generate dari master_groups
        Route::put('/event-groups/{id}/assign-location', [EventGroupLocationController::class, 'assign']);
        
        // EVENT - PENGATURAN KATEGORI LOMBA (PUTRA | PUTRI)
        Route::get('event-categories', [EventCategoryController::class, 'index']);
        Route::post('event-categories', [EventCategoryController::class, 'store']);
        Route::put('event-categories/{eventCategory}', [EventCategoryController::class, 'update']);
        Route::delete('event-categories/{eventCategory}', [EventCategoryController::class, 'destroy']);
        Route::post('event-categories/generate-from-template', [EventCategoryController::class, 'generateFromTemplate']);

        // EVENT - PENGATURAN KOMPONEN PENILAIAN LOMBA
        Route::get('event-field-components', [EventFieldComponentController::class, 'index']);
        Route::post('event-field-components', [EventFieldComponentController::class, 'store']);
        Route::put('event-field-components/{id}', [EventFieldComponentController::class, 'update']);
        Route::delete('event-field-components/{id}', [EventFieldComponentController::class, 'destroy']);
        Route::post('event-field-components/generate-from-template', [EventFieldComponentController::class, 'generateFromTemplate']);

        // EVENT USERS
        Route::apiResource('users', UserController::class);
        Route::post('events/{event}/generate-users', [UserController::class, 'generateUsersByEvent']);
        Route::get('roles', [RoleController::class, 'index']);

        // EVENT MEDAL RULES
        Route::get('event-medal-rules', [EventMedalRuleController::class, 'index']);
        Route::post('event-medal-rules', [EventMedalRuleController::class, 'store']);
        Route::put('event-medal-rules/{eventMedalRule}', [EventMedalRuleController::class, 'update']);
        Route::delete('event-medal-rules/{eventMedalRule}', [EventMedalRuleController::class, 'destroy']);
        Route::post('event-medal-rules/generate-from-template', [EventMedalRuleController::class, 'generateFromTemplate']);

        /**
         * PARTICIPANT DATA
         * Data Peserta Event dengan Siklus
         */
        // EVENT PARTICIPANT - BANK DATA TO REGISTRATIONS
        Route::get('/events/{event}/participants', [EventParticipantController::class, 'index']);
        Route::post('/save-event-participants/', [EventParticipantController::class, 'eventParticipant']);
        Route::get('/events/{event}/simple', [EventParticipantController::class, 'simple']);
        Route::get('/events/{event}/participants/simple', [EventParticipantController::class, 'simpleParticipant']);
        Route::get('/event-participants/{eventParticipant}', [EventParticipantController::class, 'show']);
        Route::put('/event-participants/{eventParticipant}', [EventParticipantController::class, 'update']);
        Route::delete('/event-participants/{eventParticipant}', [EventParticipantController::class, 'destroy']);
        Route::post('event-participants/{eventParticipant}/mutasi-wilayah', [EventParticipantController::class, 'mutasiWilayah']);
        Route::post('event-participants/bulk-register', [EventParticipantController::class, 'bulkRegister']);

        // EVENT PARTICIPANT - VERIFICATIONS
        Route::get('get/event-participants/status-counts', [EventParticipantController::class, 'statusCounts']);
        Route::get('get/event-participants/{eventParticipant}/biodata-pdf', [EventParticipantController::class, 'biodataPdf'])->name('participants.biodata-pdf');
        Route::get('participants/{participant}/verifications', [ParticipantVerificationController::class, 'index']);
        Route::post('participants/{participant}/verifications', [ParticipantVerificationController::class, 'store']);
        Route::get('participants/{participant}/verifications/{verification}', [ParticipantVerificationController::class, 'show']);
        
        // EVENT PARTICIPANT - RE-REGISTRATIONS
        Route::get('event-participants/re-registration/index', [EventParticipantReRegistrationController::class, 'index']);
        Route::post('event-participants/{eventParticipant}/re-registration', [EventParticipantReRegistrationController::class, 'store']);
        Route::post('event-participants/{eventParticipant}/draw-number', [EventParticipantReRegistrationController::class, 'drawNumber']);
        Route::post('event-participants/{eventParticipant}/assign-number', [EventParticipantReRegistrationController::class, 'assignNumber']);
        Route::post('event-teams/{eventTeam}/draw-number', [__EventTeamNumberController::class, 'drawNumber']); // EVENT TEAM REREGISTRATION AND DRAW NUMBER
        Route::post('event-teams/{eventTeam}/assign-number', [__EventTeamNumberController::class, 'assignNumber']);
        Route::post('event-teams/{eventTeam}/re-registration', [__EventTeamReRegistrationController::class, 'store']);
        
        // EVENT PARTICIPANT - FINAL KAFILAH
        Route::get('events/{event}/kafilah-pdf', [EventParticipantController::class, 'kafilahPdf']);


        // EVENT JUDGES
        Route::get('/events/{event}/judges',  [JudgeUserController::class, 'index']);
        Route::post('/events/{event}/judges', [JudgeUserController::class, 'store']);
        Route::put('/judges/{user}',                [JudgeUserController::class, 'update']); // UPDATE
        Route::delete('/judges/{user}',             [JudgeUserController::class, 'destroy']); // DELETE
        Route::patch('/judges/{user}/toggle-active',[JudgeUserController::class, 'toggleActive']); // TOGGLE ACTIVE


        // EVENT JUDGE PANEL
        Route::get('/events/{event}/judge-panels', [EventJudgePanelController::class, 'index']);
        // Default cabang (event_branch_judges)
        Route::get('/event-branches/{eventBranch}/judges', [EventJudgePanelController::class, 'getBranchJudges']);
        Route::put('/event-branches/{eventBranch}/judges', [EventJudgePanelController::class, 'syncBranchJudges']);
        // Override golongan (event_group_judges + toggle use_custom_judges)
        Route::get('/event-groups/{eventGroup}/judges', [EventJudgePanelController::class, 'getGroupJudges']);
        Route::put('/event-groups/{eventGroup}/judges', [EventJudgePanelController::class, 'syncGroupJudges']);
        Route::patch('/event-groups/{eventGroup}/use-custom-judges', [EventJudgePanelController::class, 'toggleUseCustom']);


        // EVENT COMPETITIONS - TREE 
        Route::get('/events/{event}/competitions/meta', [EventCompetitionController::class, 'meta']);
        Route::get('/events/{event}/competitions/tree', [EventCompetitionController::class, 'tree']);
        Route::post('/events/{event}/competitions', [EventCompetitionController::class, 'store']);
        Route::get('/event-competitions/{eventCompetition}', [EventCompetitionController::class, 'show']);
        Route::put('/event-competitions/{eventCompetition}', [EventCompetitionController::class, 'update']);
        Route::delete('/event-competitions/{eventCompetition}', [EventCompetitionController::class, 'destroy']);

        // EVENT COMPETITIONS - SCORING
        Route::get('/event-competitions/{competition}/scoring/form', [EventCompetitionScoringController::class, 'form']);
        Route::post('/event-competitions/{competition}/scoring/draft', [EventCompetitionScoringController::class, 'saveDraft']);
        Route::post('/event-competitions/{competition}/scoring/submit', [EventCompetitionScoringController::class, 'submit']);
        Route::post('/event-competitions/{competition}/scoring/lock', [EventCompetitionScoringController::class, 'lock']);

        // EVENT COMPETITIONS - SCORES
        Route::get('/event-competitions/{competition}/scores', [EventCompetitionScoresController::class, 'index']);
        Route::get('/event-competitions/{competition}/scores-detail', [EventCompetitionScoresController::class, 'indexDetail']);
        Route::patch('/event-competitions/{competition}/scoresheets/{scoresheet}', [EventCompetitionScoresController::class, 'update']);

        // EVENT COMPETITIONS - RANKINGS
        Route::get('/event-competitions/{competition}/ranking', [EventCompetitionRankingController::class, 'index']);
        Route::post('/event-competitions/{competition}/ranking/recalculate', [EventCompetitionRankingController::class, 'recalculate']);
        Route::post('/event-competitions/{competition}/ranking/publish', [EventCompetitionRankingController::class, 'publish']);
        Route::get('/event-competitions/{competition}/ranking/export', [EventCompetitionRankingController::class, 'export']);
        Route::get('/event-competitions/{competition}/ranking/details', [EventCompetitionRankingController::class, 'details']);
        
        // EVENT COMPETITIONS - STANDINGS
        Route::get('event-contingent-standings',[EventContingentStandingsController::class, 'index']);
        Route::get('event-contingent-standings/export/excel',[EventContingentStandingsController::class, 'exportExcel']);
        Route::get('event-contingent-standings/export/pdf',[EventContingentStandingsController::class, 'exportPdf']);
        
        // EVENT PARTICIPANTS - KOKARDE
        Route::get('/event-kokarde/export/pdf', [EventKokardeController::class, 'exportPdf']);

        // HELPER WILAYAH
        Route::get('get/provinces', [LocationController::class, 'provinces']);
        Route::get('get/regencies', [LocationController::class, 'regencies']);
        Route::get('get/districts', [LocationController::class, 'districts']);
        Route::get('get/villages', [LocationController::class, 'villages']);
    });