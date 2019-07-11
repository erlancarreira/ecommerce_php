<?php 
use \ec\PageAdmin;
use \ec\Model\User;

$app->get('/admin', function() {
    
	User::verifyLogin();
	$page = new PageAdmin();
     
    
	$page->setTpl("index");

});

$app->get('/admin/login', function() {
    
	//echo 'ENTREI';
	$page = new PageAdmin([
        "header"=>false,
        "footer"=>false,

	]);
     
    
	$page->setTpl("login");

});

$app->post('/admin/login', function() {
    
	//var_dump($_POST); exit;
	User::login($_POST['login'], $_POST['password']);
    
	header("Location: /admin");
	exit;

});

$app->get('/admin/logout', function() {
	User::logout();

	header("Location: /admin/login");
	exit;
});


$app->get("/admin/forgot", function() {
    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("forgot");
});

$app->post("/admin/forgot", function($request, $response, $args)  {
    
    $user = User::getForgot($_POST['email']);

    return $response->withRedirect('/admin/forgot/sent');
});

$app->get("/admin/forgot/sent", function (){
    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("forgot-sent");
});

$app->get("/admin/forgot/reset", function ($request, $response, $args) {
    
    $user = User::validForgotDecrypt($_GET['code']);

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("forgot-reset", array( 
        "name" => $user['desperson'],
        "code" => $_GET['code']
    ));
});

$app->post("/admin/forgot/reset", function ($request, $response, $args){
    
    $forgot = User::validForgotDecrypt($_POST['code']);

    User::setForgotUsed($forgot['idrecovery']);

    $user = new User();

    $user->get((int) $forgot['iduser']);

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT, [
        "cost" => 12
    ]);

    $user->setPassword($password);

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("forgot-reset-success");

});

?>