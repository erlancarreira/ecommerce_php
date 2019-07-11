<?php 
use \ec\Page;
use \ec\Model\Product;
use \ec\Model\Category;
use \ec\Model\Cart;
use \ec\Model\Address;
use \ec\Model\User;
use \ec\Model\Order;
use \ec\Model\OrderStatus;

$app->get('/', function() {
    
	$products = Product::listAll();

	$page = new Page();

	$page->setTpl("index", [
       'products' => Product::checkList($products)
	]);

});


$app->get("/categories/{idcategory}", function($request, $response, $args) {
    
    $currentPage = (isset($_GET['page'])) ? (int) $_GET['page'] : 1; 

    $category = new Category();
    
    $category->get((int) $args['idcategory']);

    $pagination = $category->getProductsPage($currentPage);

    $pages = [];

    for ($i=1; $i <= $pagination['pages']; $i++) { 
        array_push($pages, [
           'currentPage' => $currentPage,
           'link' => '/categories/'.$category->getidcategory().'?page='.$i,
           'page' => $i           
        ]);
    }

    $page = new Page();

    $page->setTpl("category", [
        'category'    => $category->getData(),
        'products'    => $pagination['data'],
        
        'pages'       => $pages,

    ]);
});

$app->get("/products/{desurl}", function($request, $response, $args) {
    
    $product = new Product();

    $product->getFromURL($args['desurl']);

    $page = new Page();

    $page->setTpl("product-detail", [
        'product'    => $product->getData(),
        'categories' => $product->getCategories()
    ]);
});

$app->get("/cart", function($request, $response, $args) {

    $cart = Cart::getFromSession();

    $page = new Page();
    
    $page->setTpl("cart", [
       'cart'     => $cart->getData(),
       'products' => $cart->getProducts(),
       'error'    => Cart::getMsgError()
    ]);
});

$app->get("/cart/add/{idproduct}", function($request, $response, $args) {
    
    
    $product = new Product();

    $product->get((int) $args['idproduct']);

    $cart = Cart::getFromSession();

    $qtd = isset($_GET['qtd']) ? (int) $_GET['qtd'] : 1; 
    
    for ($i=0; $i < $qtd; $i++) { 
        
        $cart->addProduct($product);

    }
    
    return $response->withRedirect('/cart');

});

$app->get("/cart/minus/{idproduct}", function($request, $response, $args) {
    
    $product = new Product();

    $product->get((int) $args['idproduct']);

    $cart = Cart::getFromSession();

    $cart->removeProduct($product);
    
    return $response->withRedirect('/cart');

});

$app->get("/cart/remove/{idproduct}", function($request, $response, $args) {
    
    $product = new Product();

    $product->get((int) $args['idproduct']);

    $cart = Cart::getFromSession();

    $cart->removeProduct($product, true);
    
    return $response->withRedirect('/cart');

});

$app->post("/cart/freight", function($request, $response, $args) use ($app) {
    
    $cart = Cart::getFromSession();
   
    $cart->setFreight($_POST['zipcode']);  

    return $response->withRedirect('/cart');
});

$app->get("/checkout", function() {
    
    User::verifyLogin(false); 
    
    $address = new Address();

    $cart = Cart::getFromSession();

    if (isset($_GET['zipcode'])) {

        $_POST['zipcode'] = $cart->getdeszipcode();
    
    }

    if (isset($_GET['zipcode'])) {

        $address->loadFromCEP($_GET['zipcode']); 

        $cart->setdeszipcode($_GET['zipcode']);

        $cart->save();

        $cart->getCalculateTotal();
    }

    if (!$address->getdesaddress())    $address->setdesaddress('');
    if (!$address->getdescomplement()) $address->setdescomplement('');
    if (!$address->getdesnumber())     $address->setdesnumber('');
    if (!$address->getdesdistrict())   $address->setdesdistrict('');
    if (!$address->getdescity())       $address->setdescity('');
    if (!$address->getdesstate())      $address->setdesstate('');
    if (!$address->getdescountry())    $address->setdescountry('');
    if (!$address->getdeszipcode())    $address->setdeszipcode(''); 
    
    $page = new Page(); 
    
    //var_dump($address->getData()); exit;  
    $page->setTpl("checkout", [
        'cart'     => $cart->getData(),
        'address'  => $address->getData(),
        'products' => $cart->getProducts(),
        'msg'      => Address::getMsg()
    ]);
});

