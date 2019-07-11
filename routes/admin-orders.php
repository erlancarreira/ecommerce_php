<?php  

use \ec\PageAdmin;
use \ec\Model\User;
use \ec\Model\Order;
use \ec\Model\OrderStatus;

$app->get("/admin/orders/status/{idorder}", function($request, $response, $args) {
    
    User::verifyLogin();

    $order = new Order();

    $order->get((int) $args['idorder']);

    $page = new PageAdmin();
    
   // var_dump(OrderStatus::listAll()); exit;
    $page->setTpl("order-status", [
        'order'  => $order->getData(),
        'status' => OrderStatus::listAll(),
        'msg'    => Order::getMsg()
        
    ]);
});	

$app->post("/admin/orders/status/{idorder}", function($request, $response, $args) {
    
    User::verifyLogin();
    
    $_SESSION['success'] = 1;

    if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0) {
    	
    	Order::setMsg("Informe o status atual.");
    	$_SESSION['success'] = 0;
    
    }

    if ($_SESSION['success'] === 1) {

	    $order = new Order();

	    $order->get((int) $args['idorder']);

	    $order->setidstatus((int) $_POST['idstatus']);

	    $order->save();

	    $_SESSION['success'] = 1;
	    Order::setMsg("Status atualizado.");
    }

    return $response->withRedirect('/admin/orders/status/'.$args['idorder']);
});	

$app->get("/admin/orders/delete/{idorder}", function($request, $response, $args) {
    
    User::verifyLogin();

    $order = new Order();

    $order->get((int) $args['idorder']);
    
    //var_dump($order->getData()); exit; 
    $order->delete();

    return $response->withRedirect('/admin/orders');

});	

$app->get("/admin/orders/{idorder}", function($request, $response, $args) {
    
    User::verifyLogin();

    $order = new Order();

    $order->get((int) $args['idorder']);

    $cart = $order->getCart();
    
    $page = new PageAdmin();

    $page->setTpl("order", [
        "order"    => $order->getData(),
        "cart"     => $cart->getData(),
        "products" => $cart->getProducts()
    ]);

});	

$app->get("/admin/orders", function($request, $response, $args) {
   
    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : '';

    $page = (isset($_GET['page'])) ? (int) $_GET['page'] : 1;

    $pagination = Order::getPage($search, $page,1);

    $pages = [];

    for ($i=1; $i <= $pagination['pages']; $i++) { 
        array_push($pages, [
            'href' => '/admin/orders?'.http_build_query([
                'page'   => $i,
                'search' => $search  
            ]),
            'text'   => $i
        ]);
    }
    
    //var_dump($pages); exit; 
    $page = new PageAdmin();

    $page->setTpl("orders", [
        "orders" => $pagination['data'],
        "search" => $search,
        "pages"  => $pages
    ]);
});




?>