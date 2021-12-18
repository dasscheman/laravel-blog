@extends("layouts.app",['title'=>$title])

@section('blog-custom-css')
    <link type="text/css" href="{{ asset('blog/css/binshops-blog.css') }}" rel="stylesheet">
@endsection

@section("content")
    <div class='col-sm-12 binshopsblog_container'>
        @if(\Auth::check() && \Auth::user()->canManageBinshopsBlogPosts())
            <div class="text-center">
                <p class='mb-1'>@lang('blog.you_are_admin')
                    <br>
                    <a href='{{route("binshopsblog.admin.index")}}'
                       class='btn border  btn-outline-primary btn-sm '>
                        <i class="fa fa-cogs" aria-hidden="true"></i>
                        @lang('blog.go_to_admin')</a>
                </p>
            </div>
        @endif
        {{ $posts->links() }}
            <div class="col-md-9">
                @if(isset($binshopsblog_category) && $binshopsblog_category)
                    <h2 class='text-center'> {{$binshopsblog_category->category_name}}</h2>

                    @if($binshopsblog_category->category_description)
                        <p class='text-center'>{{$binshopsblog_category->category_description}}</p>
                    @endif

                @endif

                <div class="card-columns">
                    @forelse($posts as $post)
                        @include("binshopsblog::partials.index_loop")
                    @empty
                        <div class="col-md-12">
                            <div class='alert alert-danger'>@lang('blog.no_posts')</div>
                        </div>
                    @endforelse
                </div>
            </div>

        @if (config('binshopsblog.search.search_enabled') )
            @include('binshopsblog::sitewide.search_form')
            @endif
    </div>
@endsection
