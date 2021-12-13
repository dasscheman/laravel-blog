<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use BinshopsBlog\Models\BinshopsCategory;
use BinshopsBlog\Models\BinshopsCategoryTranslation;

class MoveCategoryTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('binshops_categories', function (Blueprint $table) {
            $table->string('category_name')->nullable();
            $table->string('slug');
            $table->mediumText('category_description')->nullable();
            $table->unsignedInteger('lang_id')->index();
        });

        $categoryTranslations = BinshopsCategoryTranslation::all();
        foreach ($categoryTranslations as $translation) {
            $category = BinshopsCategory::find($translation->category_id);
            $category->category_name = $translation->category_name;
            $category->slug = $translation->slug;
            $category->category_description = $translation->category_description;
            $category->lang_id = $translation->lang_id;
            $category->save();
        }
        Schema::table('binshops_categories', function (Blueprint $table) {
            $table->string('slug')->unique()->change();;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('binshops_categories', function (Blueprint $table) {
            $table->dropColumn('category_name');
            $table->dropColumn('slug');
            $table->dropColumn('category_description');
            $table->dropColumn('lang_id');
        });
    }
}
