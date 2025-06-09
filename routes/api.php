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
        return $request->user()->load('approvedProjects', 'pendingProjectRequests', 'createdProjects');
        //return $request->user();
    });

    Route::apiResource('projects', ProjectController::class);
    Route::post('projects/{project}/request-join', [ProjectController::class, 'requestToJoinProject'])->name('projects.request-join');
    Route::post('projects/{project}/leave', [ProjectController::class, 'leaveProject'])->name('projects.leave');
    Route::get('projects/{project}/members', [ProjectController::class, 'getProjectMembers'])->name('projects.members');
    Route::get('projects/{project}/join-requests', [ProjectController::class, 'listJoinRequests'])->name('projects.join-requests.list');
    Route::post('projects/{project}/join-requests/{userToManage}/approve', [ProjectController::class, 'approveJoinRequest'])->name('projects.join-requests.approve');
    Route::post('projects/{project}/join-requests/{userToManage}/reject', [ProjectController::class, 'rejectJoinRequest'])->name('projects.join-requests.reject');


    // Todos (Nested under projects)
    Route::scopeBindings()->group(function () {
        Route::apiResource('projects.todos', TodoController::class);
        // Ini akan membuat:
        // GET    projects/{project}/todos -> TodoController@index (projects.todos.index)
        // POST   projects/{project}/todos -> TodoController@store (projects.todos.store)
        // GET    projects/{project}/todos/{todo} -> TodoController@show (projects.todos.show)
        // PUT    projects/{project}/todos/{todo} -> TodoController@update (projects.todos.update)
        // DELETE projects/{project}/todos/{todo} -> TodoController@destroy (projects.todos.destroy)
    });


    // Notes (Nested under projects - Definisi manual untuk index & store, sisanya oleh apiResource)
    Route::get('projects/{project}/notes', [NoteController::class, 'index'])->name('projects.notes.index');
    Route::post('projects/{project}/notes', [NoteController::class, 'store'])->name('projects.notes.store');
    Route::scopeBindings()->group(function () {
        Route::apiResource('projects.notes', NoteController::class)->except(['index', 'store']);
        // Ini akan membuat:
        // GET    projects/{project}/notes/{note} -> NoteController@show (projects.notes.show)
        // PUT    projects/{project}/notes/{note} -> NoteController@update (projects.notes.update)
        // DELETE projects/{project}/notes/{note} -> NoteController@destroy (projects.notes.destroy)
    });
});
