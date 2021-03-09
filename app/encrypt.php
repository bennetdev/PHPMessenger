<?php
session_start();
require_once "classes/Database.php";
if(isset($_SESSION["user_id"]) && isset($_POST["data"])){
    $db = new Database("localhost", "messenger", "root", "");
    // if you want to encrypt with the public key of someone else
    if(isset($_POST["chat_id"])){
        // encrypt with public-key of partner
        $partner = $db->get_user_by_chat_id($_POST["chat_id"]);
        $public = sodium_hex2bin($partner["public"]);
        echo sodium_bin2hex(sodium_crypto_box_seal($_POST["data"], $public));
    }
    else{
        // encrypt with own public key (for storing in database, so current user can read his own messages)
        echo sodium_bin2hex(sodium_crypto_box_seal($_POST["data"], sodium_crypto_box_publickey($_SESSION["keypair"])));
    }

}