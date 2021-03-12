<?php
    require_once "classes/Database.php";
    require_once "classes/Message.php";
    if(session_status() == 1){
        session_start();
    }
    if(isset($_SESSION["user_id"]) && isset($_POST["chat_id"])){
        $database = new Database();
        $user_id = $_SESSION["user_id"];
        $user_chat_id = $database->get_user($user_id)["chat_id"];
        $chat_id = $_POST["chat_id"];

        $chat_partner = $database->get_user_by_chat_id($chat_id);
        $messages = $database->get_messages($user_id, $chat_id);
        $online_status = $chat_partner["online"] ? "online" : "offline";

        echo '<div class="chat_interface" data-current_id="' . $chat_id . '">
        <div class="topbar">
            <span class="material-icons" id="menu">arrow_back</span>
            <h2 id="chatname" class="name_title">'. $chat_partner["username"] . '</h2>
            <div id="online_status" title="' . $online_status . '" class="' . $online_status . '"></div>
            <p class="last_message" id="writing_status">writing...</p>
        </div><div class="chat_messages">';
        // iterate over messages
        foreach ($messages as $message){
            // assign from and to
            if($message["from_id"] == $user_id){
                $from_chat_id = $user_chat_id;
                $to_chat_id = $chat_id;
            }
            else{
                $from_chat_id = $chat_id;
                $to_chat_id = $user_chat_id;
            }
            // create message object
            $message = new Message($to_chat_id, $from_chat_id, $message["message_from"], $message["message_to"], is_numeric($message["file_id"]) ? array("id" => $message["file_id"], "data_from" => $message["data_from"], "data_to" => $message["data_to"], "type" => $message["type"], "name" => $message["name"]) : null);

            // decrypt message with keypair stored in session
            $message->decrypt($user_chat_id, $_SESSION["keypair"]);
            // get html for current message
            echo $message->get_html($user_chat_id, false);
        }
        echo '</div></div><div class="bottombar">
            <label for="file" class="material-icons send_option" id="upload">attach_file</label>
            <input type="file" name="file" id="file">
            <input type="text" placeholder="Message" class="text_field" id="message_input">
            <span class="material-icons send_option" id="send" title="send">send</span>
        </div>';
    }