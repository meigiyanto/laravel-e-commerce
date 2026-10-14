
<div class="form-group">
    <label>Choose Existing Gallery Images</label>

    @if (count($availableGalleryImages))
        <div class="row">
            @foreach ($availableGalleryImages as $image)
                <div class="col-6 col-md-3 mb-3">
                    <label class="border rounded p-2 d-block">
                        <img
                            src="{{ $image['url'] }}"
                            alt="{{ $image['filename'] }}"
                            class="img-fluid mb-2"
                            style="width: 100%; height: 130px; object-fit: contain;"
                        >

                        <input
                            type="checkbox"
                            name="existing_image_paths[]"
                            value="{{ $image['path'] }}"
                            @checked(
                                in_array(
                                    $image['path'],
                                    old('existing_image_paths', [])
                                )
                            )
                        >

                        <span>{{ $image['filename'] }}</span>
                    </label>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-soft">
            No unused gallery images available.
            Upload images below or through Product Gallery.
        </p>
    @endif
</div>
