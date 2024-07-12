<?php

namespace App\Services;

use App\Conversation\ExampleConversation;
use BotMan\BotMan\BotMan;
use UnhandledMatchError;

class ChatHandlerService implements HandlerInterface
{
    public function __construct(
        private BotMan $botman, 
        private mixed $message
    ){}

    public function handle()
    {
        try {

            match($this->message) {

                'help' => $this->botman->reply('Say hi to start a conversation'),

                'hi' => $this->botman->startConversation(new ExampleConversation()),
            };

        } catch(UnhandledMatchError $e) {
            
            $this->botman->reply('I couldn\'t understand');

            $this->botman->reply('Say hi or help');
        }
    }
}