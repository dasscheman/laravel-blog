<?php


namespace BinshopsBlog\Models;

use Illuminate\Database\Eloquent\Model;

class BinshopsCategoryTranslation extends Model
{
    public $fillable = [
        'category_id',
        'category_name',
        'slug',
        'category_description',
        'lang_id'
    ];

    /**
     * Get the category that owns the phone.
     */
    public function category()
    {
        return $this->belongsTo(BinshopsCategory::class, 'category_id');
    }

    /**
     * The associated Language
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function language()
    {
        return $this->hasOne(BinshopsLanguage::class, 'id', 'lang_id');
    }

}
