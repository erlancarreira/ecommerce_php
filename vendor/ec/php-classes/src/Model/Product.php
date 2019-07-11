<?php 

namespace ec\Model;

use \ec\DB\Sql;
use \ec\Model;
use \ec\Mailer;

class Product extends Model
{
 	
 	private $urlResource;	
 	
 	public static function listAll() 
	{
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");  
	}

	public static function checkList($list) 
	{
		foreach ($list as &$row) {
			
			$p = new Product();
			$p->setData($row);
			$row = $p->getData();
		}
      


		return $list;
	} 

	public function save() 
	{
		$sql = new Sql();

		$results = $sql->select("CALL 
			sp_products_save(
				:idproduct, 
				:desproduct, 
				:vlprice, 
				:vlwidth, 
				:vlheight,
				:vllength, 
				:vlweight, 
				:desurl,

			    :phid,
			    :phtitle,
			    :phname,
			    :phorder)", 
			
			array(
	            ":idproduct"   => $this->getidproduct(),
	            ":desproduct"  => $this->getdesproduct(), 
	            ":vlprice"     => $this->getvlprice(),
	            ":vlwidth"     => $this->getvlwidth(),
	            ":vlheight"    => $this->getvlheight(),   
	            ":vllength"    => $this->getvllength(),
	            ":vlweight"    => $this->getvlweight(),
	            ":desurl"      => $this->getdesurl(),
	            
	            ":phid"        => $this->getphid(),   
			    ":phtitle"     => $this->getphtitle(),   
			    ":phname"      => $this->getphname(),  
			    ":phorder"     => $this->getphorder()                        
		    )
		);

		$this->setData($results[0]);
	}

	public function get($idproduct) 
	{
		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", [
            ':idproduct' => $idproduct  
		]);

		$this->setData($results[0]);

	}

	public function delete() 
	{
		$sql = new Sql();

		$sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", [
            ':idproduct' => $this->getidproduct()
		]);

	}

	public function setUrlResource($urlResource) 
	{
        $this->urlResource = $urlResource;
	}

	public function getUrlResource() 
	{
        return $this->urlResource; 
	}

	public function getUrlImage() {

 		return $_SERVER['DOCUMENT_ROOT']. DIRECTORY_SEPARATOR . 
	    		'resources'. DIRECTORY_SEPARATOR . 
	    		'site' . DIRECTORY_SEPARATOR . 
	    		'img' .	DIRECTORY_SEPARATOR . 
	    		'products' . DIRECTORY_SEPARATOR .
	    		$this->getidproduct() . '.jpg';
 	}
    
    //quando for feito o envio de post
	public function checkImg($file) {
        
       // var_dump($file['name']);
        $extension = explode('.', $file['name']);
        $extension = end($extension);

        switch ($extension) {
        	case 'jpg':
        	case 'jpeg':
        		$image = imagecreatefromjpeg($file['tmp_name']);
        		break;
            case 'gif':
                $image = imagecreatefromgif($file['tmp_name']); 
            break;

            case 'png':
                $image = imagecreatefrompng($file['tmp_name']); 
        	break;
        }

        
          
        $this->setUrlResource($this->getUrlImage());  

        imagejpeg($image, $this->getUrlResource());

        imagedestroy($image); 

        $this->checkPhoto();

	}

	public function checkPhoto() 
	{
        
        //var_dump(); exit;
 	    
 	    if (file_exists($this->getUrlImage())) 
	    {
           
            $this->setUrlResource("/resources/site/img/products/" . $this->getidproduct() . ".jpg");  
	    
	    } else {
             
	    	$this->setUrlResource("/resources/site/img/product.jpg");
	    }
	    
        return $this->setdesphoto($this->getUrlResource()); 
	} 

	

	public function setPhoto($file) 
	{   
        
		$this->checkImg($file); 

		$this->setdesphoto($this->getUrlResource());		
	}

	public function getData() 
	{
		$this->checkPhoto();

		$values = parent::getData();

		return $values;
	}

	public function getFromURL($desurl) 
	{
		$sql = new Sql();

		$rows = $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1", [
            ':desurl' => $desurl   
		]);

		$this->setData($rows[0]);
	}

	public function getCategories() 
	{
		$sql = new Sql();

		return $sql->select("
			    
			    SELECT * FROM 
			        tb_categories a 
			    INNER JOIN 
			        tb_productscategories b 
			        ON a.idcategory = b.idcategory 
			    WHERE b.idproduct = :idproduct", 
            [
               ':idproduct' => $this->getidproduct()
            ]
		);
	}

	public static function getPage($search, $page = 1, $itemsPerPage = 3) 
	{
		$start = ($page - 1) * $itemsPerPage;
        
        $query = (!empty($search)) ? ' WHERE desproduct LIKE :search ' : '';

		$sql   = new Sql();

		$results = $sql->select(" 
            SELECT sql_calc_found_rows * 
			FROM tb_products 
			$query
			ORDER BY desproduct
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