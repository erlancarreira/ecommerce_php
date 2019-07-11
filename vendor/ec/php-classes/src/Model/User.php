<?php 

namespace ec\Model;

use \ec\DB\Sql;

use \ec\Model;

use \ec\Mailer;
/**
 * 
 */
class User extends Model
{
	const SESSION        = "User";
	const SECRET         = "HcodePhp7_Secret";
	const ERROR          = "UserError";
	const ERROR_REGISTER = 'UserErrorRegister';
	const MSG            = 'UserMsg';

	public static function getFromSession() 
	{
		$user = new User();

		if (isset($_SESSION[User::SESSION]) && (int) $_SESSION[User::SESSION]['iduser'] > 0) {
			

			$user->setData($_SESSION[User::SESSION]);
		
		} 

		return $user;
	}

	public static function checkLogin($inAdmin = true) 
	{
		//var_dump([User::SESSION]); exit;
		if(
            !isset($_SESSION[User::SESSION]) //Se nao estiver setado
			|| 
			!$_SESSION[User::SESSION] // Se for falsa
			|| 
			!(int)$_SESSION[User::SESSION]["iduser"] > 0 //Se o idusuario nao for maior que 0
			
		) {
            //Nao esta logado
			return false;
		
		} else {
            
           

            if($inAdmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) {
                        
            	return true;
            
            } else if($inAdmin === false) {               

                return true; 
            
            } else {

            	return false;
            }

		}
	}

