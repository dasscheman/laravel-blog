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

        $default_locale = BinshopsConfiguration::get('DEFAULT_LANGUAGE_LOCALE');
        $lang = BinshopsLanguage::where('locale', $default_locale)
            ->first();
        App::setLocale($lang->locale);
        session()->put('lang_id', $lang->id);

        return $next($request);
    }
}
