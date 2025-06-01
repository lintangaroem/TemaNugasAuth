<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TodoController;
use App\Http\Controllers\Api\NoteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working!',
        'timestamp' => now()->toDateTimeString()
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    // Route untuk mendapatkan detail user yang sedang login
    Route::get('/user', function (Request $request) {
        // Muat grup yang disetujui dan permintaan grup yang pending untuk user saat ini
        return $request->user()->load('approvedGroups:id,name', 'pendingGroupRequests:id,name', 'createdGroups:id,name');
    });

    // Groups
    Route::apiResource('groups', GroupController::class);
    // Ganti nama route dari 'join' menjadi 'request-join'
    Route::post('groups/{group}/request-join', [GroupController::class, 'requestToJoinGroup'])->name('groups.request-join');
    Route::post('groups/{group}/leave', [GroupController::class, 'leaveGroup'])->name('groups.leave');
    Route::get('groups/{group}/members', [GroupController::class, 'getMembers'])->name('groups.members'); // Ini akan get approved members

    // Routes untuk manajemen approval (hanya bisa diakses oleh group creator)
    Route::get('groups/{group}/join-requests', [GroupController::class, 'listJoinRequests'])->name('groups.join-requests.list');
    // {userToManage} akan di-resolve ke model User karena type-hint di controller
    Route::post('groups/{group}/join-requests/{userToManage}/approve', [GroupController::class, 'approveJoinRequest'])->name('groups.join-requests.approve');
    Route::post('groups/{group}/join-requests/{userToManage}/reject', [GroupController::class, 'rejectJoinRequest'])->name('groups.join-requests.reject');


    // Projects (Nested under groups)
    Route::get('groups/{group}/projects', [ProjectController::class, 'index'])->name('groups.projects.index');
    Route::post('groups/{group}/projects', [ProjectController::class, 'store'])->name('groups.projects.store');
    Route::scopeBindings()->group(function () {
        Route::apiResource('groups.projects', ProjectController::class)->except(['index', 'store']);
    });

    // Todos (Nested under projects)
    Route::get('projects/{project}/todos', [TodoController::class, 'index'])->name('projects.todos.index');
    Route::post('projects/{project}/todos', [TodoController::class, 'store'])->name('projects.todos.store');
    Route::scopeBindings()->group(function () {
        Route::apiResource('projects.todos', TodoController::class)->except(['index', 'store']);
    });

    // Notes (Nested under projects)
    Route::get('projects/{project}/notes', [NoteController::class, 'index'])->name('projects.notes.index');
    Route::post('projects/{project}/notes', [NoteController::class, 'store'])->name('projects.notes.store');
    Route::scopeBindings()->group(function () {
        Route::apiResource('projects.notes', NoteController::class)->except(['index', 'store']);
    });
});
