<?php
$varchivopj = "prefijos.txt";
$vprefijos  = '';
if(isset($_POST['prefijos']))
{
	$vprefijos = $_POST['prefijos'];
}

if(file_exists($varchivopj))
{
	$fpj = fopen($varchivopj, "w");
	fwrite($fpj, $vprefijos);
	fclose($fpj);
}
?>