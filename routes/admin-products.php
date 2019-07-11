<?php 

use \ec\PageAdmin; 
use \ec\Model\User;
use \ec\Model\Product;

$app->get("/admin/products", function() {
    
    User::verifyLogin(); 

    $search = (isset($_GET['search'])) ? $_GET['search'] : '';

    $page = (isset($_GET['page'])) ? (int) $_GET['page'] : 1;

    $pagination = Product::getPage($search, $page);

    $pages = [];

    for ($i=1; $i <= $pagination['pages']; $i++) { 
        array_push($pages, [
            'href' => '/admin/products?'.http_build_query([
                'page'   => $i,
                'search' => $search  
            ]),
            'text' => $i
        ]);
    }

	$page = new PageAdmin();

	$page->setTpl("products", [
        "products"  => $pagination['data'],
        "search" => $search,
        "pages"  => $pages
	]);
});

$app->get("/admin/products/create", function($request, $response, $args) {
    
    User::verifyLogin(); 
	
	$page = new PageAdmin();

	$page->setTpl("products-create");
});

$app->post("/admin/products/create", function($request, $response, $args) {
    
    User::verifyLogin(); 
	
	$product = new Product();

	$product->setData($_POST);

	$product->save();

	return $response->withRedirect('/admin/products'); 
});

$app->get("/admin/products/{idproduct}", function($request, $response, $args) {
    
    User::verifyLogin(); 
	
	$product = new Product();


	$product->get((int) $args['idproduct']);

  // var_dump($args['idproduct']); exit;
	$page = new PageAdmin();

	$page->setTpl("products-update", [
        'product' => $product->getData()
	]);

});

$app->post("/admin/products/{idproduct}", function($request, $response, $args) {
    
    User::verifyLogin(); 
	
	$product = new Product();

	$product->get((int) $args['idproduct']);
 
	$product->setData($args);

	$product->save();

	$product->setPhoto($_FILES['file']); 

	return $response->withRedirect('/admin/products'); 
});

$app->get("/admin/products/delete/{idproduct}", function($request, $response, $args) {
    
    User::verifyLogin(); 
	
	$product = new Product();
    
    $product->get((int) $args['idproduct']);
	
	$product->delete();

	return $response->withRedirect('/admin/products'); 
});


?>