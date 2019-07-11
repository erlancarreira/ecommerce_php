<?php 

namespace ec\Model;

use \ec\DB\Sql;

use \ec\Model;


class Address extends Model
{
	const MSG = 'AddressMsg';

	public static function getCEP($nrcep) 
	{
        $nrcep = preg_replace('/#[^-9]#/', '', $nrcep); 

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "http://viacep.com.br/ws/$nrcep/json/");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $data = json_decode(curl_exec($ch), true);

        curl_close($ch);

        return $data;
	} 

	public function loadFromCEP($nrcep) 
	{
		$data = Address::getCEP($nrcep);

		if (isset($data['logradouro']) && !empty($data['logradouro'])) {
            
            $this->setdesaddress($data['logradouro']);
            $this->setdescomplement($data['complemento']);
            $this->setdesdistrict($data['bairro']);
            $this->setdescity($data['localidade']);
            $this->setdesstate($data['uf']);
            $this->setdescountry('Brasil');
            $this->setdeszipcode($nrcep);
		}
	} 

	public function save() 
	{
        $sql = new Sql();
        
        $results = $sql->select("CALL sp_addresses_save(:idaddress, :idperson, :desaddress, :desnumber, :descomplement, :descity, :desstate, :descountry, :deszipcode, :desdistrict)", [
            
            ':idaddress'     => $this->getidaddress(),
            ':idperson'      => $this->getidperson(),
            ':desaddress'    => $this->getdesaddress(),
            ':desnumber'     => $this->getdesnumber(),
            ':descomplement' => $this->getdescomplement(),
            ':descity'       => $this->getdescity(),
            ':desstate'      => $this->getdesstate(),
            ':descountry'    => $this->getdescountry(),
            ':deszipcode'    => $this->getdeszipcode(),
            ':desdistrict'   => $this->getdesdistrict()
        ]);
        
        //var_dump($results); exit;
        if (count($results) > 0){
            $this->setData($results[0]);
        }
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
}