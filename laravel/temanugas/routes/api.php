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
        return $request->user()->load('groups', 'createdGroups'); // Contoh memuat relasi
    });
    // Groups
    Route::apiResource('groups', GroupController::class);
    Route::post('groups/{group}/join', [GroupController::class, 'joinGroup'])->name('groups.join');
    Route::post('groups/{group}/leave', [GroupController::class, 'leaveGroup'])->name('groups.leave');
    Route::get('groups/{group}/members', [GroupController::class, 'getMembers'])->name('groups.members');

    Route::get('groups/{group}/projects', [ProjectController::class, 'index'])->name('groups.projects.index');
    Route::post('groups/{group}/projects', [ProjectController::class, 'store'])->name('groups.projects.store');

    // Menggunakan scopeBindings() agar {project} otomatis di-scope ke {group}
    Route::scopeBindings()->group(function () {
        Route::apiResource('groups.projects', ProjectController::class)->except(['index', 'store']);
    });

    Route::get('projects/{project}/todos', [TodoController::class, 'index'])->name('projects.todos.index');
    Route::post('projects/{project}/todos', [TodoController::class, 'store'])->name('projects.todos.store');

    Route::scopeBindings()->group(function () {
        Route::apiResource('projects.todos', TodoController::class)->except(['index', 'store']);
    });

    Route::get('projects/{project}/notes', [NoteController::class, 'index'])->name('projects.notes.index');
    Route::post('projects/{project}/notes', [NoteController::class, 'store'])->name('projects.notes.store');

    Route::scopeBindings()->group(function () {
        Route::apiResource('projects.notes', NoteController::class)->except(['index', 'store']);
    });
});
