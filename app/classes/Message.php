<?php


class Message
{
    public $to_chat_id;
    public $from_chat_id;
    public $message_from;
    public $message_to;
    public $sent_datetime;
    public $file;

    /**
     * Message constructor.
     * @param $to_chat_id
     * @param $from_chat_id
     * @param $message_from
     * @param $message_to
     * @param $sent_datetime
     * @param $file
     */
    public function __construct($to_chat_id, $from_chat_id, $message_from, $message_to, $file = null, $sent_datetime = null)
    {
        $this->to_chat_id = $to_chat_id;
        $this->from_chat_id = $from_chat_id;
        $this->message_from = $message_from;
        $this->message_to = $message_to;
        $this->sent_datetime = $sent_datetime;
        $this->file = $file;
    }

    // return html for this object
    public function get_html($chat_id, $async=true){
        $file_html = "";
        $style = "";
        // if message contains file
        if(!is_null($this->file)){
            $link = "data:" . $this->file["type"] . ";base64," . (!$async ? $this->get_file($chat_id) : "");
            if(strpos($this->file["type"], "image") !== false){
                $file_html = '<img class="image" src="' . $link . '"/>';
            }
            else{
                $file_html = '<div class="text_file"><a class="download_text_file" download="' . $this->file["name"] . '" href="' . $link . '">' . $this->file["name"] . '</a></div>';
            }
            $style = 'style="max-width: 200px"';
        }

        return '<div class="message">
                    <div class="message_content ' . ($chat_id == $this->from_chat_id ? "sent" : "received" ). '" ' . $style . '>
                        ' . $file_html . '
                        <p class="message_text">' . (!$async ? $this->get_message($chat_id) : ""). '</p>
                    </div>
                </div>';
    }

    // get correct message for specific user
    public function get_message($chat_id){
        if($this->from_chat_id == $chat_id){
            return $this->message_from;
        }
        else{
            return $this->message_to;
        }
    }

    // get correct file for specific user
    public function get_file($chat_id){
        if($this->from_chat_id == $chat_id){
            return $this->file["data_from"];
        }
        else{
            return $this->file["data_to"];
        }
    }

    // decrypt file and message with given keypair
    public function decrypt($chat_id, $keypair){
        if($this->from_chat_id == $chat_id){
            $this->message_from = sodium_crypto_box_seal_open($this->message_from, $keypair);
            if(!is_null($this->file)) {
                $this->file["data_from"] = sodium_crypto_box_seal_open($this->file["data_from"], $keypair);
            }
        }
        else{
            $this->message_to = sodium_crypto_box_seal_open($this->message_to, $keypair);
            if(!is_null($this->file)) {
                $this->file["data_to"] = sodium_crypto_box_seal_open($this->file["data_to"], $keypair);
            }
        }
    }
}