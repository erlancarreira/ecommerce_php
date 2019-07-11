<?php 
//use \ec\Page;
use \ec\PageAdmin;
use \ec\Model\User;
use \ec\Model\Category;
use \ec\Model\Product;

$app->get("/admin/categories", function() {
    
    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : '';

    $page = (isset($_GET['page'])) ? (int) $_GET['page'] : 1;

    $pagination = Category::getPage($search, $page);

    $pages = [];

    for ($i=1; $i <= $pagination['pages']; $i++) { 
        array_push($pages, [
            'href' => '/admin/categories?'.http_build_query([
                'page'   => $i,
                'search' => $search  
            ]),
            'text' => $i
        ]);
    }
    
    $page = new PageAdmin();
    
    $categories = Category::listAll();

    $page->setTpl("categories", [
        "categories" => $pagination['data'],
        "search"     => $search,
        "pages"      => $pages
    ]); 
});

$app->get("/admin/categories/create", function () {
    
    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("categories-create");
});


$app->post("/admin/categories/create", function ($request, $response, $args) {
    
    User::verifyLogin();

    $category = new Category();
     
    $category->setData($_POST);

    $category->save();
    
    return $response->withRedirect('/admin/categories'); 
    
  
});

$app->get("/admin/categories/{idcategory}", function($request, $response, $args) {
    
    User::verifyLogin();

    $category = new Category();

    $category->get((int) $args['idcategory']); 
    
    $page = new PageAdmin();

    $page->setTpl("categories-update", [
       "category" => $category->getData()
    ]);
});

$app->post("/admin/categories/{idcategory}", function($request, $response, $args) {
    
    User::verifyLogin();

    $category = new Category();

    $category->get((int) $args['idcategory']); 
    
    $category->setData($_POST);

    $category->save(); 

    return $response->withRedirect('/admin/categories'); 
});

$app->get("/admin/categories/delete/{idcategory}", function($request, $response, $args) {
     
    User::verifyLogin();

    $category = new Category();

    $category->get((int) $args['idcategory']); 

    $category->delete();

    return $response->withRedirect('/admin/categories'); 
});

$app->get("/admin/categories/products/{idcategory}", function($request, $response, $args) {
    
    //var_dump($args['idcategory']); exit;
    User::verifyLogin();

    $category = new Category();

    $category->get((int) $args['idcategory']); 
    
    $page = new PageAdmin();

    $page->setTpl("../categories-products", [
       "category"            => $category->getData(),
       "productsRelated"     => $category->getProducts(),
       "productsNotRelated"  => $category->getProducts(false)
    ]); 
});


$app->get("/admin/categories/{idcategory}/products/{idproduct}/add", function($request, $response, $args) {
    
    //var_dump($args['idcategory']); exit;
    User::verifyLogin();

    $category = new Category();

    $category->get((int) $args['idcategory']); 
    
    $product = new Product();

    $product->get((int) $args['idproduct']);

    $category->addProduct($product);

    return $response->withRedirect("/admin/categories/products/{$args['idcategory']}"); 
    
});

$app->get("/admin/categories/{idcategory}/products/{idproduct}/remove", function($request, $response, $args) {
    
    //var_dump($args['idcategory']); exit;
    User::verifyLogin();

    $category = new Category();

    $category->get((int) $args['idcategory']); 
    
    $product = new Product();

    $product->get((int) $args['idproduct']);

    $category->removeProduct($product);

    var_dump($arg['idcategory']);

    return $response->withRedirect("/admin/categories/products/{$args['idcategory']}"); 
    
});


?>