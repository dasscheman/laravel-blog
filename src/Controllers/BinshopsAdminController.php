<?php

namespace BinshopsBlog\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use BinshopsBlog\Interfaces\BaseRequestInterface;
use BinshopsBlog\Events\BlogPostAdded;
use BinshopsBlog\Events\BlogPostWillBeDeleted;
use BinshopsBlog\Helpers;
use BinshopsBlog\Middleware\LoadLanguage;
use BinshopsBlog\Middleware\PackageSetup;
use BinshopsBlog\Middleware\UserCanManageBlogPosts;
use BinshopsBlog\Models\BinshopsCategoryTranslation;
use BinshopsBlog\Models\BinshopsField;
use BinshopsBlog\Models\BinshopsFieldValue;
use BinshopsBlog\Models\BinshopsLanguage;
use BinshopsBlog\Models\BinshopsPost;
use BinshopsBlog\Models\BinshopsPostTranslation;
use BinshopsBlog\Models\BinshopsUploadedPhoto;
use BinshopsBlog\Requests\CreateBinshopsBlogPostRequest;
use BinshopsBlog\Requests\DeleteBinshopsBlogPostRequest;
use BinshopsBlog\Requests\UpdateBinshopsBlogPostRequest;
use BinshopsBlog\Traits\UploadFileTrait;
use Illuminate\Support\Facades\App;

/**
 * Class BinshopsAdminController
 * @package BinshopsBlog\Controllers
 */
class BinshopsAdminController extends Controller
{
    use UploadFileTrait;
    private $lang_id;

    /**
     * BinshopsAdminController constructor.
     */
    public function __construct()
    {
        $this->middleware(UserCanManageBlogPosts::class);
        $this->middleware(LoadLanguage::class);
        $this->middleware(PackageSetup::class);

        if (!is_array(config("binshopsblog"))) {
            throw new \RuntimeException('The config/binshopsblog.php does not exist. Publish the vendor files for the BinshopsBlog package by running the php artisan publish:vendor command');
        }
    }

