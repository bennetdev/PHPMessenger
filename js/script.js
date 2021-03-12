function load_messages(chat_id){
    $.post("./app/getMessages.php",{chat_id: chat_id} ,function (result){
        $(".chat_wrapper").html(result);
        scroll_messages();
    });
}
function scroll_messages(){
    $(".chat_messages").scrollTop($(".chat_messages").prop("scrollHeight"));
}
function new_chat(chat_id){
    $.post("./app/addChat.php",{chat_id: chat_id} ,function (result){
        $(".chats").append(result);
    });
}
function add_person(username){
    $.post("./app/insertChat.php",{username: username} ,function (result){
        $(".chats").append(result)
    });
}
async function encrypt(data, chat_id){
    var formData = new FormData();
    formData.append("data", data)
    formData.append("chat_id", chat_id)
    const result = await fetch("./app/encrypt.php", {
        method: "post", body: formData
    });
    return result.text();
}

async function encrypt_me(data){
    var formData = new FormData();
    formData.append("data", data)
    const result = await fetch("./app/encrypt.php", {
        method: "post", body: formData
    });
    return result.text();
}


async function decrypt(data){
    var formData = new FormData();
    formData.append("data", data)
    const result = await fetch("./app/decrypt.php", {
        method: "post", body: formData
    });
    return result.text();
}

function update_attach_icon(){
    var label = $("#upload");
    var file = $("#file");

    label.attr("title", "")
    if(file.val() === ""){
        label.html("attach_file")
    }
    else{
        if(file.prop("files")[0].size > 2088576){
            label.html("error");
            label.attr("title", "File is too big, maximum file size: 2MB")
            file.val("");
        }
        else{
            label.html("check");
        }
    }
}

function send_writing(status, to_id){
    conn.send(JSON.stringify({
        "type": "writing",
        "status": status,
        "to_id": to_id
    }))
}

async function send_message(message, to_id){
    var file = await upload_file($("#file").prop("files")[0], to_id);
    var encrypted_message_partner = await encrypt(message, to_id);
    var encrypted_message_me = await encrypt_me(message);

    if(message !== "" || file !== ""){
        conn.send(JSON.stringify({
            "type": "message",
            "to_id": to_id,
            "message_to": encrypted_message_partner,
            "message_from": encrypted_message_me,
            "file": file
        }))
    }
    $(".chat[data-id='" + to_id + "'").find(".last_message").html(message)
    $("#file").val("");
    update_attach_icon();
}
async function upload_file(file, chat_id){
    var formdata = new FormData();
    formdata.append("uploaded_file", file)
    formdata.append("chat_id", chat_id)
    const result = await fetch("./app/insertFile.php", {
        method: "POST", body: formdata
    });
    return result.text();
}

$(document).ready(function (){

    conn.onopen = function(e) {
        console.log("Connection established!");
    };

    conn.onmessage = async function(e) {
        var data = JSON.parse(e.data);

        var current_chat_id = $(".chat_interface").data("current_id")
        var chat_messages = $(".chat_messages");
        var online_status = $("#online_status");
        var chat = $(".chat[data-id='" + data.from_id + "'")
        var writing_status = $("#writing_status");

        if(data.type === "message"){
            var decrypted_text = await decrypt(data.text)
            if(current_chat_id !== undefined && (data.from_id == current_chat_id || data.to_id == current_chat_id)){
                var message = $(data.message);
                if(data.file !== null){
                    var file = await decrypt(data.file);
                    message.find(".image").attr("src", message.find(".image").attr("src") + file);
                    message.find(".download_text_file").attr("href", message.find(".download_text_file").attr("href") + file)
                }
                chat_messages.append(message);

                message.find(".message_text").html(decrypted_text);
                chat_messages.scrollTop(chat_messages.prop("scrollHeight"));
                chat.find(".last_message").html(decrypted_text);
                decrypt(data.text).then((response) => console.log(response))
            }
            else if(chat.length){
                var notifications = chat.find(".notifications");

                chat.find(".last_message").html(decrypted_text);
                notifications.html(parseInt(notifications.html()) + 1);
                notifications.css("visibility", "visible");
            }
            else{
                new_chat(data.from_id)
            }
        }
        else if(data.type === "online_status"){
            if(data.from_id == current_chat_id){
                if(data.status === true){
                    online_status.removeClass("offline");
                    online_status.addClass("online");
                    online_status.attr("title", "online");
                }
                else{
                    online_status.removeClass("online");
                    online_status.addClass("offline");
                    online_status.attr("title", "offline");
                }
            }
        }
        else if(data.type === "writing_status"){
            if(data.from_id == current_chat_id){
                writing_status.css("visibility", data.status ? "visible" : "hidden")
            }
        }
    };

    $(".chat_wrapper").on("click", "#send", function (){
        var input = $("#message_input");

        send_message(input.val(), $(".chat_interface").data("current_id"))
        input.val("");
        scroll_messages();
    })
    $(".chat_wrapper").on("keyup", "#message_input", function (event){
        if (event.keyCode === 13 && !event.shiftKey) {
            send_message($("#message_input").val(), $(".chat_interface").data("current_id"))
            $(this).val("");
        }
    });
    $("#add_input").on("keyup", function (event){
        if (event.keyCode === 13) {
            add_person($("#add_input").val());
            $("#add_input").val("");
        }
    });

    scroll_messages();
    $(".chats").on("click", ".chat", function (){
        var notifications = $(this).find(".notifications")
        load_messages($(this).data("id"));
        notifications.css("visibility", "hidden");
        notifications.html("0");

        $(".chat_wrapper").addClass("open");
        $(".chat_wrapper").removeClass("closed");
        $(".chats_sidebar").addClass("closed");
        $(".chats_sidebar").removeClass("open");
    });
    $(".chat_wrapper").on("change", "#file", function (){
        update_attach_icon();
    });
    $("#add_person").click(function (){
        add_person($("#add_input").val());
        $("#add_input").val("");
    });
    $(".chat_wrapper").on("focusin", "#message_input", function (){
        send_writing(true, $(".chat_interface").data("current_id"));
    })
    $(".chat_wrapper").on("focusout", "#message_input", function (){
        send_writing(false, $(".chat_interface").data("current_id"));
    })
    $(window).on("beforeunload", function (){
       send_writing(false, $(".chat_interface").data("current_id"));
    });
    $(".chat_wrapper").on("click", "#menu", function (){
        $(".chat_wrapper").addClass("closed");
        $(".chat_wrapper").removeClass("open");
        $(".chats_sidebar").addClass("open");
        $(".chats_sidebar").removeClass("closed");
    });
})