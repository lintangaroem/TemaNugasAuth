<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Group; // Import Group model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Group $group)
    {

        $user = $request->user();
        // Pastikan user adalah anggota grup
        if (!$group->members()->where('users.id', $user->id)->exists() && $group->created_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized to view projects in this group.'], 403);
        }

        $projects = $group->projects()->with('todos', 'notes')->latest()->paginate(10);
        return response()->json($projects);

    }

    public function store(Request $request, Group $group)
    {
        $user = $request->user();
        // Pastikan user adalah anggota grup atau pembuat grup
        if (!$group->members()->where('users.id', $user->id)->exists() && $group->created_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized to create projects in this group.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date|after_or_equal:today',
            'status' => 'nullable|string|in:pending,in_progress,completed,on_hold,cancelled', // Sesuaikan status yang valid
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $project = $group->projects()->create([
            'name' => $request->name,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'status' => $request->status ?? 'pending', // Default status jika tidak diberikan
        ]);

        return response()->json($project, 201);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function show(Project $project, Group $group, Request $request)
    {
        $user = $request->user();
        // Pastikan proyek ini milik grup yang diberikan dan user adalah anggota grup
        if ($project->group_id !== $group->id || (!$group->members()->where('users.id', $user->id)->exists() && $group->created_by !== $user->id)) {
            return response()->json(['message' => 'Unauthorized or project not found in this group.'], 403);
        }

        return response()->json($project->load(['todos.assignee:id,name', 'notes.creator:id,name']));
    }

    public function update(Request $request, Project $project, Group $group)
    {
         $user = $request->user();
        // Pastikan proyek ini milik grup yang diberikan dan user adalah anggota grup (atau pembuat grup)
         if ($project->group_id !== $group->id || (!$group->members()->where('users.id', $user->id)->exists() && $group->created_by !== $user->id)) {
            return response()->json(['message' => 'Unauthorized or project not found in this group.'], 403);
        }
        // Mungkin hanya anggota tertentu atau pembuat proyek/grup yang boleh update, tambahkan logika otorisasi jika perlu

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date|after_or_equal:today',
            'status' => 'nullable|string|in:pending,in_progress,completed,on_hold,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $project->update($request->only(['name', 'description', 'deadline', 'status']));
        return response()->json($project);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function destroy(Project $project, Group $group, Request $request)
    {
        $user = $request->user();
        // Pastikan proyek ini milik grup yang diberikan dan user adalah pembuat grup atau memiliki hak khusus
         if ($project->group_id !== $group->id || $group->created_by !== $user->id) { // Contoh: Hanya pembuat grup yang bisa hapus proyek
            return response()->json(['message' => 'Unauthorized to delete this project.'], 403);
        }

        $project->delete();
        return response()->json(['message' => 'Project deleted successfully.'], 200);

    }
}