	public static function login($login, $password) 
	{
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b ON a.idperson = b.idperson WHERE a.deslogin = :LOGIN", array(
            ":LOGIN" => $login
        ));

        if (count($results) === 0) 
        {
        	throw new \Exception("Usuario inexistente ou senha invalida.");
        }

        $data = $results[0];

        if (password_verify($password, $data['despassword']) === true) 
        {
        	$user = new User();

        	$user->setData($data);

            $_SESSION[User::SESSION] = $user->getData();
        	
        	return $user;

        } else {
        	throw new \Exception("Usuario inexistente ou senha invalida.");
        }
	} 

	public static function verifyLogin($inAdmin = true) 
	{
		if (!User::checkLogin($inAdmin)) {
            
            if ($inAdmin) {
	            
	            header("Location: /admin/login");
	            
			} else {

				header("Location: /login");
            
		    }
            exit;
        }    
	}

	public static function logout() 
	{
		$_SESSION[User::SESSION] = NULL;
	}

	public static function listAll() 
	{
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");  
	}

	public function save() 
	{
		$sql = new Sql();

		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :desinadmin)", 
			array(
	            ":desperson"   => $this->getdesperson(),
	            ":deslogin"    => $this->getdeslogin(),
	            ":despassword" => User::getPasswordHash($this->getdespassword()),
	            ":desemail"    => $this->getdesemail(),
	            ":nrphone"     => $this->getnrphone(),
	            ":desinadmin"  => $this->getinadmin()                  
		    )
		);

		//var_dump($this->getData()); exit;

		$this->setData($results[0]);
	}

	public function get(int $iduser) 
	{
		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING (idperson) WHERE a.iduser = :iduser", [
           ":iduser"=>$iduser
		]);

		$this->setData($results[0]);
	}

	public function update() 
	{
		$sql = new Sql();

		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", 
			array(
	            "iduser"      => $this->getiduser(),
	            "desperson"   => $this->getdesperson(),
	            "deslogin"    => $this->getdeslogin(),
	            "despassword" => User::getPasswordHash($this->getdespassword()),
	            "desemail"    => $this->getdesemail(),
	            "nrphone"     => $this->getnrphone(),
	            "inadmin"     => $this->getinadmin()                  
		    )
		);

		$this->setData($results[0]);
		$_SESSION[User::SESSION] = $this->getData();
	}

	public function delete() 
	{
		$sql = new Sql();

		$sql->query("CALL sp_users_delete(:iduser)", 
			array(
	            "iduser"=> $this->getiduser()	                        
		    )
		);
	}

	public static function getForgot($email, $inadmin = true) 
	{
		$sql = new Sql();

		$results = $sql->select("
            SELECT *
            FROM tb_persons a 
            INNER JOIN tb_users b USING(idperson)
            WHERE a.desemail = :email
			", array(
                ":email" => $email  
			));

		    if (count($results) === 0) 
		    {
		    	throw new Exception("Nao foi possivel recuperar a senha");
		    	
		    } else {

		    	$data = $results[0];
                
                $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                    ":iduser" =>  $data["iduser"],
                    ":desip"   => $_SERVER["REMOTE_ADDR"]
                ));  

                if (count($results2) === 0) {
                    
                    throw new \Exception("Nao foi possivel recuperar a senha");
                            
                } else {
                    
                    $dataRecovery = $results2[0];

                    $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
                    $code = openssl_encrypt($dataRecovery['idrecovery'], 'aes-256-cbc', User::SECRET, 0, $iv);
                    $result = base64_encode($iv.$code);

                    
		            $link = ($inadmin === true) ? "https://ecommerce.test/admin/forgot/reset?code=$result" : "https://ecommerce.test/forgot/reset?code=$result";
		            
		           
                    $mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir Senha", "forgot", array(
                        "name" => $data['desperson'],
                        "link" => $link
                    ));

                    $mailer->send();

                    return $link;
                }

		    }
	}

	public static function validForgotDecrypt($result)
    {
	    $result = base64_decode($result);
	    $code = mb_substr($result, openssl_cipher_iv_length('aes-256-cbc'), null, '8bit');
	    $iv = mb_substr($result, 0, openssl_cipher_iv_length('aes-256-cbc'), '8bit');;
	    $idrecovery = openssl_decrypt($code, 'aes-256-cbc', User::SECRET, 0, $iv);
	    $sql = new Sql();
	    $results = $sql->select("
	        SELECT *
	        FROM tb_userspasswordsrecoveries a
	        INNER JOIN tb_users b USING(iduser)
	        INNER JOIN tb_persons c USING(idperson)
	        WHERE
	        a.idrecovery = :idrecovery
	        AND
	        a.dtrecovery IS NULL
	        AND
	        DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW()
	        ",  array(
	            ":idrecovery"=>$idrecovery
	        ));
	    if (count($results) === 0)
	    {
	        throw new \Exception("Não foi possível recuperar a senha.");
	    }
	    else
	    {
	        
	        return $results[0];
	    }
    }

    public static function setForgotUsed($idrecovery) 
    {
    	$sql = new Sql();

    	$sql->query("UPDATE 
    		            tb_userspasswordsrecoveries 
    		        SET 
    		            dtrecovery = NOW() 
    		        WHERE 
    		            idrecovery = :idrecovery", 
    		                array(
                                ":idrecovery" => $idrecovery   
    	));
    }

    public function setPassword($password) 
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
            ":password" => $password,
            ":iduser"   => $this->getiduser()
        ));
    }

    public static function setError($msg) 
    {
    	$_SESSION[User::ERROR] = $msg;
    }

    public static function getError() 
    {
        $msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : "";
        
        User::clearError();

        return $msg;
    }

    public static function clearError() 
    {
        $_SESSION[User::ERROR] = NULL;	
    } 

    public static function setMsg($msg, $class = 'alert-success') 
    {
    	$_SESSION[User::MSG] = ['text' => $msg, 'class' => $class];  
    }

    public static function getMsg() 
    {
        $msg = (isset($_SESSION[User::MSG]) && !empty($_SESSION[User::MSG])) ? $_SESSION[User::MSG] : "";

        User::clearMsg();

        return $msg;
    }

    public static function clearMsg() 
    {
        $_SESSION[User::MSG] = NULL;
    } 

    public static function checkLoginExist($login) 
    {
    	$sql = new Sql();
    	$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = : deslogin", [
            ':deslogin' => $login
    	]);

    	return (count($results) > 0);
    }

    public static function getPasswordHash($password) 
    {
    	return password_hash($password, PASSWORD_DEFAULT, [
            'cost' => 12  
    	]);
    } 

    public static function setErrorRegister($msg) {
        
        $_SESSION[User::ERROR_REGISTER] = $msg;  
    
    }

    public static function getErrorRegister() {
        
        $msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';  

        User::clearErrorRegister();

        return $msg;
    
    }

    public static function clearErrorRegister() {

    	$_SESSION[User::ERROR_REGISTER] = NULL;
    
    }

    public function getOrders() 
	{
        $sql = new Sql();

        $results = $sql->select("
        	SELECT * 
        	FROM tb_orders a 
        	INNER JOIN tb_ordersstatus b USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users d ON d.iduser = a.iduser
            INNER JOIN tb_addresses e USING(idaddress)
            INNER JOIN tb_persons f ON f.idperson = d.idperson
            WHERE a.iduser = :iduser
        ", [
            ':iduser' => $this->getiduser() 
        ]);

        return $results;
	}


	public static function getPage($search, $page = 1, $itemsPerPage = 3) 
	{
		$start      = ($page - 1) * $itemsPerPage;
        
        $query     = (!empty($search)) ? ' WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search ' : '';

		$sql = new Sql();

		$results = $sql->select(" 
            SELECT sql_calc_found_rows * 
			FROM tb_users a 
			INNER JOIN tb_persons b USING(idperson) 
			$query
			ORDER BY b.desperson
            LIMIT $start, $itemsPerPage 
		", [ ':search' => '%'.$search.'%' ]);

		$resultTotal = $sql->select("SELECT found_rows() as nrtotal");

		return [
            'data'  => $results,
            'total' => (int) $resultTotal[0]["nrtotal"],
            'pages' => ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
	}


}