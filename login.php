<?php

require_once "/home/main/ddos_skripsi/ddos_detection/logger.php";

$username = $_POST['username'] ?? '';

$password = $_POST['password'] ?? '';



$valid_user = "admin";

$valid_pass = "12345";



if($username == $valid_user && $password == $valid_pass){

    header("Location: dashboard.php");

}else{

    echo "Login gagal. Username atau password salah.";

}



?>