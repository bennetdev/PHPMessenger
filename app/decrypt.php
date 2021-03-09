<?php
session_start();
require_once "classes/Database.php";
if(isset($_SESSION["keypair"]) && isset($_POST["data"])){
    // decrypt data with private-key
    echo sodium_crypto_box_seal_open(sodium_hex2bin($_POST["data"]), $_SESSION["keypair"]);
}