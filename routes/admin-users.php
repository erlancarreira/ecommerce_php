<?php 
use \ec\PageAdmin;
use \ec\Model\User;

$app->get("/admin/users/password/{iduser}", function($request, $response, $args) {
     
    User::verifyLogin();
    
    $user = new User();

    $user->get((int) $args['iduser']);

    $page = new PageAdmin();
    

    $page->setTpl("users-password", [
        "users"  => $user->getData(),
        "msg"    => User::getMsg()        
    ]); 
    
});

$app->post("/admin/users/password/{iduser}", function($request, $response, $args) {
     
    User::verifyLogin();
    
    $user = new User();

    $user->get((int) $args['iduser']);

    if (!isset($_POST['despassword']) || empty($_POST['despassword'])) {
    
        User::setMsg('Preencha a nova senha', 'alert-danger');
    
    } else if (!isset($_POST['despassword-confirm']) || empty($_POST['despassword-confirm'])) {
        
        User::setMsg('Preencha a confirmacao da nova senha', 'alert-danger');
    
    } else if ($_POST['despassword'] !== $_POST['despassword-confirm']) {
        
        User::setMsg('Confirme corretamente as senhas', 'alert-danger');
    
    } else {
        
        $user->setPassword(User::getPasswordHash($_POST['despassword']));

        User::setMsg('Senha alterada com sucesso', 'alert-success');
    }

    return $response->withRedirect('/admin/users/password/'.$args['iduser']);

});

$app->get("/admin/users", function() {
    
    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : '';

    $page = (isset($_GET['page'])) ? (int) $_GET['page'] : 1;

    $pagination = User::getPage($search, $page);

    $pages = [];

    for ($i=1; $i <= $pagination['pages']; $i++) { 
        array_push($pages, [
            'href' => '/admin/users?'.http_build_query([
                'page'   => $i,
                'search' => $search  
            ]),
            'text' => $i
        ]);
    }
    
    $page = new PageAdmin();
   

    $page->setTpl("users", array(
        "users"  => $pagination['data'],
        "search" => $search,
        "pages"  => $pages
    ));
    
});

$app->get("/admin/users/create", function() {
    
    User::verifyLogin();
    
    $page = new PageAdmin();
     
    $page->setTpl("users-create"); 

});

$app->get("/admin/users/{iduser}", function($request, $response, $args) {
    
    User::verifyLogin();
    
    $user = new User();

    $user->get((int)$args['iduser']);
    
    $page = new PageAdmin();

    $page->setTpl("users-update", array(
       "user" => $user->getData()
    ));

});

$app->post("/admin/users/create", function() {
    User::verifyLogin();

    $user = new User();

    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;
    
    $user->setData($_POST);

    $user->save();

    header("Location: /admin/users");
    exit;
});

$app->post("/admin/users/{iduser}", function($request, $response, $args) {
    User::verifyLogin();

    $user = new User();

    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;
    
    $user->get((int)$args['iduser']);

    $user->setData($_POST);

    $user->update();

    return $response->withRedirect('/admin/users');
});

$app->get("/admin/users/delete/{iduser}", function($request, $response, $args) {
    
    User::verifyLogin();

    $user = new User();

    $user->get((int)$args['iduser']);

    //$user->setData($_POST);

    $user->delete();

    return $response->withRedirect('/admin/users');
});

?>