    /**
     * View all posts
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        $posts = BinshopsPost::orderBy("posted_at", "desc")
            ->paginate(10);
        $languages = BinshopsLanguage::all();

        return view("binshopsblog_admin::index", [
            'posts' => $posts,
            'languages' => $languages
        ]);
    }

    /**
     * Show form for creating new post
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create_post(Request $request)
    {
        $locale = App::getLocale();
        $post = new BinshopsPost();
        $locale = BinshopsLanguage::defaultLocale()->locale;
        if ($request->get('locale') !== null) {
            $locale = BinshopsLanguage::where('locale', $request->get('locale'))->first()->locale;
        }

        $ts = BinshopsCategoryTranslation::limit(1000)->get();
        if ($request->get('post_id') != null) {
            $post = new BinshopsPost([$request->get('post_id')]);
        }

        return view("binshopsblog_admin::posts.add_post", [
            'cat_ts' => $ts,
            'post' => $post,
            'locale' => $locale,
            'post_translation' => new \BinshopsBlog\Models\BinshopsPostTranslation(),
            'post_id' => -1,
            'fields' => BinshopsField::all()
        ]);
    }

    /**
     * Save a new post - this method is called whenever add post button is clicked
     *
     * @param CreateBinshopsBlogPostRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function store_post(CreateBinshopsBlogPostRequest $request)
    {
        $new_blog_post = null;
        $locale = BinshopsLanguage::where('locale', $request->get('locale'))->first();
        $translation = BinshopsPostTranslation::where(
            [
                ['post_id','=',$request['post_id']],
                ['lang_id', '=', $locale->id]
            ]
        )->first();

        if (!$translation) {
            $translation = new BinshopsPostTranslation();
        }

        if ($request['post_id'] == -1 || $request['post_id'] == null) {
            //cretes new post
            $new_blog_post = new BinshopsPost();
            $translation = new BinshopsPostTranslation();

            $new_blog_post->posted_at = Carbon::now();
        } else {
            //edits post
            $new_blog_post = BinshopsPost::findOrFail($request['post_id']);
        }

        $post_exists = $this->check_if_same_post_exists($request['slug'], $request['lang_id'], $request['post_id']);
        if ($post_exists) {
            Helpers::flash_message("Post already exists - try to change the slug for this language");
        } else {
            $new_blog_post->is_published = $request['is_published'];
            $new_blog_post->user_id = \Auth::user()->id;
            $translation->title = $request['title'];
            $translation->subtitle = $request['subtitle'];
            $translation->short_description = $request['short_description'];
            $translation->post_body = $request['post_body'];
            $translation->seo_title = $request['seo_title'];
            $translation->meta_desc = $request['meta_desc'];
            $translation->slug = $request['slug'];
            $translation->use_view_file = $request['use_view_file'];
            $translation->post_id = $new_blog_post->id;
            $translation->lang_id = $locale->id;

            DB::beginTransaction();
            try {
                $new_blog_post->loadFields($request->categories());
                $new_blog_post->saveOrFail();
                $new_blog_post->updateFieldValues($request->post());

                $this->processUploadedImages($request, $translation);

                $translation->post_id = $new_blog_post->id;
                $translation->saveOrFail();

                $new_blog_post->categories()->sync($request->categories());
                Helpers::flash_message("Post Updated");
                event(new BlogPostAdded($new_blog_post));

                //if there is not error/exception in the above code, it'll commit
                DB::commit();
            } catch (\Exception $e) {
                //if there is an error/exception in the above code before commit, it'll rollback
                DB::rollBack();
            }
        }

        return redirect(route('binshopsblog.admin.index'));
    }

    /**
     * Show form to edit post
     *
     * @param $blogPostId
     * @return mixed
     */
    public function edit_post(Request $request, $blogPostTransId)
    {
        $post_translation = BinshopsPostTranslation::find($blogPostTransId);
        $post = BinshopsPost::findOrFail($post_translation->post_id);
        $ts = BinshopsCategoryTranslation::limit(1000)->get();

        $locale = BinshopsLanguage::defaultLocale()->locale;
        if ($request->get('locale') !== null) {
            $locale = BinshopsLanguage::where('locale', $request->get('locale'))->first()->locale;
        }

        return view("binshopsblog_admin::posts.edit_post", [
            'cat_ts' => $ts,
            'post' => $post,
            'locale' => $locale,
            'post_translation' => $post_translation,
            'fields' => BinshopsField::all()
        ]);
    }

