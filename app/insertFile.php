<?php
session_start();
require_once "classes/Database.php";


if(isset($_SESSION["user_id"]) && isset($_FILES["uploaded_file"])){
    $db = new Database();
    $user_id = $_SESSION["user_id"];
    // read file and silence errors
    $handle=@fopen($_FILES["uploaded_file"]["tmp_name"], 'rb');

    // get metadata and content in base64
    $type = $_FILES["uploaded_file"]["type"];
    $name = $_FILES["uploaded_file"]["name"];
    $file_content = file_get_contents($_FILES["uploaded_file"]["tmp_name"]);
    $base64_content = base64_encode($file_content);

    // get public key of chat partner
    $public = sodium_hex2bin($db->get_user_by_chat_id($_POST["chat_id"])["public"]);

    // insert file and return id in database
    echo $db->insert_file(sodium_crypto_box_seal($base64_content, $public), sodium_crypto_box_seal($base64_content, sodium_crypto_box_publickey($_SESSION["keypair"])), $type, $name, $user_id);
}
