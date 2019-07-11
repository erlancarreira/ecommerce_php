<?php 
use \ec\Model\User;
use \ec\Model\Cart;

function formatPrice(float $vlprice = null) 
{
	
	return (count((array) $vlprice) > 0) ? number_format($vlprice, 2, ",", ".") : 0;
}

function formatDate($date) 
{
	return date('d/m/Y', strtotime($date));
}

function checkLogin($inAdmin = true) 
{
	return User::checkLogin($inAdmin);
}

function getUserName() 
{
	$user = User::getFromSession();
    //var_dump($user); exit;
	return $user->getdesperson();
}

function getCartNrQtd() 
{
	$cart   = Cart::getFromSession();

	$totals = $cart->getProductsTotals();

	return $totals['nrqtd']; 
}

function getCartVlSubTotal() 
{

	$cart = Cart::getFromSession();

	$totals = $cart->getProductsTotals();

	return formatPrice($totals['vlprice']); 
}




?>