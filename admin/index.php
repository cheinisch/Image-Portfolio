<?php
session_start();

$_SESSION['user_id'] = "1";

if($_SESSION['user_id'] == "2")
{
	include 'admin.php';
}else{
	include 'login.php';
}

?>