$app->post("/checkout", function($request, $response, $args) {
   
    User::verifyLogin(false); 
   

    if (!isset($_POST['zipcode']) || empty($_POST['zipcode'])) {
        Address::setMsg('Informe o cep', 'alert-danger');
      
    } else if (!isset($_POST['desaddress']) || empty($_POST['desaddress'])) {
        Address::setMsg('Informe o endereco', 'alert-danger');
    
    } else if (!isset($_POST['desnumber']) || empty($_POST['desnumber'])) {
        Address::setMsg('Informe o numero', 'alert-danger');

    } else if (!isset($_POST['desdistrict']) || empty($_POST['desdistrict'])) {
        Address::setMsg('Informe o bairro', 'alert-danger');

    } else if (!isset($_POST['descity']) || empty($_POST['descity'])) {
        Address::setMsg('Informe a cidade', 'alert-danger');

    } else if (!isset($_POST['desstate']) || empty($_POST['desstate'])) {
        Address::setMsg('Informe o estado', 'alert-danger');

    } else if (!isset($_POST['descountry']) || empty($_POST['descountry'])) {
        Address::setMsg('Informe o pais', 'alert-danger');

    } else if (!isset($_POST['payment-method']) || empty($_POST['payment-method'])) {
        Address::setMsg('Escolha um metodo de pagamento', 'alert-danger');    

    } else {
        
        $user = User::getFromSession();
        
        $address = new Address();
       
        $_POST['deszipcode'] = $_POST['zipcode'];
       
        $_POST['idperson']   = $user->getidperson(); 

        $address->setData($_POST);

        $address->save();

        $cart = Cart::getFromSession();

        $cart->getCalculateTotal();

        $order = new Order();

        $order->setData([
            'idcart'    => $cart->getidcart(),
            'idaddress' => $address->getidaddress(),
            'iduser'    => $user->getiduser(),
            'idstatus'  => OrderStatus::EM_ABERTO,
            'vltotal'   => $cart->getvltotal() 
        ]);

        $order->save();

        Cart::setMethod((int)$_POST['payment-method'], $order->getidorder());

        return $response->withRedirect(Cart::getMethod());
    }

    return $response->withRedirect('/checkout');

});

$app->get("/order/pagseguro/{idorder}", function($request, $response, $args) {
    
    User::verifyLogin(false);
    
    $order = new Order(); 

    $order->get((int) $args['idorder']);

    $cart = $order->getCart();
    
    $page = new Page([
        'header' => false,
        'footer' => false 
    ]);

    $page->setTpl("payment-pagseguro", [
        'order'    => $order->getData(),
        'cart'     => $cart->getData(),
        'products' => $cart->getProducts(),
        'phone'    => [
            'areacode' => substr($order->getnrphone(), 0, 2),
            'number' => substr($order->getnrphone(), 2, strlen($order->getnrphone()))

        ]
    ]);
});

$app->get("/order/paypal/{idorder}", function($request, $response, $args) {
    
    User::verifyLogin(false);
    
    $order = new Order(); 

    $order->get((int) $args['idorder']);

    $cart = $order->getCart();
    
    $page = new Page([
        'header' => false,
        'footer' => false 
    ]);

    $page->setTpl("payment-paypal", [
        'order'    => $order->getData(),
        'cart'     => $cart->getData(),
        'products' => $cart->getProducts()
    ]);
});

