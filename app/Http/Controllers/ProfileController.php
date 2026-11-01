<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('storefront.account.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $oldAvatar = $user->avatar;
        $newAvatar = null;
    
        if ($request->hasFile('avatar')) {
            $newAvatar = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $newAvatar;
        }
    
        $user->fill($validated);
    
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
    
        try {
            $user->save();
        } catch (\Throwable $exception) {
            if ($newAvatar) {
                Storage::disk('public')->delete($newAvatar);
            }
    
            throw $exception;
        }
    
        if ($newAvatar && $oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }
    
        return Redirect::route('profile.edit')->with('success', 'profile has been updated successfully');
    }
    
    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('passwordUpdate', [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'string',
                Password::defaults(),
                'confirmed',
                'different:current_password',
            ],
        ]);
    
        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();
    
        return Redirect::route('profile.edit')->with('success', 'password has been updated successfully');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);
    
        $user = $request->user();
    
        Auth::logout();
    
        DB::transaction(function () use ($user) {
            $user->delete();
        });
    
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    
        return Redirect::to('/');
    }
}
