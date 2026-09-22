<div class="ps-top-categories mt-40 mb-40">
    <div class="ps-container">
        <h3>{!! BaseHelper::clean($title) !!}</h3>
        <div class="row justify-content-center">
            @foreach($categories as $category)
                <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-6">
                    <div class="ps-block--category">
                        <a class="ps-block__overlay" href="{{ $category->url }}" title="{{ $category->name }}"></a>
                        {!! RvMedia::image($category->image, $category->name, 'small') !!}
                        <p>{{ $category->name }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
