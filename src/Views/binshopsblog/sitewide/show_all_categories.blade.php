<h5>@lang('blog.post_categories')</h5>
<ul class="nav">
    @foreach(\BinshopsBlog\Models\BinshopsCategory::orderBy("category_name")->limit(200)->get() as $category)
        <li class="nav-item">
            <a class='nav-link' href='{{$category->url()}}'>{{$category->category_name}}</a>
        </li>
    @endforeach
</ul>