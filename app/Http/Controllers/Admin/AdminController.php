<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;


class AdminController extends Controller
{
    /** Display the authenticated administrator's profile. */
    public function profile(Request $request): View
     {
        return view('admin.profile', ['user' => $request->user()]);
    }

    /** Update the authenticated administrator's personal details and optional avatar. */
    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'address' => ['nullable', 'string', 'max:5000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $oldAvatar = $user->avatar;
        if ($user->email !== $validated['email']) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            unset($validated['avatar']);
        }

        if ($user->email !== $validated['email']) {
            $user->email_verified_at = null;
        }

        $user->fill($validated);
        $user->save();

        // Remove the previous avatar only after the new profile reference is saved.
        if (isset($validated['avatar']) && $oldAvatar && $oldAvatar !== $user->avatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return redirect()->route('admin.profile')->with('status', 'admin-profile-updated');
    }

    /** Update the authenticated administrator's password after verifying the current one. */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('passwordUpdate', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed', 'different:current_password'],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return redirect()->route('admin.profile')->with('status', 'admin-password-updated');
    }
}
