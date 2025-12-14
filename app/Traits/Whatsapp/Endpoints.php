<?php


namespace App\Traits\Whatsapp;


trait Endpoints
{
    public static string $CREATE_NEW_SESSION='sessions/add';

     public function CHECK_STATUS_SESSION():string{
        return "sessions/status/".$this->session;
    }

    public function SEND_MESSAGE():string{
        return "chats/send?id=".$this->session;
    }
    public function GET_CHAT_LIST():string{
         return "chats";
    }
        public function GET_MESSAGES($jid):string{
        return "chats/{$jid}";
    }
    public function SEND_BULK_MESSAGE():string{
        return "chats/send-bulk?id=".$this->session;
    }
    public function DELETE_SESSION():string{
        return "/sessions/delete/".$this->session;
    }
}
