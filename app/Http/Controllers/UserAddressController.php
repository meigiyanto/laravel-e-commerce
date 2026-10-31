<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserAddressController extends Controller
{
    public function index(Request $request): View
    {
        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();

        return view('storefront.account.addresses.index', compact('addresses'));
    }

    public function create(): View
    {
        return view('storefront.account.addresses.form', [
            'address' => new UserAddress(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $user = $request->user();

        DB::transaction(function () use ($user, $validated) {
            $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $hasAddress = $user->addresses()->exists();
            $makeDefault = ! $hasAddress || (bool) ($validated['is_default'] ?? false);

            if ($makeDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            $user->addresses()->create([
                ...$validated,
                'is_default' => $makeDefault,
            ]);
        });

        return redirect()
            ->route('account.addresses.index')
            ->with('success', 'Alamat berhasil ditambahkan.');
    }

    public function edit(Request $request, int $address): View
    {
        $address = $this->findOwnedAddress($request, $address);

        return view('storefront.account.addresses.form', compact('address'));
    }

    public function update(Request $request, int $address): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $user = $request->user();

        DB::transaction(function () use ($request, $user, $address, $validated) {
            $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $record = $this->findOwnedAddress($request, $address);
            $makeDefault = array_key_exists('is_default', $validated) ? (bool) $validated['is_default'] : $record->is_default;

            if ($makeDefault) {
                $user->addresses()
                    ->whereKeyNot($record->getKey())
                    ->update(['is_default' => false]);
            }

            $record->update([
                ...$validated,
                'is_default' => $makeDefault,
            ]);
        });

        return redirect()
            ->route('account.addresses.index')
            ->with('success', 'Alamat berhasil diperbarui.');
    }

    public function destroy(Request $request, int $address): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user, $address) {
            $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $record = $this->findOwnedAddress($request, $address);
            $wasDefault = $record->is_default;

            $record->delete();

            if ($wasDefault) {
                $nextAddress = $user->addresses()
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->first();

                $nextAddress?->update(['is_default' => true]);
            }
        });

        return redirect()
            ->route('account.addresses.index')
            ->with('success', 'Alamat berhasil dihapus.');
    }

    public function makeDefault(Request $request, int $address): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user, $address) {
            $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $record = $this->findOwnedAddress($request, $address);

            $user->addresses()->update(['is_default' => false]);
            $record->update(['is_default' => true]);
        });

        return redirect()
            ->route('account.addresses.index')
            ->with('success', 'Alamat utama berhasil diubah.');
    }

    private function findOwnedAddress(Request $request, int $address): UserAddress
    {
        return $request->user()
            ->addresses()
            ->whereKey($address)
            ->firstOrFail();
    }

    private function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'address_line' => ['required', 'string', 'max:5000'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'village' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
