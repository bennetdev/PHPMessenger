<?php


class Database
{
    private $host, $database, $username, $password, $connection;
    private $port = 3306;

    /**
     * database constructor.
     * @param $host
     * @param $database
     * @param $username
     * @param $password
     */
    public function __construct($host, $database, $username, $password)
    {
        $this->host = $host;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->connect();
    }

    public function connect(){
        $this->connection = new PDO('mysql:dbname='. $this->database .';host=' . $this->host, $this->username, $this->password,
            [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES latin1 COLLATE latin1_general_ci",]);


    }

    public function insert_file($data_to, $data_from, $type, $name,$user_id){
        $query = $this->connection->prepare("
			INSERT files (data_from, data_to, name, type, owner_id)
			VALUES (:data_from, :data_to, :name, :type, :owner_id)
		");
        $query->execute([
            'data_from' => $data_from,
            'data_to' => $data_to,
            "name" => $name,
            "type" => $type,
            "owner_id" => $user_id,
        ]);

        $lastId = $this->connection->lastInsertId();
        return $lastId;
    }
    public function get_file($id){
        $query = $this->connection->prepare("
        SELECT * FROM files
        WHERE id = :id;
        ");
        $query->execute([
            "id" => $id
        ]);

        $file = $query->rowCount() ? $query->fetchAll(\PDO::FETCH_ASSOC)[0] : [];
        return $file;
    }

    public function get_user_by_username($username){
        $query = $this->connection->prepare("
        SELECT * FROM users
        WHERE username = :username;
        ");
        $query->execute([
            "username" => $username
        ]);

        $user = $query->rowCount() ? $query->fetchAll(\PDO::FETCH_ASSOC)[0] : [];
        return $user;
    }

    public function get_chats($user_id){
        $query = $this->connection->prepare("
        SELECT chats.id, chats.user1_id, chats.user2_id, u1.username as user1_name, u2.username as user2_name, u1.chat_id as user1_chat_id, u2.chat_id as user2_chat_id FROM chats
        INNER JOIN users AS u1 ON chats.user1_id = u1.id
        INNER JOIN users AS u2 ON chats.user2_id = u2.id
        WHERE user1_id = :user_id OR user2_id = :user_id;
        ");
        $query->execute([
            "user_id" => $user_id
        ]);

        $chats = $query->rowCount() ? $query->fetchAll(\PDO::FETCH_ASSOC) : [];
        return $chats;
    }

    public function get_chat($user1_id, $user2_id){
        $query = $this->connection->prepare("
        SELECT chats.id, IF(u1.id = :user1_id, u2.username, u1.username) as partner_username FROM chats
        INNER JOIN users AS u1 ON chats.user1_id = u1.id
        INNER JOIN users AS u2 ON chats.user2_id = u2.id
        WHERE (chats.user1_id = :user1_id and chats.user2_id = :user2_id) or (chats.user1_id = :user2_id and chats.user2_id = :user1_id)
        ");
        $query->execute([
            "user1_id" => $user1_id,
            "user2_id" => $user2_id
        ]);

        $chat = $query->rowCount() ? $query->fetchAll(\PDO::FETCH_ASSOC)[0] : [];
        return $chat;
    }

    public function get_user($user_id){
        $query = $this->connection->prepare("
        SELECT * FROM users
        WHERE id = :user_id
        ");
        $query->execute([
            "user_id" => $user_id
        ]);

        $user = $query->rowCount() ? $query->fetchAll(\PDO::FETCH_ASSOC)[0] : [];
        return $user;
    }
    public function get_user_id_by_chat_id($chat_id){
        $query = $this->connection->prepare("
        SELECT id FROM users
        WHERE chat_id = :chat_id
        ");
        $query->execute([
            "chat_id" => $chat_id
        ]);

        $user = $query->rowCount() ? $query->fetchAll(\PDO::FETCH_ASSOC)[0] : [];
        return $user["id"];
    }

    public function get_user_by_chat_id($chat_id){
        $query = $this->connection->prepare("
        SELECT * FROM users
        WHERE chat_id = :chat_id
        ");
        $query->execute([
            "chat_id" => $chat_id
        ]);

        $user = $query->rowCount() ? $query->fetchAll(\PDO::FETCH_ASSOC)[0] : [];
        return $user;
    }

    public function get_all_chat_ids($chat_id){
        $user_id = $this->get_user_id_by_chat_id($chat_id);
        $chat_ids = array();

        $chats = $this->get_chats($user_id);
        foreach ($chats as $chat){
            if($chat["user1_id"] == $user_id){
                array_push($chat_ids, $chat["user2_chat_id"]);
            }
            else{
                array_push($chat_ids, $chat["user1_chat_id"]);
            }
        }
        return $chat_ids;
    }

    public function get_messages_by_chats_id($chats_id){
        $query = $this->connection->prepare("
        SELECT * FROM messages
        WHERE chat_id = :chat_id
        ORDER BY sent_datetime DESC
        LIMIT 50
        ");
        $query->execute([
            "chat_id" => $chats_id
        ]);

        $messages = $query->rowCount() ? $query->fetchAll(\PDO::FETCH_ASSOC) : [];
        return array_reverse($messages);
    }

    public function get_messages($user1_id, $user2_chat_id){
        $user2_id = $this->get_user_id_by_chat_id($user2_chat_id);
        $chat = $this->get_chat($user1_id, $user2_id);
        $query = $this->connection->prepare("
        SELECT messages.*, files.* FROM messages
        LEFT OUTER JOIN files on messages.file_id = files.id
        WHERE chat_id = :chat_id
        ORDER BY sent_datetime DESC
        ");
        $query->execute([
            "chat_id" => $chat["id"]
        ]);

        $messages = $query->rowCount() ? $query->fetchAll(\PDO::FETCH_ASSOC) : [];
        return array_reverse($messages);
    }

    public function get_last_message_by_chats_id($chats_id, $user_id){

        $query = $this->connection->prepare("
        SELECT IF(:user_id = from_id, message_from, message_to) as message FROM messages
        WHERE chat_id = :chat_id
        ORDER BY sent_datetime DESC
        LIMIT 1
        ");
        $query->execute([
            "chat_id" => $chats_id,
            "user_id" => $user_id
        ]);

        $message = $query->rowCount() ? $query->fetchAll(\PDO::FETCH_ASSOC)[0] : ["message" => ""];
        return $message;
    }

    public function insert_message($message){
        $from_id = $this->get_user_id_by_chat_id($message->from_chat_id);
        $to_id = $this->get_user_id_by_chat_id($message->to_chat_id);

        $chat = $this->get_chat($from_id, $to_id);

        $query = $this->connection->prepare("
			INSERT messages (from_id, chat_id, message_from, message_to, file_id)
			VALUES (:from_id, :chat_id, :message_from, :message_to, :file_id)
		");
        $query->execute([
            'from_id' => $from_id,
            "chat_id" => $chat["id"],
            'message_from' => sodium_hex2bin($message->message_from),
            'message_to' => sodium_hex2bin($message->message_to),
            "file_id" => $message->file["id"]
        ]);
        $lastId = $this->connection->lastInsertId();
    }

    public function insert_chat($user1_id, $user2_id){
        $query = $this->connection->prepare("
			INSERT chats (user1_id, user2_id)
			VALUES (:user1_id, :user2_id)
		");
        $query->execute([
            "user1_id" => $user1_id,
            "user2_id" => $user2_id
        ]);
    }

    public function set_online($online, $chat_id){
        $nameQuery = $this->connection->prepare("
			UPDATE users SET online = :online WHERE chat_id = :chat_id
		");
        $nameQuery->execute([
            'online' => $online,
            'chat_id' => $chat_id,
        ]);
    }

    public function insert_user($username, $password, $encryption_key, $public_key, $secret_key, $encryption_salt){
        $statement = $this->connection->prepare("
		        	INSERT INTO users (username, password, chat_id, public, secret, encryption_key, encryption_salt)
		        	VALUES (:username, :password, :chat_id, :public, :secret, :encryption_key, :encryption_salt)
	        ");
        $result = $statement->execute([
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'chat_id' => random_int(0, 999999999),
            'public' => $public_key,
            'secret' => $secret_key,
            'encryption_key' => $encryption_key,
            'encryption_salt' => $encryption_salt,
        ]);
    }

    public function update_session_id($user_id){
        $session_id = random_int(0, 999999999);
        $statement = $this->connection->prepare("
		        	UPDATE users SET session_id = :session_id WHERE id = :id
	        ");
        $result = $statement->execute([
            "id" => $user_id,
            "session_id" => $session_id
        ]);
        return $session_id;
    }

    public function check_session_id($user_chat_id, $session_id){
        $query = $this->connection->prepare("
        SELECT * FROM users
        WHERE chat_id = :chat_id and session_id = :session_id
        ");
        $query->execute([
            "chat_id" => $user_chat_id,
            "session_id" => $session_id
        ]);

        return $query->rowCount() ? true : false;
    }

}