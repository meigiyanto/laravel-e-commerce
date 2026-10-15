@extends('layouts.admin')

@php
    $editing = isset($attribute);
@endphp

@section('title', $editing ? 'Edit Atribut' : 'Tambah Atribut')
@section('header', $editing ? 'Edit Atribut' : 'Tambah Atribut')

@section('content')
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">
                {{ $editing ? 'Edit Atribut' : 'Tambah Atribut' }}
            </h3>
            <p class="text-soft">
                Atur nama, tipe, dan apakah atribut digunakan untuk varian.
            </p>
        </div>
        <div class="nk-block-head-content">
            <a href="{{ route('admin.attributes.index') }}"
               class="btn btn-outline-primary">
                Kembali
            </a>
        </div>
    </div>
</div>

<div class="card card-bordered mb-4">
    <div class="card-inner">
        <form
            method="POST"
            action="{{ $editing
                ? route('admin.attributes.update', $attribute)
                : route('admin.attributes.store') }}"
        >
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="form-group">
                <label for="name">Nama atribut *</label>
                <input
                    id="name"
                    name="name"
                    class="form-control"
                    value="{{ old('name', $attribute->name ?? '') }}"
                    required
                    maxlength="100"
                    placeholder="Contoh: Warna"
                >
                @error('name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="slug">Slug</label>
                <input
                    id="slug"
                    name="slug"
                    class="form-control"
                    value="{{ old('slug', $attribute->slug ?? '') }}"
                    maxlength="100"
                    placeholder="Kosongkan untuk dibuat otomatis"
                >
                @error('slug')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="type">Tipe tampilan *</label>
                <select id="type" name="type" class="form-control" required>
                    @foreach ([
                        'select' => 'Pilihan daftar',
                        'color' => 'Warna',
                        'text' => 'Teks',
                    ] as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(old('type', $attribute->type ?? 'select') === $value)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('type')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="sort_order">Urutan</label>
                <input
                    id="sort_order"
                    name="sort_order"
                    type="number"
                    min="0"
                    class="form-control"
                    value="{{ old('sort_order', $attribute->sort_order ?? 0) }}"
                >
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input
                        type="checkbox"
                        class="custom-control-input"
                        id="is_variant"
                        name="is_variant"
                        value="1"
                        @checked(old('is_variant', $attribute->is_variant ?? false))
                    >
                    <label class="custom-control-label" for="is_variant">
                        Atribut ini digunakan untuk varian yang dapat dibeli
                    </label>
                </div>
                <small class="text-soft">
                    Contoh: warna, ukuran, atau kapasitas.
                </small>
            </div>

            <button type="submit" class="btn btn-primary">
                {{ $editing ? 'Simpan Perubahan' : 'Buat Atribut' }}
            </button>
        </form>
    </div>
</div>

@if ($editing)
    <div class="card card-bordered">
        <div class="card-inner">
            <h5 class="title mb-1">Nilai Atribut: {{ $attribute->name }}</h5>
            <p class="text-soft">
                Tambahkan pilihan yang dapat dihubungkan ke varian produk.
            </p>

            <form
                method="POST"
                action="{{ route('admin.attributes.values.store', $attribute) }}"
                class="border rounded p-3 mb-4"
            >
                @csrf

                <h6>Tambah nilai</h6>

                <div class="form-group">
                    <label for="value">Nama nilai *</label>
                    <input
                        id="value"
                        name="value"
                        class="form-control"
                        required
                        maxlength="150"
                        placeholder="Contoh: Coklat"
                    >
                </div>

                <div class="form-group">
                    <label for="value_slug">Slug (opsional)</label>
                    <input
                        id="value_slug"
                        name="slug"
                        class="form-control"
                        maxlength="150"
                        placeholder="Dibuat otomatis jika kosong"
                    >
                </div>

                <div class="form-group">
                    <label for="hex_color">Kode warna HEX</label>
                    <input
                        id="hex_color"
                        name="hex_color"
                        class="form-control"
                        maxlength="7"
                        placeholder="#8B4513"
                    >
                    <small class="text-soft">
                        Opsional; gunakan format #RRGGBB. Relevan untuk atribut warna.
                    </small>
                </div>

                <div class="form-group">
                    <label for="value_sort_order">Urutan</label>
                    <input
                        id="value_sort_order"
                        name="sort_order"
                        type="number"
                        min="0"
                        class="form-control"
                        value="0"
                    >
                </div>

                <button class="btn btn-primary" type="submit">
                    Tambah Nilai
                </button>
            </form>

            @if ($attribute->values->isEmpty())
                <p class="text-soft">Belum ada nilai untuk atribut ini.</p>
            @else
                @foreach ($attribute->values as $value)
                    <div class="border rounded p-3 mb-3">
                        <form
                            method="POST"
                            action="{{ route('admin.attributes.values.update', [$attribute, $value]) }}"
                        >
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label>Nama nilai</label>
                                <input
                                    name="value"
                                    class="form-control"
                                    value="{{ $value->value }}"
                                    required
                                    maxlength="150"
                                >
                            </div>

                            <div class="form-group">
                                <label>Slug</label>
                                <input
                                    name="slug"
                                    class="form-control"
                                    value="{{ $value->slug }}"
                                    maxlength="150"
                                >
                            </div>

                            <div class="form-group">
                                <label>Kode warna HEX</label>
                                <input
                                    name="hex_color"
                                    class="form-control"
                                    value="{{ $value->hex_color }}"
                                    maxlength="7"
                                    placeholder="#8B4513"
                                >
                            </div>

                            <div class="form-group">
                                <label>Urutan</label>
                                <input
                                    name="sort_order"
                                    type="number"
                                    min="0"
                                    class="form-control"
                                    value="{{ $value->sort_order }}"
                                >
                            </div>

                            <p class="text-soft small">
                                Digunakan oleh {{ $value->variants_count }} varian.
                            </p>

                            <button class="btn btn-sm btn-primary" type="submit">
                                Simpan Nilai
                            </button>
                        </form>

                        <form
                            method="POST"
                            action="{{ route('admin.attributes.values.destroy', [$attribute, $value]) }}"
                            class="mt-2"
                            onsubmit="return confirm('Hapus nilai atribut ini?')"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn btn-sm btn-outline-danger"
                                @disabled($value->variants_count > 0)
                            >
                                Hapus Nilai
                            </button>
                        </form>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endif
@endsection
