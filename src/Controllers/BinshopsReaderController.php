<?php

namespace BinshopsBlog\Controllers;

use App\Http\Controllers\Controller;
use BinshopsBlog\Models\BinshopsFieldValue;
use Carbon\Carbon;
use BinshopsBlog\Laravel\Fulltext\Search;
use BinshopsBlog\Models\BinshopsCategoryTranslation;
use Illuminate\Http\Request;
use BinshopsBlog\Captcha\UsesCaptcha;
//use BinshopsBlog\Laravel\Fulltext\Search;
use BinshopsBlog\Middleware\LoadLanguage;
use BinshopsBlog\Models\BinshopsCategory;
//use BinshopsBlog\Models\BinshopsCategoryTranslation;
use BinshopsBlog\Models\BinshopsLanguage;
use BinshopsBlog\Models\BinshopsPostTranslation;
//use Carbon\Carbon;
//use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Session;

/**
 * Class BinshopsReaderController
 * All of the main public facing methods for viewing blog content (index, single posts)
 * @package BinshopsBlog\Controllers
 */
class BinshopsReaderController extends Controller
{
    use UsesCaptcha;
    private $lang_id;

    public function __construct()
    {
//        $this->middleware(LoadLanguage::class, ['except' => ['changeLanguage']]);
//        $this->middleware(LoadLanguage::class);
//        dd('TEST' . App::getLocale());
//        $this->middleware('local');
//        dump(App::getLocale());
//        $this->lang_id = BinshopsLanguage::where('locale', App::getLocale())->first()->id;
//        dump($this->lang_id);
    }

    /**
     * Show blog posts
     * If category_slug is set, then only show from that category
     *
     * @param null $category_slug
     * @return mixed
     */
    public function index(Request $request, $categorySlug = null)
    {
        // the published_at + is_published are handled by BinshopsBlogPublishedScope,
        // and don't take effect if the logged in user can manageb log posts
        //todo
        $title = 'Blog Page'; // default title...

        $categoryChain = null;


        $posts = BinshopsPostTranslation::where('lang_id', Session::get('lang_id'))
            ->with(['post' => function ($query) {
                $query->where('is_published', '=', true);
                $query->where('posted_at', '<', Carbon::now()->format('Y-m-d H:i:s'));
                $query->orderBy('posted_at', 'desc');
            }])->paginate(config('binshopsblog.per_page', 10));

        if ($categorySlug) {
            $category = BinshopsCategoryTranslation::where("slug", $categorySlug)
                ->with('category')
                ->firstOrFail()->category;
            $categoryChain = $category->getAncestorsAndSelf();

            $posts = BinshopsPostTranslation::where('lang_id', Session::get('lang_id'))
                ->whereHas('post', function ($query) use ($category) {
                    $query->whereHas('categories', function ($q) use ($category) {
                        $q->where('binshops_post_categories.category_id', $category->id);
                    })
                        ->where('is_published', '=', true)
                        ->where('posted_at', '<', Carbon::now()->format('Y-m-d H:i:s'))
                        ->orderBy('posted_at', 'desc');
                })->paginate(config('binshopsblog.per_page', 10));

            // at the moment we handle this special case (viewing a category) by hard coding in the following two lines.
            // You can easily override this in the view files.
            \View::share('binshopsblog_category', $category); // so the view can say "You are viewing
                                                             // $CATEGORYNAME category posts"
            $title = 'Posts in ' . $category->category_name . ' category'; // hardcode title here...
        }

        //load category hierarchy
        $rootList = BinshopsCategory::roots()->get();
        BinshopsCategory::loadSiblingsWithList($rootList);

        return view('binshopsblog::index', [
            'lang_list' => BinshopsLanguage::all('locale', 'name'),
            'category_chain' => $categoryChain,
            'categories' => $rootList,
            'posts' => $posts,
            'title' => $title,
        ]);
    }

    /**
     * Show the search results for $_GET['s']
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function search(Request $request)
    {
        if (!config("binshopsblog.search.search_enabled")) {
            throw new \Exception("Search is disabled");
        }
        $query = $request->get("s");
        $search = new Search();
        $search_results = $search->run($query);

        \View::share("title", "Search results for " . e($query));

        $rootList = BinshopsCategory::roots()->get();
        BinshopsCategory::loadSiblingsWithList($rootList);

        return view(
            "binshopsblog::search",
            [
                'categories' => $rootList,
                'query' => $query,
                'search_results' => $search_results]
        );
    }

    /**
     * View all posts in $category_slug category
     *
     * @param Request $request
     * @param $category_slug
     * @return mixed
     */
    public function view_category(Request $request, $hierarchy)
    {
        $categories = explode('/', $hierarchy);
        return $this->index($request, end($categories));
    }

    /**
     * View a single post and (if enabled) it's comments
     *
     * @param Request $request
     * @param $blogPostSlug
     * @return mixed
     */
    public function viewSinglePost(Request $request, $blogPostSlug)
    {
        // the published_at + is_published are handled by BinshopsBlogPublishedScope, and don't take effect if the logged in user can manage log posts
        $blog_post = BinshopsPostTranslation::where([
            ["slug", "=", $blogPostSlug]
        ])->firstOrFail();
        $fieldValues = BinshopsFieldValue::where('post_id', $blog_post->post_id)->get();

        if ($captcha = $this->getCaptchaObject()) {
            $captcha->runCaptchaBeforeShowingPosts($request, $blog_post);
        }

        return view("binshopsblog::single_post", [
            'post' => $blog_post,
            // the default scope only selects approved comments, ordered by id
            'comments' => $blog_post->post->comments()
                ->with("user")
                ->get(),
            'captcha' => $captcha,
            'fieldvalues' => $fieldValues
        ]);
    }
}
