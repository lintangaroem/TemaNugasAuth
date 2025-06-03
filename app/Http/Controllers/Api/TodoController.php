<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TodoController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $user = $request->user();
        if ($project->created_by !== $user->id && !$project->approvedMembers()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Unauthorized to view todos for this project.'], 403);
        }

        // Muat juga creator jika ada
        $todos = $project->todos()->with('creator:id,name')->latest()->paginate(10);
        return response()->json($todos);
    }

    public function store(Request $request, Project $project)
    {
        $user = $request->user();
        if ($project->created_by !== $user->id && !$project->approvedMembers()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Unauthorized to create todos for this project.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'is_completed' => 'sometimes|boolean', 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $todo = $project->todos()->create([
            'title' => $request->title,
            'is_completed' => $request->is_completed ?? false,
            'created_by_user_id' => Auth::id(), // User yang membuat todo
        ]);

        return response()->json($todo->load('creator:id,name'), 201); // Muat creator
    }

    public function show(Request $request, Project $project, Todo $todo)
    {
        $user = $request->user();
        if ($todo->project_id !== $project->id) {
            return response()->json(['message' => 'Todo not found in this project.'], 404);
        }
        if ($project->created_by !== $user->id && !$project->approvedMembers()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Unauthorized to view this todo.'], 403);
        }

        return response()->json($todo->load('creator:id,name')); // Muat creator
    }

    public function update(Request $request, Project $project, Todo $todo)
    {
        $user = $request->user();
        if ($todo->project_id !== $project->id) {
            return response()->json(['message' => 'Todo not found in this project.'], 404);
        }
        // Otorisasi: Siapa yang boleh update? Pembuat todo? Pembuat proyek? Semua anggota proyek?
        // Contoh: Hanya pembuat todo atau pembuat proyek
        if ($todo->created_by_user_id !== $user->id && $project->created_by !== $user->id) {
             return response()->json(['message' => 'Unauthorized to update this todo.'], 403);
        }
        // Atau jika semua anggota proyek boleh update status selesai/belum:
        // if ($project->created_by !== $user->id && !$project->approvedMembers()->where('users.id', $user->id)->exists()) {
        //     return response()->json(['message' => 'Unauthorized to update this todo.'], 403);
        // }


        // Validasi hanya untuk field yang bisa diupdate
        // Biasanya hanya 'title' dan 'is_completed' untuk todo sederhana
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'is_completed' => 'sometimes|required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Hanya update field yang diizinkan
        $updateData = [];
        if ($request->has('title')) {
            $updateData['title'] = $request->title;
        }
        if ($request->has('is_completed')) {
            $updateData['is_completed'] = $request->is_completed;
        }

        if (!empty($updateData)) {
            $todo->update($updateData);
        }

        return response()->json($todo->load('creator:id,name'));
    }

    public function destroy(Request $request, Project $project, Todo $todo)
    {
        $user = $request->user();
        if ($todo->project_id !== $project->id) {
            return response()->json(['message' => 'Todo not found in this project.'], 404);
        }
        // Otorisasi: Siapa yang boleh hapus? Pembuat todo? Pembuat proyek?
        if ($todo->created_by_user_id !== $user->id && $project->created_by !== $user->id) {
             return response()->json(['message' => 'Unauthorized to delete this todo.'], 403);
        }
        // Atau jika semua anggota proyek boleh hapus:
        // if ($project->created_by !== $user->id && !$project->approvedMembers()->where('users.id', $user->id)->exists()) {
        //    return response()->json(['message' => 'Unauthorized to delete this todo.'], 403);
        // }

        $todo->delete();
        return response()->json(['message' => 'Todo deleted successfully.'], 200);
    }
}
