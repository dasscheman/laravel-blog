<?php

namespace BinshopsBlog\Models;

use BinshopsBlog\Baum\Node;
use BinshopsBlog\Helpers;

class BinshopsCategory extends Node
{
    protected $parentColumn = 'parent_id';
    public $siblings = array();

    public $fillable = [
        'parent_id',
        'category_name',
        'slug',
        'category_description',
        'lang_id'
    ];

    public static function boot()
    {
        parent::boot();
    }

    /**
     * The associated Language
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function language()
    {
        return $this->hasOne(BinshopsLanguage::class, 'id', 'lang_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function posts()
    {
        return $this->belongsToMany(BinshopsPost::class, 'binshops_post_categories', 'category_id', 'post_id');
    }

    public function loadSiblings()
    {
        $this->siblings = $this->children()->get();
    }

    public static function loadSiblingsWithList($node_list)
    {
        for ($i = 0; sizeof($node_list) > $i; $i++) {
            $node_list[$i]->loadSiblings();
            if (sizeof($node_list[$i]->siblings) > 0) {
                self::loadSiblingsWithList($node_list[$i]->siblings);
            }
        }
    }

    /**
     * Returns the public facing URL of showing blog posts in this category
     * @return string
     */
    public function url()
    {
        $theChainString = "";
        $cat = $this->get();
        $chain = $cat[0]->getAncestorsAndSelf();
        foreach ($chain as $category) {
            $theChainString .=  "/" . $category->slug;
        }
        return route("binshopsblog.view_category", [$theChainString]);
    }

    /**
     * Returns the URL for an admin user to edit this category
     * @return string
     */
    public function edit_url()
    {
        return route("binshopsblog.admin.categories.edit_category", $this->id);
    }

//    public function parent()
//    {
//        return $this->belongsTo('BinshopsBlog\Models\BinshopsCategory', 'parent_id');
//    }
//
//    public function children()
//    {
//        return $this->hasMany('BinshopsBlog\Models\BinshopsCategory', 'parent_id');
//    }
//
//    // recursive, loads all descendants
//    private function childrenRecursive()
//    {
//        return $this->children()->with('children')->get();
//    }
//
//    public function loadChildren(){
//        $this->childrenCat = $this->childrenRecursive();
//    }

//    public function scopeApproved($query)
//    {
//        dd("A");
//        return $query->where("approved", true);
//    }
}