$app->get("/login", function() {
    
    $page = new Page(); 

    $page->setTpl("login", [
        'error' => User::getError(),
        'errorRegister' => User::getErrorRegister(),
        'registerValues' => (isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name' => '', 'email' => '', 'phone' => '']  
    ]);
});

$app->post("/login", function($request, $response, $args) {
    
    try{ 
        
        User::login($_POST['login'], $_POST['password']);
    
    } catch(Exception $e) {
        
        User::setError($e->getMessage());   
    }

    return $response->withRedirect('/checkout');
});

$app->get("/logout", function($request, $response, $args) {
    User::logout(); 

    return $response->withRedirect('/login');
});

$app->post("/register", function($request, $response, $args) {
    
    $error = 0;

    $_SESSION['registerValues'] = $_POST;
    
    
    if (!isset($_POST['name']) || empty($_POST['name'])) {
    
        User::setErrorRegister("Preencha o seu nome");
        $error = 1;
    
    } else if (!isset($_POST['email']) || empty($_POST['email'])) {   

        User::setErrorRegister("Preencha o seu email");
        $error = 1;
       
    } else if (!isset($_POST['email']) || empty($_POST['email'])) {       

        User::setErrorRegister("Preencha o seu email");
        $error = 1;
         
    } else if (!isset($_POST['password']) || empty($_POST['password'])) {     
    
        User::setErrorRegister("Preencha o campo senha");
        $error = 1;
       
    } else if (User::checkLoginExist($_POST['email']) === true) {    
    
        User::setErrorRegister("Email existente");
        $error = 1;
    
    } 

    if ($error === 1) {

        return $response->withRedirect('/login');
    }
    
    $user = new User();

    $user->setData([
        'inadmin'     => 0,
        'deslogin'    => $_POST['email'],
        'desperson'   => $_POST['name'],
        'desemail'    => $_POST['email'],
        'despassword' => $_POST['password'],
        'nrphone'     => (empty($_POST['phone'])) ? NULL : $_POST['phone'],   
    ]);

    $user->save();

    User::login($_POST['email'], $_POST['password']);

    return $response->withRedirect('/checkout');
});

$app->get("/forgot", function() {
    $page = new Page();

    $page->setTpl("forgot");
});

$app->post("/forgot", function($request, $response, $args)  {
    
    $user = User::getForgot($_POST['email'], false);

    return $response->withRedirect('/forgot/sent');
});

$app->get("/forgot/sent", function (){
    $page = new Page();
    $page->setTpl("forgot-sent");
});

$app->get("/forgot/reset", function ($request, $response, $args) {
    
    $user = User::validForgotDecrypt($_GET['code']);

    $page = new Page();

    $page->setTpl("forgot-reset", array( 
        "name" => $user['desperson'],
        "code" => $_GET['code']
    ));
});

$app->post("/forgot/reset", function ($request, $response, $args){
    
    $forgot = User::validForgotDecrypt($_POST['code']);

    User::setForgotUsed($forgot['idrecovery']);

    $user = new User();

    $user->get((int) $forgot['iduser']);

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT, [
        "cost" => 12
    ]);

    $user->setPassword($password);

    $page = new Page();

    $page->setTpl("forgot-reset-success");

});

$app->get('/profile', function($request, $response, $args) {
    
    User::verifyLogin(false);
    
    $user = User::getFromSession();

    //var_dump($user);

    $page = new Page();   
    
    $page->setTpl('profile', [
        'user'         => $user->getData(),
        'profileMsg'   => User::getMsg()
    ]);

});

$app->post('/profile', function($request, $response, $args) {
    
    User::verifyLogin(false);

    $_SESSION['success'] = 1;

    if (!isset($_POST['desperson']) || empty($_POST['desperson'])) {
        $_SESSION['success'] = 0;
        User::setMsg('Preencha o seu nome');
        
    } else if (!isset($_POST['desemail']) || empty($_POST['desemail'])) {
        $_SESSION['success'] = 0;
        User::setMsg('Preencha o seu email');
       
    } else if ($_POST['desemail'] !== User::getFromSession()->getdesmail()) {

        if (User::checkLoginExist($_POST['desemail'])) {
            $_SESSION['success'] = 0;
            User::setMsg("Este endereco de email ja esta cadastrado");
            
        }
    }
    
    if (!$_SESSION['success']) {

        return $response->withRedirect('/profile');
    
    } else {

        $user = User::getFromSession();
        
        //var_dump($user->getdespassword()); exit;
        $_POST['inadmin']     = $user->getinadmin();
        $_POST['despassword'] = $user->getdespassword();
        $_POST['deslogin']    = $_POST['desemail'];

        $user->setData($_POST);
        
       // var_dump($user->getData()); exit;
        $user->update();     

        User::setMsg("Dados alterados com sucesso!");
    }

    return $response->withRedirect('/profile');
});

$app->get('/order/{idorder}', function($request, $response, $args) { 
    User::verifyLogin(false);
    
    $order = new Order();

    $order->get((int) $args['idorder']);

    $page = new Page();

    $page->setTpl("payment", [
       'order' => $order->getData()     
    ]);  
});

