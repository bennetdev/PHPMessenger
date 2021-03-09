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