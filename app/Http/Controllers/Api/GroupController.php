<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        // Ambil grup yang dibuat oleh user ATAU dimana user adalah anggota
        $groups = Group::where('created_by', $user->id)
            ->orWhereHas('approvedMembers', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with(['creator:id,name', 'approvedMembers:id,name']) // Eager load relasi dengan kolom tertentu
            ->latest() // Urutkan berdasarkan yang terbaru
            ->paginate(10); // Tambahkan paginasi

        return response()->json($groups);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => Auth::id(), // atau $request->user()->id
        ]);
        $group->allMemberEntries()->attach(Auth::id(), [
            'status' => 'approved',
            'responded_at' => now(),
            'approved_by' => Auth::id()
        ]);

        return response()->json($group->load(['creator:id,name', 'approvedMembers:id,name']), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function show(Group $group, Request $request)
    {
        $user = $request->user();
        if ($group->created_by !== $user->id && !$group->approvedMembers()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Unauthorized to view this group.'], 403);
        }
        // Eager load relasi yang dibutuhkan
        return response()->json($group->load(['creator:id,name', 'approvedMembers:id,name', 'projects']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Group $group)
    {
        if ($group->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized to update this group.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $group->update($request->only(['name', 'description']));

        return response()->json($group->load(['creator:id,name', 'members:id,name']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function destroy(Group $group)
    {
        if ($group->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized to delete this group.'], 403);
        }

        $group->delete();
        return response()->json(['message' => 'Group deleted successfully.'], 200);
    }

    /**
     * Allow an authenticated user to request to join a group.
     * (Menggantikan 'joinGroup' sebelumnya)
     */
    public function requestToJoinGroup(Request $request, Group $group)
    {
        $user = $request->user();

        // Cek apakah user adalah pembuat grup (sudah otomatis jadi anggota)
        if ($group->created_by === $user->id) {
            return response()->json(['message' => 'You are the creator of this group.'], 400);
        }

        // Cek apakah user sudah memiliki entri di pivot table (pending, approved, atau rejected)
        $existingEntry = $group->allMemberEntries()->where('users.id', $user->id)->first();

        if ($existingEntry) {
            $pivotStatus = $existingEntry->pivot->status;
            if ($pivotStatus === 'approved') {
                return response()->json(['message' => 'You are already a member of this group.'], 409);
            } elseif ($pivotStatus === 'pending') {
                return response()->json(['message' => 'Your request to join is already pending.'], 409);
            } elseif ($pivotStatus === 'rejected') {
                // Kebijakan: jika pernah ditolak, apakah boleh request lagi? Untuk sekarang, kita izinkan request ulang.
                // Jika tidak boleh, return error di sini.
                // Jika boleh, kita akan update status menjadi pending lagi.
                $group->allMemberEntries()->updateExistingPivot($user->id, [
                    'status' => 'pending',
                    'responded_at' => null,
                    'approved_by' => null,
                    'created_at' => now(), // Perbarui waktu permintaan
                    'updated_at' => now()
                ]);
                 return response()->json(['message' => 'Your request to join has been re-submitted.'], 200);
            }
        }

        // Buat permintaan baru
        $group->allMemberEntries()->attach($user->id, ['status' => 'pending', 'created_at' => now(), 'updated_at' => now()]); // created_at dari withTimestamps

        return response()->json(['message' => 'Request to join group sent successfully. Waiting for approval.'], 200);
    }

    /**
     * Allow an authenticated user (who is an approved member but not the creator) to leave a group.
     */
    public function leaveGroup(Request $request, Group $group)
    {
        $user = $request->user();

        if ($group->created_by === $user->id) {
            return response()->json(['message' => 'Group creator cannot leave the group. You can delete the group instead.'], 403);
        }

        $isApprovedMember = $group->approvedMembers()->where('users.id', $user->id)->exists();

        if (!$isApprovedMember) {
            return response()->json(['message' => 'You are not an approved member of this group or your request is not approved.'], 404);
        }

        $group->allMemberEntries()->detach($user->id); // Hapus entri dari pivot
        return response()->json(['message' => 'Successfully left the group.'], 200);
    }

    /**
     * Get approved members of a specific group.
     */
    public function getMembers(Request $request, Group $group)
    {
        $user = $request->user();
        // Hanya pembuat atau anggota yang disetujui yang boleh melihat daftar anggota
        if ($group->created_by !== $user->id && !$group->approvedMembers()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Unauthorized to view members of this group.'], 403);
        }
        return response()->json($group->approvedMembers()->select('users.id', 'users.name', 'users.email')->get());
    }

    // --- Metode Baru untuk Approval ---

    /**
     * List pending join requests for a group (only for group creator).
     */
    public function listJoinRequests(Request $request, Group $group)
    {
        if ($group->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized to manage join requests for this group.'], 403);
        }
        // Memuat nama user yang request, email, dan kapan request dibuat (dari pivot table created_at)
        $pendingRequests = $group->pendingRequests()
                                ->select('users.id', 'users.name', 'users.email', 'group_user.created_at as requested_at_pivot')
                                ->get();

        return response()->json($pendingRequests);
    }

    /**
     * Approve a join request for a user (only by group creator).
     * $userId adalah ID dari user yang permintaannya akan di-approve.
     */
    public function approveJoinRequest(Request $request, Group $group, User $userToManage) // Menggunakan User model binding
    {
        if ($group->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized to manage join requests for this group.'], 403);
        }

        // Cek apakah userToManage memang punya request pending
        $requestExists = $group->pendingRequests()->where('users.id', $userToManage->id)->exists();
        if (!$requestExists) {
            return response()->json(['message' => 'No pending request found for this user in this group.'], 404);
        }

        $group->allMemberEntries()->updateExistingPivot($userToManage->id, [
            'status' => 'approved',
            'responded_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        // Kirim notifikasi ke $userToManage jika perlu

        return response()->json(['message' => 'User ' . $userToManage->name . ' has been approved to join the group.']);
    }

    /**
     * Reject a join request for a user (only by group creator).
     * $userId adalah ID dari user yang permintaannya akan di-reject.
     */
    public function rejectJoinRequest(Request $request, Group $group, User $userToManage) // Menggunakan User model binding
    {
        if ($group->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized to manage join requests for this group.'], 403);
        }

        // Cek apakah userToManage memang punya request pending
        $requestExists = $group->pendingRequests()->where('users.id', $userToManage->id)->exists();
        if (!$requestExists) {
            return response()->json(['message' => 'No pending request found for this user in this group.'], 404);
        }

        $group->allMemberEntries()->updateExistingPivot($userToManage->id, [
            'status' => 'rejected',
            'responded_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        // Kirim notifikasi ke $userToManage jika perlu

        return response()->json(['message' => 'User ' . $userToManage->name . '\'s request to join the group has been rejected.']);
    }
}
