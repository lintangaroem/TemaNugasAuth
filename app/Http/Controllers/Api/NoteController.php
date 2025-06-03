<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Project; // Import Project model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NoteController extends Controller
{
    /**
     * Display a listing of notes for a specific project.
     */
    public function index(Request $request, Project $project)
    {
        $user = $request->user();
        // Pastikan user adalah anggota grup dari proyek ini
        if (!$project->group->members()->where('users.id', $user->id)->exists() && $project->group->created_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized to view notes for this project.'], 403);
        }

        $notes = $project->notes()->with('creator:id,name')->latest()->paginate(10);
        return response()->json($notes);
    }

    /**
     * Store a newly created note in storage for a specific project.
     */
    public function store(Request $request, Project $project)
    {
        $user = $request->user();
        // Pastikan user adalah anggota grup dari proyek ini
        if (!$project->group->members()->where('users.id', $user->id)->exists() && $project->group->created_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized to create notes for this project.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $note = $project->notes()->create([
            'title' => $request->title,
            'content' => $request->content,
            'user_id' => $user->id, // Pembuat catatan adalah user yang terotentikasi
        ]);

        return response()->json($note->load('creator:id,name'), 201);
    }

    /**
     * Display the specified note.
     */
    public function show(Request $request, Project $project, Note $note)
    {
        $user = $request->user();
        // Pastikan note ini milik proyek yang diberikan dan user adalah anggota grup
        if ($note->project_id !== $project->id || (!$project->group->members()->where('users.id', $user->id)->exists() && $project->group->created_by !== $user->id)) {
            return response()->json(['message' => 'Unauthorized or note not found in this project.'], 403);
        }
        return response()->json($note->load('creator:id,name'));
    }

    /**
     * Update the specified note in storage.
     */
    public function update(Request $request, Project $project, Note $note)
    {
        $user = $request->user();
        // Pastikan note ini milik proyek yang diberikan dan user adalah pembuat catatan ini
        if ($note->project_id !== $project->id || $note->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized to update this note.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'content' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $note->update($request->only(['title', 'content']));
        return response()->json($note->load('creator:id,name'));
    }

    /**
     * Remove the specified note from storage.
     */
    public function destroy(Request $request, Project $project, Note $note)
    {
        $user = $request->user();
         // Pastikan note ini milik proyek yang diberikan dan user adalah pembuat catatan ini atau pembuat grup
        if ($note->project_id !== $project->id || ($note->user_id !== $user->id && $project->group->created_by !== $user->id) ) {
            return response()->json(['message' => 'Unauthorized to delete this note.'], 403);
        }

        $note->delete();
        return response()->json(['message' => 'Note deleted successfully.'], 200);
    }
}
