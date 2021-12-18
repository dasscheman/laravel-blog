{{--Used on the index page (so shows a small summary--}}
{{--See the guide on binshops.binshops.com for how to copy these files to your /resources/views/ directory--}}
{{--https://binshops.binshops.com/laravel-blog-package--}}

<div class="card">
    <div class='text-center blog-image card-img-top'>
        <?=$post->image_tag("medium", true, ''); ?>
    </div>
    <div class="card-body">
        <h3 class="card-title"><a href='{{$post->url()}}'>{{$post->title}}</a></h3>
        <h5 class="card-title">{{$post->subtitle}}</h5>
    	<p>{!! $post->postBodyOutput(true) !!}</p>
        <div class="card-footer">
            <small class="text-muted">
            <span class="light-text">@lang('blog.authored_by'): </span> {{$post->post->author->name}}
            <span class="light-text">@lang('blog.posted_at'): </span> {{date('d M Y ', strtotime($post->post->posted_at))}}
        </div>
    </div>
</div>