$app->get("/boleto/{idorder}", function($request, $response, $args) { 
   
    User::verifyLogin(false);
    
    $order = new Order();

    $order->get((int) $args['idorder']);

    // DADOS DO BOLETO PARA O SEU CLIENTE
    $dias_de_prazo_para_pagamento = 10;
    $taxa_boleto = 5.00;
    $data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
    $valor_cobrado = formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
    $valor_cobrado = $order->getvltotal();
    $valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

    $dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
    $dadosboleto["numero_documento"] = $order->getidorder();  // Num do pedido ou nosso numero
    $dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
    $dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
    $dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
    $dadosboleto["valor_boleto"] = $valor_boleto;   // Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

    // DADOS DO SEU CLIENTE
    $dadosboleto["sacado"]    = $order->getdesperson();
    $dadosboleto["endereco1"] = $order->getdesaddress() .' '.$order->getdesdistrict();
    $dadosboleto["endereco2"] = $order->getdescity() ." - ". $order->getdesstate() ." - ".  "CEP: ".$order->getdeszipcode();

    // INFORMACOES PARA O CLIENTE
    $dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
    $dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
    $dadosboleto["demonstrativo3"] = "";
    $dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
    $dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
    $dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
    $dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

    // DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
    $dadosboleto["quantidade"] = "";
    $dadosboleto["valor_unitario"] = "";
    $dadosboleto["aceite"] = "";        
    $dadosboleto["especie"] = "R$";
    $dadosboleto["especie_doc"] = "";


    // ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


    // DADOS DA SUA CONTA - ITAÚ
    $dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
    $dadosboleto["conta"] = "48781";    // Num da conta, sem digito
    $dadosboleto["conta_dv"] = "2";     // Digito do Num da conta

    // DADOS PERSONALIZADOS - ITAÚ
    $dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

    // SEUS DADOS
    $dadosboleto["identificacao"] = "Hcode Treinamentos";
    $dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
    $dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
    $dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
    $dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";
    
    // NÃO ALTERAR!
    $path = $_SERVER['DOCUMENT_ROOT']. DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR ."boletophp". DIRECTORY_SEPARATOR. "include". DIRECTORY_SEPARATOR;
    require_once($path."funcoes_itau.php");
    require_once($path."layout_itau.php");

    

});    

$app->get("/profile/orders", function($request, $response, $args) { 
    User::verifyLogin(false);

    $user = User::getFromSession();

    $page = new Page();

    $page->setTpl("profile-orders", [
        'orders' => $user->getOrders()
    ]);
});

$app->get("/profile/orders/{idorder}", function($request, $response, $args) { 
    
    User::verifyLogin(false);
    
    $order = new Order(); 
     
    $order->get((int) $args['idorder']);  

    $cart = new Cart();

    $cart->get((int) $order->getidcart());

    $cart->getCalculateTotal();
    
    $page = new Page();

    $page->setTpl("profile-orders-detail", [
        'order'   => $order->getData(),
        'cart'     => $cart->getData(),
        'products' => $cart->getProducts()
    ]);
});

$app->get("/profile/change-password", function($request, $response, $args) { 
    
    User::verifyLogin(false);

    $page = new Page();

    $page->setTpl("profile-change-password", [
        'msg' => User::getMsg(),
       
    ]);

});

$app->post("/profile/change-password", function($request, $response, $args) { 
    
    User::verifyLogin(false);

    $user = User::getFromSession();
    
    $_SESSION['success'] = 1;

    if (!isset($_POST['current_pass']) || empty($_POST['current_pass'])) {
    
        User::setMsg('Digite a senha atual');
        $_SESSION['success'] = 0; 
    
    } else if (!isset($_POST['new_pass']) || empty($_POST['new_pass'])) {
        
        User::setMsg('Digite a nova senha');
        $_SESSION['success'] = 0; 
    
    } else if (!isset($_POST['new_pass_confirm']) || empty($_POST['new_pass_confirm'])) {
        
        User::setMsg('Confirme a nova senha');
        $_SESSION['success'] = 0; 
    
    } else if ($_POST['current_pass'] === $_POST['new_pass']) {
        
        User::setMsg('A senha deve ser diferente');
        $_SESSION['success'] = 0; 
    
    } else if ($_POST['new_pass'] !== $_POST['new_pass_confirm']) {
        
        User::setMsg('A nova senha deve ser igual a confirmacao.');
        $_SESSION['success'] = 0; 
       
    } else if (!password_verify($_POST['current_pass'], $user->getdespassword())) {
        
        User::setMsg('A senha esta invalida');
        $_SESSION['success'] = 0; 
    
    }

    if ($_SESSION['success'] === 1) {
    
        $user->setdespassword($_POST['new_pass']);

        $user->update();

        User::setMsg("Senha alterada com sucesso.");
    
    }

    return $response->withRedirect('/profile/change-password');
});
?>