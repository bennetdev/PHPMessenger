<?php
function hash_password_for_encryption($password, $salt){
    return hash_pbkdf2("sha256", $password, "5fa4a64ded13" . $salt, 4000);
}

function encrypt_ssl($data, $key){
    return openssl_encrypt($data, "AES-256-CBC", $key);
}
function decrypt_ssl($data, $key){
    return openssl_decrypt($data, "AES-256-CBC", $key);
}

function set_auth_cookies($selector_id, $key, $expires){
    setcookie("key", $key, $expires, "/");
    setcookie("selector", $selector_id, $expires, "/");
}

function clear_cookies(){
    set_auth_cookies("", "", time() - 3600);
}

function get_userid_by_cookies($db){
    if(isset($_COOKIE["selector"]) && isset($_COOKIE["key"])){
        $key = $_COOKIE["key"];
        $selector = $_COOKIE["selector"];

        $token = $db->check_cookie($selector, $key);
        if($token !== false){
            return $token["user_id"];
        }
        else{
            clear_cookies();
        }
    }
    return false;
}