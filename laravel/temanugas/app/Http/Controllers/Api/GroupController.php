<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
                       ->orWhereHas('members', function ($query) use ($user) {
                           $query->where('users.id', $user->id);
                       })
                       ->with(['creator:id,name', 'members:id,name']) // Eager load relasi dengan kolom tertentu
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

        // Secara otomatis menambahkan pembuat grup sebagai anggota pertama
        $group->members()->attach(Auth::id());

        return response()->json($group->load(['creator:id,name', 'members:id,name']), 201);
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
        if ($group->created_by !== $user->id && !$group->members()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Unauthorized to view this group.'], 403);
        }
        // Eager load relasi yang dibutuhkan
        return response()->json($group->load(['creator:id,name', 'members:id,name', 'projects']));
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

    public function joinGroup(Request $request, Group $group)
    {
        $user = $request->user();

        // Cek apakah user sudah menjadi anggota
        if ($group->members()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'You are already a member of this group.'], 409); // 409 Conflict
        }

        $group->members()->attach($user->id);
        return response()->json(['message' => 'Successfully joined the group.', 'group' => $group->load('members:id,name')], 200);
    }
    public function leaveGroup(Request $request, Group $group)
    {
        $user = $request->user();

        // Pembuat grup tidak bisa meninggalkan grup (harus menghapus grup atau transfer kepemilikan - logika lebih kompleks)
        if ($group->created_by === $user->id) {
            return response()->json(['message' => 'Group creator cannot leave the group. You can delete the group instead.'], 403);
        }

        // Cek apakah user adalah anggota
        if (!$group->members()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'You are not a member of this group.'], 404);
        }

        $group->members()->detach($user->id);
        return response()->json(['message' => 'Successfully left the group.'], 200);
    }

     /**
     * Get members of a specific group.
     * Mendapatkan anggota dari grup tertentu.
     */
    public function getMembers(Request $request, Group $group)
    {
        // Pastikan user yang meminta adalah anggota atau pembuat grup
        $user = $request->user();
        if ($group->created_by !== $user->id && !$group->members()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Unauthorized to view members of this group.'], 403);
        }

        return response()->json($group->members()->select('users.id', 'users.name', 'users.email')->get());
    }
}
