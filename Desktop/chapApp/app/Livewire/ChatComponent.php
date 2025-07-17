<?php

namespace App\Livewire;

use App\Events\MessageSentEvent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Mail\Events\MessageSent;
use Livewire\Attributes\On;
use Livewire\Component;
use Psy\CodeCleaner\ReturnTypePass;

class ChatComponent extends Component
{
    public $user;
    public $sender_id;
    public $receiver_id;

    public $message = '';
    public $messages = [];   // ✅ Add this line
    public function render()
    {
        return view('livewire.chat-component');
    }

    public function mount($user_id)
    {
        $this->sender_id = auth()->user()->id;
        $this->receiver_id = $user_id;
        $this->user = User::whereId($user_id)->first();
        $messages = Message::where(function ($query) {
            $query->where('sender_id', $this->sender_id)
                ->where('receiver_id', $this->receiver_id);
        })
            ->orWhere(function ($query) {
                $query->where('sender_id', $this->receiver_id)
                    ->where('receiver_id', $this->sender_id);
            })
            ->with('sender:id,name', 'receiver:id,name')
            
            ->get();
    

        foreach ($messages as $message) {
            $this->appendChatMessage($message);
        }
                 




    }

    public function sendMessage()
    {
        $chatMessage = new Message();
        $chatMessage->sender_id = $this->sender_id;
        $chatMessage->receiver_id = $this->receiver_id;
        $chatMessage->message = $this->message;
        $chatMessage->save();
          $this->appendChatMessage($chatMessage);
        broadcast(new MessageSentEvent($chatMessage))->toOthers();

        $this->message = ''; // Clear the message input after sending

    }
    #[On('echo-private:chat-channel.{sender_id},MessageSentEvent')]
    public function listenForMessage($event) {
        $chatMessage = Message::whereId($event['message']['id'])->with('sender:id,name', 'receiver:id,name')->first();
        $this->appendChatMessage($chatMessage);

    }

    public function appendChatMessage($message)
    {
        $this->messages[] = [
            'id' => $message->id,
            'sender' => $message->sender->name,
            'receiver' => $message->receiver->name,
            'message' => $message->message,


        ];
    }
}
