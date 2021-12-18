<?php


namespace BinshopsBlog\Middleware;

use Closure;
use BinshopsBlog\Models\BinshopsConfiguration;
use BinshopsBlog\Models\BinshopsLanguage;
use Illuminate\Support\Facades\App;
use Session;

class LoadLanguage
{
    public function handle($request, Closure $next)
    {
        if (Session::has('locale')) {
            App::setLocale(Session::get('locale'));
            $lang = BinshopsLanguage::where('locale', App::getLocale())->first();
            session()->put('lang_id', $lang->id);
            if ($lang->exists()) {
                return $next($request);
            }
        }
        $lang = BinshopsLanguage::defaultLocale();
        App::setLocale($lang->locale);
        session()->put('lang_id', $lang->id);

        return $next($request);
    }
}
