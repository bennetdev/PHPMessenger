<?php
if(isset($_POST["username"]) && $_POST["password"]){
    require_once 'classes/Database.php';
    require_once 'utils.php';
    session_start();
    $username = $_POST["username"];
    $password = $_POST["password"];

    $db = new Database("localhost", "messenger", "root", "");

    $user = $db->get_user_by_username(htmlspecialchars(trim($username)));
    // if user exists
    if(!empty($user)){
        // verify password
        if(password_verify($password, $user["password"])){
            // save user_id in session
            $_SESSION['user_id'] = $user['id'];

            // hash password for key decryption
            $password_hash = hash_password_for_encryption($password, $user["encryption_salt"]);
            // decrypt key stored in database with hashed password
            $encryption_key = decrypt_ssl($user["encryption_key"], $password_hash);
            // decrypt secret_key stored in database with decrypted key
            $secret_key = decrypt_ssl($user["secret"], $encryption_key);

            // save sodium keypair in session for asymmetric encryption
            $_SESSION["keypair"] = sodium_crypto_box_keypair_from_secretkey_and_publickey(sodium_hex2bin($secret_key), sodium_hex2bin($user["public"]));

        }
    }
    header("Location: ../index.php");

}