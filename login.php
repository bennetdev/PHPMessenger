<?php
require_once "app/classes/Database.php";
require_once "app/utils.php";
session_start();

// check if cookie exists -> get user_id to skip login
$db = new Database();
$user_id = get_userid_by_cookies($db);

if($user_id !== false){
    $_SESSION["user_id"] = $user_id;
    header("Location: index.php");
}


// load response from register
if(isset($_GET["response"])){
    $response = htmlspecialchars($_GET["response"]);
}
else{
    $response = "";
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/style.css">
    <title>TimeTracker</title>
</head>
<body class="login_page">
<div class="center">
    <div class="login">
        <div class="switch_login">
            <h1>Flash Messenger</h1>
            <p><?php echo $response ?></p>
            <button id="switch_sign_up" class="switch_button">Sign up</button>
        </div>
        <div class="credentials">
            <h1>Sign in</h1>
            <form action="app/login.php" id="login-form" method="POST" class="cred_form">
                <input type="text" placeholder="Username" name="username"><br>
                <input type="password" placeholder="Password" name="password" class="password">
                <span class="material-icons toggle_visible">visibility</span><br>
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Remember me</label>
            </form>
            <button type="submit" id="submit_credentials" class="submit_form">Login</button>
        </div>
        <div class="credentials_register">
            <h1>Sign up</h1>
            <form action="app/register.php" id="register-form" method="POST" class="cred_form">
                <input type="text" placeholder="Username" name="username"><br>
                <input type="password" placeholder="Password" name="password" class="password"><br>
                <input type="password" placeholder="Password" name="password2" class="password">
            </form>
            <button type="submit" id="submit_register" class="submit_form">Register</button>
        </div>
        <button id="switch_sign_up_mobile" class="switch_button">Sign up</button>
    </div>
</div>
</body>
</html>
<script>

    function toggle_password_visible(selector, visible){
        $(selector).attr("type", visible ? "text" : "password");
    }

    $(document).ready(function (){
        $("#submit_credentials").click(function (){
            $("#login-form").submit()
        });
        $("#submit_register").click(function (){
            $("#register-form").submit()
        });
        $(document).keyup(function(event) {
            if (event.keyCode === 13) {
                if($(".credentials").css("visibility") === "hidden"){
                    $("#submit_register").click();
                }
                else{
                    $("#submit_credentials").click();
                }
            }
        });
        $("#switch_sign_up").click(function (){
            var switch_login = $(".switch_login")
            var credentials = $(".credentials")
            var credentials_register = $(".credentials_register")
            var switch_button = $("#switch_sign_up")
            switch_login.css("width", "100%")
            credentials.css("width", "0")
            credentials_register.css("width", "0")
            setTimeout(function (){
                if(credentials_register.css("visibility") === "hidden"){
                    switch_button.html("Sign in")
                    credentials_register.css("visibility", "visible")
                    credentials.css("visibility", "hidden")
                    credentials_register.css("width", "790px")
                }
                else{
                    switch_button.html("Sign up")
                    credentials_register.css("visibility", "hidden")
                    credentials.css("visibility", "visible")
                    credentials.css("width", "790px")
                }
                if(switch_login.css("order") === "0"){
                    switch_login.css("order", "1");
                } else{
                    switch_login.css("order", "0");
                }
                switch_login.css("width", "410px")
            },1000);
        })
        $("#switch_sign_up_mobile").click(function (){
            var credentials = $(".credentials")
            var credentials_register = $(".credentials_register")

            credentials.addClass("no_transition")
            credentials_register.addClass("no_transition")

            if(credentials.css("visibility") === "hidden"){
                credentials.css("visibility", "visible")
                credentials.css("width", "790px")
                credentials_register.css("visibility", "hidden")
                credentials_register.css("width", "0")
            }
            else{
                credentials.css("visibility", "hidden")
                credentials.css("width", "0")
                credentials_register.css("visibility", "visible")
                credentials_register.css("width", "790px")
            }
            credentials[0].offsetHeight;
            credentials.removeClass("no_transition")
            credentials_register.removeClass("no_transition")
        });
        $(".toggle_visible").click(function (){
            var toggle_visible = $(this);
            if(toggle_visible.html() === "visibility"){
                toggle_visible.html("visibility_off")
                toggle_password_visible(".password", true)
            }
            else{
                toggle_visible.html("visibility")
                toggle_password_visible(".password", false)
            }
        })
    })
</script>