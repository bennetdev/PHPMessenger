<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . "/app/classes/Database.php";
require_once dirname(__DIR__) . "/app/classes/Message.php";

class User{
    public $chat_id;
    public $session_id;
    public $conn;

    public function __construct($chat_id, $session_id, $conn)
    {
        $this->session_id = $session_id;
        $this->chat_id =  $chat_id;
        $this->conn = $conn;
    }
}

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $users;
    protected $db;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->db = new Database("localhost", "messenger", "root", "");
    }

    public function onOpen(ConnectionInterface $conn) {
        // get chat_id and session_id of user
        parse_str($conn->httpRequest->getUri()->getQuery(), $query);
        $chat_id = $query["chat_id"];
        $session_id = $query["session_id"];

        // check if the session_id matches the user (additional layer of security to make sure the user connecting is logged in with his account)
        if($this->db->check_session_id($chat_id, $session_id)){
            // Store the new connection to send messages to later
            $this->clients->attach($conn);

            // store user associated to connection id
            $this->users[$conn->resourceId] = new User($chat_id, $session_id, $conn);

            // send user is online to all of his chats and store online_status in database
            $this->sendStatus($this->db->get_all_chat_ids($query["chat_id"]),$query["chat_id"], "online_status", true);
            $this->db->set_online(true, $query["chat_id"]);
            echo "New connection! ({$conn->resourceId})\n";
        }
        else{
            echo "wrong session id";
        }
    }


    public function onMessage(ConnectionInterface $from, $data) {
        // check if user connection was accepted
        if($this->clients->contains($from)){
            $from_id = $from->resourceId;
            $data = json_decode($data);

            $from = $this->users[$from_id];
            $to = $data->to_id;
            switch ($data->type){
                // if incoming message has type message
                case "message":
                    // save file data if $data->file is a number (id in database)
                    $file = is_numeric($data->file) ? $this->db->get_file($data->file) : null;
                    // create message object and escape special chars
                    $message = new Message($to, $from->chat_id, htmlspecialchars($data->message_from), htmlspecialchars($data->message_to), $file);

                    // set file to null if the owner of the file is not the message-sender -> Prevent user manipulating file_id
                    if(!is_null($file) && $file["owner_id"] !== $this->db->get_user_by_chat_id($from->chat_id)["id"]){
                        $file = null;
                    }

                    $response_from = $message->get_html($from->chat_id);
                    $response_to = $message->get_html($to);
                    // send message back to sender
                    $from->conn->send(json_encode(array("to_id"=>$to, "message"=>$response_from, "type"=>"message", "text"=>$message->get_message($from->chat_id), "file"=>sodium_bin2hex($file["data_from"]))));

                    // send message to chat partner
                    $this->privateMessage($from->chat_id, $to, $response_to, $message->get_message($to), sodium_bin2hex($message->file["data_to"]));

                    // insert in database
                    $this->db->insert_message($message);
                    break;
                // if incoming message has type writing
                case "writing":
                    // send writing status to current chat_partner
                    $this->send_private_status($to, $from->chat_id, "writing_status", $data->status);
            }
        }
    }

    // Send message to one specific user
    public function privateMessage($from_chat_id, $to_chat_id, $message, $text, $file){
        foreach($this->users as $user)
        {
            if($user->chat_id == $to_chat_id)
            {
                $user->conn->send(json_encode(array("from_id"=>$from_chat_id, "message"=>$message, "type"=>"message", "text"=>$text, "file"=>$file)));
            }
        }
    }

    public function send_private_status($to_id, $from_id, $type, $status){
        foreach($this->users as $user)
        {
            if($user->chat_id == $to_id)
            {
                $user->conn->send(json_encode(array("type"=>$type, "from_id" => $from_id, "status" => $status)));
            }
        }

    }

    // send status to multiple users (type can be onlineStatus, writeStatus)
    public function sendStatus($to_chat_ids, $from_id, $type, $status){
        foreach($this->users as $user)
        {
            if(in_array($user->chat_id, $to_chat_ids))
            {
                $user->conn->send(json_encode(array("type"=>$type, "from_id" => $from_id, "status" => $status)));
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // check if user connection was accepted
        if($this->clients->contains($conn)){
            echo "close";
            // disconnect client
            $this->clients->detach($conn);
            // send online_status as offline to all of his chats and store in database
            $this->sendStatus($this->db->get_all_chat_ids($this->users[$conn->resourceId]->chat_id),$this->users[$conn->resourceId]->chat_id, "online_status", false);
            $this->db->set_online(false, $this->users[$conn->resourceId]->chat_id);
            // remove from users
            unset($this->users[$conn->resourceId]);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}