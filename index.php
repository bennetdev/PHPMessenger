<?php
require_once "app/classes/Database.php";
session_start();

// force login
if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
}

// get current user and associated chats
$db = new Database("localhost", "messenger", "root", "");
$chats = $db->get_chats($_SESSION["user_id"]);
$user = $db->get_user($_SESSION["user_id"]);


// get updated session_id
$_SESSION["session_id"] = $db->update_session_id($user["id"]);

// automatically open first chat if exists
if(!empty($chats)){
    $messages = $db->get_messages_by_chats_id($chats[0]["id"]);
    $_POST["chat_id"] = ($_SESSION["user_id"] == $chats[0]["user1_id"] ? $chats[0]["user2_chat_id"] : $chats[0]["user1_chat_id"]);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/style.css">
    <title>Messenger</title>
</head>
<body>
<div class="container">
    <div class="chats_sidebar open">
        <div class="topbar">
            <h2 id="username" class="name_title"><?php echo $user["username"] ?></h2>
        </div>
        <div class="chats">
            <?php foreach ($chats as $chat) { ?>
                <div class="chat" data-id="<?php echo ($_SESSION["user_id"] == $chat["user1_id"] ? $chat["user2_chat_id"] : $chat["user1_chat_id"]) ?>">
                    <span class="material-icons profile">account_circle</span>
                    <div class="chat_details">
                        <h3 class="chat_title"><?php echo ($_SESSION["user_id"] == $chat["user1_id"] ? $chat["user2_name"] : $chat["user1_name"]) ?></h3>
                        <p class="last_message"><?php echo sodium_crypto_box_seal_open($db->get_last_message_by_chats_id($chat["id"], $_SESSION["user_id"])["message"], $_SESSION["keypair"]) ?></p>
                        <div class="notifications">
                            0
                        </div>
                    </div>
                </div>
                <hr>
            <?php } ?>
        </div>
        <input type="text" class="text_field" id="add_input" placeholder="Username to add">
        <div class="bottombar">
            <span class="material-icons" id="add_person">person_add</span>
        </div>
    </div>
    <div class="chat_wrapper closed">
        <?php include_once "app/getMessages.php" ?>
    </div>
</div>
</body>
</html>
<script>
    $(document).ready(function (){
        conn = new WebSocket('ws://localhost:8080?chat_id=<?php echo $user["chat_id"]?>&session_id=<?php echo $_SESSION["session_id"]?>&db_id=<?php echo $_SESSION["user_id"]?>');
    })
</script>
<script src="js/script.js"></script>