<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Group; // Import Group model
use App\Models\User;
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
    public function index(Request $request)
    {
        $user = $request->user();
        $projects = Project::where('created_by', $user->id)
            ->orWhereHas('approvedMembers', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with(['creator:id,name', 'approvedMembers:id,name'])
            ->latest()
            ->paginate(10);

        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date|after_or_equal:today',
            // Tidak ada lagi group_name atau group_description di sini
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'created_by' => $user->id,
            'status' => $request->status ?? 'pending',
        ]);

        // Pembuat proyek otomatis menjadi anggota yang disetujui
        $project->allMemberEntries()->attach($user->id, [
            'status' => 'approved',
            'responded_at' => now(),
            'approved_by' => $user->id
        ]);

        return response()->json($project->load(['creator:id,name', 'approvedMembers:id,name']), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function show(Project $project, Request $request)
    {
        $user = $request->user();
        // Pastikan user adalah pembuat atau anggota yang disetujui untuk melihat detail
        if ($project->created_by !== $user->id && !$project->approvedMembers()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Unauthorized to view this project.'], 403);
        }

        // Muat todos, notes, dan pendingRequests jika user adalah pembuat
        $project->load(['todos.assignee:id,name', 'notes.creator:id,name', 'creator:id,name', 'approvedMembers:id,name']);
        if ($project->created_by === $user->id) {
            $project->load('pendingRequests:id,name,email,project_user.created_at as requested_at_pivot');
        }

        return response()->json($project);
    }

    public function update(Request $request, Project $project)
    {
        if ($project->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized to update this project.'], 403);
        }

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
        return response()->json($project->load(['creator:id,name', 'approvedMembers:id,name']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function destroy(Project $project)
    {
        if ($project->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized to delete this project.'], 403);
        }

        $project->delete();
        return response()->json(['message' => 'Project deleted successfully.'], 200);
    }
    public function requestToJoinProject(Request $request, Project $project)
    {
        $user = $request->user();

        if ($project->created_by === $user->id) {
            return response()->json(['message' => 'You are the creator of this project.'], 400);
        }

        $existingEntry = $project->allMemberEntries()->where('users.id', $user->id)->first();
        if ($existingEntry) {
            $status = $existingEntry->pivot->status;
            if ($status === 'approved') return response()->json(['message' => 'You are already a member.'], 409);
            if ($status === 'pending') return response()->json(['message' => 'Your request is already pending.'], 409);
            // Jika rejected, izinkan request ulang
            $project->allMemberEntries()->updateExistingPivot($user->id, ['status' => 'pending', 'responded_at' => null, 'approved_by' => null, 'created_at' => now()]);
            return response()->json(['message' => 'Request to join re-submitted.'], 200);
        }

        $project->allMemberEntries()->attach($user->id, ['status' => 'pending', 'created_at' => now()]);
        return response()->json(['message' => 'Request to join project sent. Waiting for approval.'], 200);
    }

    public function listJoinRequests(Request $request, Project $project)
    {
        if ($project->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        $pendingRequests = $project->pendingRequests()
            ->select('users.id', 'users.name', 'users.email', 'project_user.created_at as requested_at_pivot')
            ->get();
        return response()->json($pendingRequests);
    }

    public function approveJoinRequest(Request $request, Project $project, User $userToManage)
    {
        if ($project->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        if (!$project->pendingRequests()->where('users.id', $userToManage->id)->exists()) {
            return response()->json(['message' => 'No pending request found for this user.'], 404);
        }
        $project->allMemberEntries()->updateExistingPivot($userToManage->id, [
            'status' => 'approved',
            'responded_at' => now(),
            'approved_by' => Auth::id()
        ]);
        return response()->json(['message' => 'User ' . $userToManage->name . ' approved to join.']);
    }

    public function rejectJoinRequest(Request $request, Project $project, User $userToManage)
    {
        if ($project->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        if (!$project->pendingRequests()->where('users.id', $userToManage->id)->exists()) {
            return response()->json(['message' => 'No pending request found for this user.'], 404);
        }
        $project->allMemberEntries()->updateExistingPivot($userToManage->id, [
            'status' => 'rejected',
            'responded_at' => now(),
            'approved_by' => Auth::id()
        ]);
        return response()->json(['message' => 'User ' . $userToManage->name . ' request rejected.']);
    }

    public function leaveProject(Request $request, Project $project)
    {
        $user = $request->user();
        if ($project->created_by === $user->id) {
            return response()->json(['message' => 'Creator cannot leave. Delete the project instead.'], 403);
        }
        if (!$project->approvedMembers()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'You are not an approved member.'], 404);
        }
        $project->allMemberEntries()->detach($user->id);
        return response()->json(['message' => 'Successfully left the project.'], 200);
    }

    public function getProjectMembers(Request $request, Project $project)
    {
        $user = $request->user();
        if ($project->created_by !== $user->id && !$project->approvedMembers()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        return response()->json($project->approvedMembers()->select('users.id', 'users.name', 'users.email')->get());
    }


    public function storeWithNewGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_name' => 'required|string|max:255',
            'project_description' => 'nullable|string',
            'project_deadline' => 'nullable|date|after_or_equal:today',
            'group_name' => 'required|string|max:255',
            'group_description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // 1. Buat Grup
        $group = Group::create([
            'name' => $request->group_name,
            'description' => $request->group_description,
            'created_by' => $user->id,
        ]);

        // 2. Tambahkan pembuat sebagai anggota grup yang disetujui
        $group->allMemberEntries()->attach($user->id, [
            'status' => 'approved',
            'responded_at' => now(),
            'approved_by' => $user->id
        ]);

        // 3. Buat Proyek
        $project = $group->projects()->create([
            'name' => $request->project_name,
            'description' => $request->project_description,
            'deadline' => $request->project_deadline,
            'status' => 'pending', // Default status
        ]);

        // Muat relasi yang mungkin dibutuhkan frontend
        $project->load('group.creator', 'group.approvedMembers');

        return response()->json($project, 201);
    }
}
