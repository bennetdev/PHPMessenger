<?php
require_once 'classes/Database.php';
require_once 'utils.php';

$response = "Registration failed. Try again";
if(isset($_POST['username']) && isset($_POST["password"]) && isset($_POST["password2"])) {
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];
    $password2 = $_POST['password2'];

    if(strlen($password) !== 0 || $password === $password2 || (strpos($password, " ") == false || strpos($username, " ") == false)) {
        $db = new Database();
        $users = $db->get_user_by_username($username);

        // if username is not already taken
        if(empty($users)){
            // create keypair for user
            $keypair = sodium_crypto_box_keypair();

            // create hash of password to encrypt key
            $salt = bin2hex(openssl_random_pseudo_bytes(6));
            $password_hash = hash_password_for_encryption($password, $salt);

            // create encryption key
            $encryption_key = bin2hex(openssl_random_pseudo_bytes(16));
            $public_key = sodium_bin2hex(sodium_crypto_box_publickey($keypair));
            $secret_key = encrypt_ssl(sodium_bin2hex(sodium_crypto_box_secretkey($keypair)), $encryption_key);
            // encrypt encryption key with hashed password
            $encryption_key = encrypt_ssl($encryption_key, $password_hash);

            // store user with encryption details in database
            $db->insert_user($username, $password, $encryption_key, $public_key, $secret_key, $salt);
            $response = "Success! You can now login with your new account.";
        }
    }
}
// send user to login-page with response
header("Location: ../login.php?response=".$response);
?>