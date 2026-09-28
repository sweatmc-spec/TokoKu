<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
     * Update nama, username, dan (opsional) foto profil user.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];

        if ($request->hasFile('avatar')) {
            // Hapus foto lama di s3_avatars sebelum menyimpan yang baru
            if ($user->avatar) {
                Storage::disk('s3_avatars')->delete($user->avatar);
            }

            $user->avatar = $request->file('avatar')->store('avatars', 's3_avatars');
        }

        $user->save();

        return back()->with('status', 'profile-updated');
    }

    /**
     * Hapus foto profil user yang sedang login.
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('s3_avatars')->delete($user->avatar);
            $user->avatar = null;
            $user->save();
        }

        return back()->with('status', 'avatar-removed');
    }
}