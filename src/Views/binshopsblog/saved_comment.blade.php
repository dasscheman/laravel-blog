@extends("layouts.app",['title'=>"Saved comment"])
@section("content")

    <div class='text-center'>
        <h3>@lang('blog.comment_saved')</h3>

        @if(!config("binshopsblog.comments.auto_approve_comments",false) )
            <p>@lang('blog.approve_comment')</p>
        @endif

        <a href='{{$blog_post->url(app('request')->get('locale'))}}' class='btn btn-primary'>@lang('blog.back_to_post')</a>
    </div>

@endsection