    /**
     * Save changes to a post
     *
     * @param UpdateBinshopsBlogPostRequest $request
     * @param $blogPostId
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function update_post(UpdateBinshopsBlogPostRequest $request, $blogPostId)
    {
        $new_blog_post = BinshopsPost::findOrFail($blogPostId);
        $locale = BinshopsLanguage::where('locale', $request->get('locale'))->first();
        $translation = BinshopsPostTranslation::where(
            [
                ['post_id','=', $new_blog_post->id],
                ['lang_id', '=', $locale->id]
            ]
        )->first();

        if (!$translation) {
            $translation = new BinshopsPostTranslation();
            $new_blog_post->posted_at = Carbon::now();
        }

        $post_exists = $this->check_if_same_post_exists($request['slug'], $request['lang_id'], $blogPostId);
        if ($post_exists) {
            Helpers::flash_message("Post already exists - try to change the slug for this language");
        } else {
            $new_blog_post->is_published = $request['is_published'];
            $new_blog_post->user_id = \Auth::user()->id;

            $translation->title = $request['title'];
            $translation->subtitle = $request['subtitle'];
            $translation->short_description = $request['short_description'];
            $translation->post_body = $request['post_body'];
            $translation->seo_title = $request['seo_title'];
            $translation->meta_desc = $request['meta_desc'];
            $translation->slug = $request['slug'];
            $translation->use_view_file = $request['use_view_file'];
            $translation->lang_id = $locale->id;

            DB::beginTransaction();
            try {
                $new_blog_post->loadFields($request->categories());
                $new_blog_post->saveOrFail();
                $new_blog_post->updateFieldValues($request->post());

                $this->processUploadedImages($request, $translation);

                $translation->post_id = $new_blog_post->id;
                $translation->saveOrFail();
                $new_blog_post->categories()->sync($request->categories());
                Helpers::flash_message("Post Updated");

                //if there is not error/exception in the above code, it'll commit
                DB::commit();
                event(new BlogPostAdded($new_blog_post));
            } catch (\Exception $e) {
                //if there is an error/exception in the above code before commit, it'll rollback
                DB::rollBack();
            }
        }

        return redirect(route('binshopsblog.admin.index'));
    }

    public function remove_photo($postSlug, $lang_id)
    {
        $post = BinshopsPostTranslation::where([
            ["slug", '=', $postSlug],
            ['lang_id', '=', $lang_id]
        ])->firstOrFail();

        $path = public_path('/' . config("binshopsblog.blog_upload_dir"));
        if (!$this->checked_blog_image_dir_is_writable) {
            if (!is_writable($path)) {
                throw new \RuntimeException("Image destination path is not writable ($path)");
            }
        }

        $destinationPath = $this->image_destination_path();

        if (file_exists($destinationPath.'/'.$post->image_large)) {
            unlink($destinationPath.'/'.$post->image_large);
        }

        if (file_exists($destinationPath.'/'.$post->image_medium)) {
            unlink($destinationPath.'/'.$post->image_medium);
        }

        if (file_exists($destinationPath.'/'.$post->image_thumbnail)) {
            unlink($destinationPath.'/'.$post->image_thumbnail);
        }

        $post->image_large = null;
        $post->image_medium = null;
        $post->image_thumbnail = null;
        $post->save();

        Helpers::flash_message("Photo removed");

        return redirect($post->edit_url());
    }

    /**
     * Delete a post
     *
     * @param DeleteBinshopsBlogPostRequest $request
     * @param $blogPostId
     * @return mixed
     */
    public function destroy_post(DeleteBinshopsBlogPostRequest $request, $blogPostId)
    {
        $post = BinshopsPost::findOrFail($blogPostId);
        BinshopsFieldValue::where('post_id', $blogPostId)->delete();
        //archive deleted post

        $post->delete();
        event(new BlogPostWillBeDeleted($post));

        // todo - delete the featured images?
        // At the moment it just issues a warning saying the images are still on the server.

        Helpers::flash_message("Post successfully deleted!");

        return redirect(route('binshopsblog.admin.index'));
    }

    /**
     * Process any uploaded images (for featured image)
     *
     * @param BaseRequestInterface $request
     * @param $new_blog_post
     * @throws \Exception
     * @todo - next full release, tidy this up!
     */
    protected function processUploadedImages(BaseRequestInterface $request, BinshopsPostTranslation $new_blog_post)
    {
        if (!config("binshopsblog.image_upload_enabled")) {
            // image upload was disabled
            return;
        }

        $this->increaseMemoryLimit();

        // to save in db later
        $uploaded_image_details = [];


        foreach ((array)config('binshopsblog.image_sizes') as $size => $image_size_details) {
            if ($image_size_details['enabled'] && $photo = $request->get_image_file($size)) {
                // this image size is enabled, and
                // we have an uploaded image that we can use

                $uploaded_image = $this->UploadAndResize($new_blog_post, $new_blog_post->slug, $image_size_details, $photo);

                $new_blog_post->$size = $uploaded_image['filename'];
                $uploaded_image_details[$size] = $uploaded_image;
            }
        }

        // store the image upload.
        // todo: link this to the binshopsblog_post row.
        if (count(array_filter($uploaded_image_details))>0) {
            BinshopsUploadedPhoto::create([
                'source' => "BlogFeaturedImage",
                'uploaded_images' => $uploaded_image_details,
            ]);
        }
    }

    //translations for the same psots are ignored
    protected function check_if_same_post_exists($slug, $lang_id, $post_id)
    {
        $slg = BinshopsPostTranslation::where(
            [
                ['slug','=', $slug],
                ['lang_id', '=', $lang_id],
                ['post_id', '<>', $post_id]
            ]
        )->first();
        if ($slg) {
            return true;
        } else {
            return false;
        }
    }
}
