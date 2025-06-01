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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Project $project)
    {
        $user = $request->user();
        // Pastikan user adalah anggota grup dari proyek ini
        if (!$project->group->members()->where('users.id', $user->id)->exists() && $project->group->created_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized to view todos for this project.'], 403);
        }

        $todos = $project->todos()->with('assignee:id,name')->latest()->paginate(10);
        return response()->json($todos);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Project $project)
    {
        $user = $request->user();
        // Pastikan user adalah anggota grup dari proyek ini
        if (!$project->group->members()->where('users.id', $user->id)->exists() && $project->group->created_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized to create todos for this project.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id', // Pastikan user_id (assignee) valid jika ada
            'due_date' => 'nullable|date|after_or_equal:today',
            'is_completed' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $todo = $project->todos()->create([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => $request->user_id,
            'due_date' => $request->due_date,
            'is_completed' => $request->is_completed ?? false,
        ]);

        return response()->json($todo->load('assignee:id,name'), 201);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Todo  $todo
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Project $project, Todo $todo)
    {
        $user = $request->user();
        // Pastikan todo ini milik proyek yang diberikan dan user adalah anggota grup
        if ($todo->project_id !== $project->id || (!$project->group->members()->where('users.id', $user->id)->exists() && $project->group->created_by !== $user->id)) {
            return response()->json(['message' => 'Unauthorized or todo not found in this project.'], 403);
        }
        return response()->json($todo->load('assignee:id,name'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Todo  $todo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Project $project, Todo $todo)
    {
        $user = $request->user();
        // Pastikan todo ini milik proyek yang diberikan dan user adalah anggota grup
        if ($todo->project_id !== $project->id || (!$project->group->members()->where('users.id', $user->id)->exists() && $project->group->created_by !== $user->id)) {
            return response()->json(['message' => 'Unauthorized or todo not found in this project.'], 403);
        }
        // Tambahkan otorisasi lebih lanjut jika perlu (misal, hanya assignee atau pembuat grup yang bisa update)

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date|after_or_equal:today',
            'is_completed' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $todo->update($request->only(['title', 'description', 'user_id', 'due_date', 'is_completed']));
        return response()->json($todo->load('assignee:id,name'));

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Todo  $todo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Project $project, Todo $todo)
    {
        $user = $request->user();
        // Pastikan todo ini milik proyek yang diberikan dan user adalah anggota grup (atau pembuat grup)
        if ($todo->project_id !== $project->id || (!$project->group->members()->where('users.id', $user->id)->exists() && $project->group->created_by !== $user->id)) {
            return response()->json(['message' => 'Unauthorized to delete this todo.'], 403);
        }
        // Tambahkan otorisasi lebih lanjut jika perlu

        $todo->delete();
        return response()->json(['message' => 'Todo deleted successfully.'], 200);

    }
}
