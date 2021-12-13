SHOULD_AUTO_GEN_SLUG = false;
enableField()
function toggleCheckbox(event){
    let categoryId = event.target.id.replace('category_check','');
    if (event.target.checked){
        let fieldCategories = document.getElementsByClassName('field_category_' + categoryId)
        for (let i=0; i < fieldCategories.length; i++) {
            fieldCategories[i].disabled = false;
        }
        return
    }
    // We cannot be sure which categories are selected, therefore, disable everything and check everything.
    let fieldCategories = document.getElementsByClassName('field_category')
    for (let i=0; i < fieldCategories.length; i++) {
        if (fieldCategories[i].classList.contains('no_categories')) {
            continue;
        }
        fieldCategories[i].disabled = true;
    }
    enableField()
}

function enableField() {
    let categoriesCheckbox = document.getElementsByClassName('category_checkbox')
    for (let i=0; i < categoriesCheckbox.length; i++) {
        if (categoriesCheckbox[i].checked) {
            let categoryId = categoriesCheckbox[i].id.replace('category_check','');
            let fieldCategories = document.getElementsByClassName('field_category_' + categoryId)
            for (let i=0; i < fieldCategories.length; i++) {
                fieldCategories[i].disabled = false;
                fieldCategories[i].style.visibility = "visible"
            }
        }
    }
}

/* Generate the slug field, if it was not touched by the user (or if it was an empty string) */
function populate_slug_field() {
    var cat_slug = document.getElementById('blog_slug');
    if (cat_slug.value.length < 1) {
        // if the slug field is empty, make sure it auto generates
        SHOULD_AUTO_GEN_SLUG = true;
    }

    if (SHOULD_AUTO_GEN_SLUG) {
        // the slug hasn't been manually changed (or it was set above), so we should generate the slug
        // This is done in two stages - one to remove non words/spaces etc, the another to replace white space (and underscore) with a -
        cat_slug.value =document.getElementById("blog_title").value.toLowerCase()
            .replace(/[^\w-_ ]+/g, '') // replace with nothing
            .replace(/[_ ]+/g, '-') // replace _ and spaces with -
            .substring(0,99); // limit str length
    }
}

if (document.getElementById("blog_slug").value.length < 1) {
    SHOULD_AUTO_GEN_SLUG = true;
} else {
    SHOULD_AUTO_GEN_SLUG = false; // there is already a value in #category_slug, so lets pretend it was changed already.
}

let select = document.getElementById("language_list");
select.addEventListener("change", function(){
    toggleCategories(this.value);
});

let defaultSelect = document.getElementsByClassName('category_checkbox')
for(let item = 0; item < select.length; item++) {
    if(select[item].selected == true) {
        toggleCategories(select[item].value);
    }
};

function toggleCategories(lang_id){
    let categories = document.getElementsByClassName('categories')
    for(let item = 0; item < categories.length; item++) {
        categories[item].hidden = false
        for (let child = 0; child < categories[item].children.length; child++) {
            categories[item].children[child].disabled = false
        }
    }
    categories = document.getElementsByClassName('lang_id-' + lang_id)
    for(let item = 0; item < categories.length; item++) {
        categories[item].hidden = true
        for (let child = 0; child < categories[item].children.length; child++) {
            categories[item].children[child].disabled = true
        }
    }
}
