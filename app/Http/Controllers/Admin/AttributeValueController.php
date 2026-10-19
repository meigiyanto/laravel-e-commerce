<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AttributeValueController extends Controller
{
    public function store(Request $request, Attribute $attribute)
    {
        $data = $request->validate([
            'value' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150'],
            'hex_color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $base = Str::slug($data['slug'] ?: $data['value']) ?: 'value';
        $slug = $base;
        $suffix = 1;

        while (
            $attribute->values()->where('slug', $slug)->exists()
        ) {
            $slug = $base . '-' . $suffix++;
        }

        $attribute->values()->create([
            'value' => $data['value'],
            'slug' => $slug,
            'hex_color' => $attribute->type === 'color'
                ? ($data['hex_color'] ?? null)
                : null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.attributes.edit', $attribute)
            ->with('success', 'Nilai atribut berhasil ditambahkan.');
    }

    public function update(
        Request $request,
        Attribute $attribute,
        AttributeValue $attributeValue
    ) {
        abort_unless(
            $attributeValue->attribute_id === $attribute->id,
            404
        );

        $data = $request->validate([
            'value' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150'],
            'hex_color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $base = Str::slug($data['slug'] ?: $data['value']) ?: 'value';
        $slug = $base;
        $suffix = 1;

        while (
            $attribute->values()
                ->where('slug', $slug)
                ->where('id', '!=', $attributeValue->id)
                ->exists()
        ) {
            $slug = $base . '-' . $suffix++;
        }

        $attributeValue->update([
            'value' => $data['value'],
            'slug' => $slug,
            'hex_color' => $attribute->type === 'color'
                ? ($data['hex_color'] ?? null)
                : null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.attributes.edit', $attribute)
            ->with('success', 'Nilai atribut berhasil diperbarui.');
    }

    public function destroy(
        Attribute $attribute,
        AttributeValue $attributeValue
    ) {
        abort_unless(
            $attributeValue->attribute_id === $attribute->id,
            404
        );

        if ($attributeValue->variants()->exists()) {
            return back()->with(
                'error',
                'Nilai ini tidak dapat dihapus karena masih digunakan oleh varian produk.'
            );
        }

        $attributeValue->delete();

        return redirect()
            ->route('admin.attributes.edit', $attribute)
            ->with('success', 'Nilai atribut berhasil dihapus.');
    }
}
