<?php
session_start();
require_once "classes/Database.php";
if(isset($_SESSION["user_id"]) && isset($_POST["chat_id"])){
    $database = new Database("localhost", "messenger", "root", "");
    $user_id = $_SESSION["user_id"];
    $chat_id = $_POST["chat_id"];

    $user2_id = $database->get_user_id_by_chat_id($chat_id);

    $chat = $database->get_chat($user_id, $user2_id);
    // get last_message and decrypt
    $last_message = sodium_crypto_box_seal_open($database->get_last_message_by_chats_id($chat["id"], $_SESSION["user_id"])["message"], $_SESSION["keypair"]);

    echo '<div class="chat" data-id="' . $chat_id . '">
                    <span class="material-icons profile">account_circle</span>
                    <div class="chat_details">
                        <h3 class="chat_title">' . $chat["partner_username"] . '</h3>
                        <p class="last_message">' . $last_message . '</p>
                    </div>
                </div>
                <hr>';
}