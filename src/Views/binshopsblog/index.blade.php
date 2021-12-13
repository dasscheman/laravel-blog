@extends("layouts.app",['title'=>$title])

@section('blog-custom-css')
    <link type="text/css" href="{{ asset('blog/css/binshops-blog.css') }}" rel="stylesheet">
@endsection

@section("content")

    <div class='col-sm-12 binshopsblog_container'>
        @if(\Auth::check() && \Auth::user()->canManageBinshopsBlogPosts())
            <div class="text-center">
                <p class='mb-1'>You are logged in as a blog admin user.
                    <br>
                    <a href='{{route("binshopsblog.admin.index")}}'
                       class='btn border  btn-outline-primary btn-sm '>
                        <i class="fa fa-cogs" aria-hidden="true"></i>
                        Go To Blog Admin Panel</a>
                </p>
            </div>
        @endif
        {{ $posts->links() }}
{{--        <div class="row"`>--}}
            <div class="col-md-9">
                @if(isset($binshopsblog_category) && $binshopsblog_category)
                    <h2 class='text-center'> {{$binshopsblog_category->category_name}}</h2>

                    @if($binshopsblog_category->category_description)
                        <p class='text-center'>{{$binshopsblog_category->category_description}}</p>
                    @endif

                @endif

                <div class="card-columns">
{{--                    <div class="container">--}}
{{--                        <div class="row">--}}
                            @forelse($posts as $post)
{{--                                <div class="card">--}}
{{--                                        sadfadsf--}}
                                    @include("binshopsblog::partials.index_loop")
{{--                                </div>--}}
{{--                            @endforeach--}}
                            @empty
                                <div class="col-md-12">
                                    <div class='alert alert-danger'>No posts!</div>
                                </div>
                            @endforelse
{{--                        </div>--}}
{{--                    </div>--}}
                </div>
            </div>
{{--        </div>--}}

        @if (config('binshopsblog.search.search_enabled') )
            @include('binshopsblog::sitewide.search_form')
            @endif
    </div>

@endsection
