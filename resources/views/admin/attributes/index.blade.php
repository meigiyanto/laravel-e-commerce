@extends('layouts.admin')

@section('title', 'Pengelolaan Atribut')
@section('header', 'Pengelolaan Atribut')

@section('content')
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">Atribut Produk</h3>
            <p class="text-soft">
                Kelola atribut dan nilai yang digunakan oleh varian produk.
            </p>
        </div>

        <div class="nk-block-head-content">
            <a href="{{ route('admin.attributes.create') }}"
               class="btn btn-primary">
                <em class="icon ni ni-plus"></em>
                <span>Tambah Atribut</span>
            </a>
        </div>
    </div>
</div>

<div class="card card-bordered">
    <div class="card-inner">
        @if ($attributes->isEmpty())
            <p class="text-soft text-center py-4">
                Belum ada atribut produk.
            </p>
        @else
            <div class="table-responsive">
                <table class="table table-middle">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Slug</th>
                            <th>Tipe</th>
                            <th>Untuk Varian</th>
                            <th>Nilai</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attributes as $attribute)
                            <tr>
                                <td>{{ $attribute->name }}</td>
                                <td>{{ $attribute->slug }}</td>
                                <td>{{ $attribute->type }}</td>
                                <td>
                                    @if ($attribute->is_variant)
                                        <span class="badge badge-dim bg-success">
                                            Ya
                                        </span>
                                    @else
                                        <span class="badge badge-dim bg-light text-dark">
                                            Tidak
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    {{ $attribute->values_count }}
                                    nilai
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.attributes.edit', $attribute) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        Kelola
                                    </a>

                                    <form
                                        action="{{ route('admin.attributes.destroy', $attribute) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Hapus atribut ini?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-sm btn-outline-danger"
                                                type="submit">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @if ($attribute->values->isNotEmpty())
                                <tr>
                                    <td colspan="6" class="text-soft small">
                                        Nilai:
                                        {{ $attribute->values->pluck('value')->join(', ') }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
