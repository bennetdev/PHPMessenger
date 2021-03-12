<?php
session_start();
require_once "classes/Database.php";
if(isset($_SESSION["user_id"]) && isset($_POST["username"])){
    $database = new Database();
    $user_id = $_SESSION["user_id"];
    $username = $_POST["username"];

    $user2 = $database->get_user_by_username($username);
    $chat = $database->get_chat($user_id, $user2["id"]);

    // if username exists and user does not already have a chat with him and the other user is not the same as calling the script
    if(!empty($user2) && empty($chat) && $user2["id"] !== $user_id){
        // save chat in database
        $database->insert_chat($user_id, $user2["id"]);

        $chat = $database->get_chat($user_id, $user2["id"]);
        echo '<div class="chat" data-id="' . $user2["chat_id"] . '">
                    <span class="material-icons profile">account_circle</span>
                    <div class="chat_details">
                        <h3 class="chat_title">' . $chat["partner_username"] . '</h3>
                        <p class="last_message"></p>
                    </div>
                </div>
                <hr>';
    }
}