@extends("binshopsblog_admin::layouts.admin_layout")
@section("content")


    <h5>Admin - Add post {{$locale}}</h5>
    <form id="add-post-form" method='post' action='{{route("binshopsblog.admin.store_post")}}'  enctype="multipart/form-data" >
        @csrf
        @include("binshopsblog_admin::posts.form", [
            'post' => $post,
            'post_translation' => $post_translation
        ])

        <input id="locale" name="locale" type="text" value="{{$locale}}" hidden>
        @if($post->id)
            <input id="post_id" name="post_id" type="number" value="{{$post->id}}" hidden>
        @endif
        <input type='submit' name="submit_btn" class='btn btn-primary' value='Add new post' >
    </form>
@endsection
