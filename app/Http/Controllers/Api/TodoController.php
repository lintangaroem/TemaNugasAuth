<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Pastikan Auth facade di-import jika belum
use Illuminate\Support\Facades\Validator;

class TodoController extends Controller
{
    /**
     * Display a listing of todos for a specific project.
     * Pengguna harus menjadi pembuat proyek atau anggota yang disetujui dari proyek tersebut.
     */
    public function index(Request $request, Project $project)
    {
        $user = $request->user();
        // PERBAIKAN OTORISASI: Cek apakah user adalah pembuat proyek atau anggota yang disetujui
        if ($project->created_by !== $user->id && !$project->approvedMembers()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Unauthorized to view todos for this project.'], 403);
        }

        $todos = $project->todos()->with('assignee:id,name')->latest()->paginate(10);
        return response()->json($todos);
    }

    /**
     * Store a newly created todo in storage for a specific project.
     * Pengguna harus menjadi pembuat proyek atau anggota yang disetujui.
     */
    public function store(Request $request, Project $project)
    {
        $user = $request->user();
        // PERBAIKAN OTORISASI
        // if ($project->created_by !== $user->id && !$project->approvedMembers()->where('users.id', $user->id)->exists()) {
        //     return response()->json(['message' => 'Unauthorized to create todos for this project.'], 403);
        // }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id', // Pastikan user_id (assignee) adalah anggota proyek jika diisi
            'due_date' => 'nullable|date|after_or_equal:today',
            'is_completed' => 'sometimes|boolean',
        ]);

        // Validasi tambahan: jika user_id (assignee) diisi, pastikan dia adalah anggota proyek
        if ($request->filled('user_id')) {
            $assigneeId = $request->input('user_id');
            if (!$project->approvedMembers()->where('users.id', $assigneeId)->exists() && $project->created_by != $assigneeId) {
                 return response()->json(['errors' => ['user_id' => ['Assignee must be an approved member or the creator of the project.']]], 422);
            }
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $todo = $project->todos()->create([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => $request->user_id, // User yang ditugaskan
            'due_date' => $request->due_date,
            'is_completed' => $request->is_completed ?? false,
            // Anda mungkin ingin menambahkan 'created_by_user_id' => Auth::id() untuk melacak siapa yang membuat todo
        ]);

        return response()->json($todo->load('assignee:id,name'), 201);
    }

    public function show(Request $request, Project $project, Todo $todo)
    {
        $user = $request->user();
        // PERBAIKAN OTORISASI
        // 1. Cek apakah todo ini benar-benar milik project yang di-pass di URL
        if ($todo->project_id !== $project->id) {
            return response()->json(['message' => 'Todo not found in this project.'], 404);
        }
        // 2. Cek apakah user punya akses ke proyek ini
        if ($project->created_by !== $user->id && !$project->approvedMembers()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Unauthorized to view this todo.'], 403);
        }

        return response()->json($todo->load('assignee:id,name'));
    }

    /**
     * Update the specified todo in storage.
     */
    public function update(Request $request, Project $project, Todo $todo)
    {
        $user = $request->user();
        // PERBAIKAN OTORISASI
        if ($todo->project_id !== $project->id) {
            return response()->json(['message' => 'Todo not found in this project.'], 404);
        }
        if ($project->created_by !== $user->id && !$project->approvedMembers()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Unauthorized to update this todo.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date|after_or_equal:today',
            'is_completed' => 'sometimes|boolean',
        ]);

         // Validasi tambahan: jika user_id (assignee) diisi, pastikan dia adalah anggota proyek
        if ($request->filled('user_id') && $request->input('user_id') != null) { // Periksa juga jika tidak null
            $assigneeId = $request->input('user_id');
            if (!$project->approvedMembers()->where('users.id', $assigneeId)->exists() && $project->created_by != $assigneeId) {
                 return response()->json(['errors' => ['user_id' => ['Assignee must be an approved member or the creator of the project.']]], 422);
            }
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $todo->update($request->only(['title', 'description', 'user_id', 'due_date', 'is_completed']));
        return response()->json($todo->load('assignee:id,name'));
    }

    /**
     * Remove the specified todo from storage.
     */
    public function destroy(Request $request, Project $project, Todo $todo)
    {
        $user = $request->user();
        // PERBAIKAN OTORISASI
        if ($todo->project_id !== $project->id) {
            return response()->json(['message' => 'Todo not found in this project.'], 404);
        }
        if ($project->created_by !== $user->id && !$project->approvedMembers()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Unauthorized to delete this todo.'], 403);
        }
        // Otorisasi lebih lanjut: Mungkin hanya pembuat proyek yang bisa hapus semua todo,
        // atau pembuat todo/assignee yang bisa hapus todo mereka.
        // if ($project->created_by !== $user->id /* && $todo->created_by_user_id !== $user->id (jika ada field ini) */ ) {
        //    return response()->json(['message' => 'Only the project creator or todo creator can delete this.'], 403);
        // }

        $todo->delete();
        return response()->json(['message' => 'Todo deleted successfully.'], 200);
    }
}
