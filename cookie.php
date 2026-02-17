<?php
$vdesde = '';
if(isset($_POST['desde']))
{
	$vdesde = $_POST['desde'];
}

if(isset( $_COOKIE['desde']))
{

}
else
{
	setcookie('desde', $vdesde, time() + 60);
}
?>