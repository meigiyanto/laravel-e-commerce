<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AttributeController extends Controller
{
    public function index()
    {
        $attributes = Attribute::withCount('values')
            ->with([
                'values' => fn ($query) => $query
                    ->orderBy('sort_order')
                    ->orderBy('value'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.attributes.index', compact('attributes'));
    }

    public function create()
    {
        return view('admin.attributes.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:attributes,name'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:attributes,slug'],
            'type' => ['required', Rule::in(['select', 'color', 'text'])],
            'is_variant' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = $this->makeUniqueSlug(
            $data['slug'] ?: Str::slug($data['name'])
        );

        $data['is_variant'] = $request->boolean('is_variant');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $attribute = Attribute::create($data);

        return redirect()
            ->route('admin.attributes.edit', $attribute)
            ->with('success', 'Atribut berhasil dibuat. Selanjutnya tambahkan nilai atribut.');
    }

    public function edit(Attribute $attribute)
    {
        $attribute->load([
            'values' => fn ($query) => $query
                ->withCount('variants')
                ->orderBy('sort_order')
                ->orderBy('value'),
        ]);

        return view('admin.attributes.form', compact('attribute'));
    }

    public function update(Request $request, Attribute $attribute)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('attributes', 'name')->ignore($attribute->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('attributes', 'slug')->ignore($attribute->id),
            ],
            'type' => ['required', Rule::in(['select', 'color', 'text'])],
            'is_variant' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = $this->makeUniqueSlug(
            $data['slug'] ?: Str::slug($data['name']),
            $attribute->id
        );

        $data['is_variant'] = $request->boolean('is_variant');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $attribute->update($data);

        return redirect()
            ->route('admin.attributes.edit', $attribute)
            ->with('success', 'Atribut berhasil diperbarui.');
    }

    public function destroy(Attribute $attribute)
    {
        if ($attribute->values()->whereHas('variants')->exists()) {
            return back()->with(
                'error',
                'Atribut tidak dapat dihapus karena salah satu nilainya digunakan oleh varian produk.'
            );
        }

        $attribute->delete();

        return redirect()
            ->route('admin.attributes.index')
            ->with('success', 'Atribut berhasil dihapus.');
    }

    private function makeUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug) ?: 'attribute';
        $candidate = $base;
        $suffix = 1;

        while (
            Attribute::query()
                ->where('slug', $candidate)
                ->when(
                    $ignoreId,
                    fn ($query) => $query->where('id', '!=', $ignoreId)
                )
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix++;
        }

        return $candidate;
    }
}
