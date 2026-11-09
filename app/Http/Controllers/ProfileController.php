<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Tampilkan form edit profil user yang sedang login.
     */
    public function edit(Request $request): View
    {
        return view('account.profile', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update nama dan (opsional) foto profil user.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user->name = $validated['name'];

        if ($request->hasFile('avatar')) {
            // Hapus foto lama di supabase_avatars sebelum menyimpan yang baru
            if ($user->avatar) {
                Storage::disk('supabase_avatars')->delete($user->avatar);
            }

            // Bucket sudah bernama "avatars", jadi foldernya cukup id user: {id}/namafile.ext
            $user->avatar = $request->file('avatar')->store((string) $user->id, 'supabase_avatars');
        }

        $user->save();

        return back()->with('status', 'profile-updated');
    }

    /**
     * Tampilkan foto profil (dipakai route profile.avatar.show).
     */
    public function avatar(User $user)
    {
        abort_unless($user->avatar, 404);

        return Storage::disk('supabase_avatars')->response($user->avatar, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Hapus foto profil user yang sedang login.
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('supabase_avatars')->delete($user->avatar);
            $user->avatar = null;
            $user->save();
        }

        return back()->with('status', 'avatar-removed');
    }
}