<?php

namespace App\Http\Controllers;

use OpenAI;
use Telegram;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    /**
     * Set the webhook for the Telegram bot
     *
     * @return void
     */
    public function setWebhook()
    {
        //set the webhook for the Telegram bot
        $response = Telegram::setWebhook(['url' => config('telegram.bots.mybot.webhook_url')]);
        if ($response) {
            return ['message' => 'Webhook set successfully'];
        } else {
            return ['message' => 'Failed to set webhook'];
        }
    }

    /**
     * Handle incoming commands from Telegram and provide response using OpenAI
     *
     */
    public function commandHandlerWebhook()
    {
        try {
            // Retrieve the update from Telegram's commands handler
            $update = Telegram::commandsHandler(true);

            // Get the chat ID of the user who sent the message
            $chat_id = $update->getChat()->getId();

            // Get the first name of the user who sent the message
            $first_name = $update->getChat()->getFirstName();

            // Get the text of the message that was sent
            $message = $update->getMessage()->getText();

            //Create OpenAI client
            $client = OpenAI::client(config('app.openai_api_key'));

            // Send the message to OpenAI for completion
            $result = $client->completions()->create([
                'model' => 'text-davinci-003',
                'temperature' => 0.7,
                'top_p' => 1,
                'frequency_penalty' => 0,
                'presence_penalty' => 0,
                'max_tokens' => 600,
                'prompt' => $message,
            ]);

            // Get the completed text from the result
            $content = trim($result['choices'][0]['text']);

            // Send a message back to the user with the completed text
            Telegram::sendMessage([
                'chat_id' => $chat_id,
                'text' => $content
            ]);

        } catch (\Throwable $e) {
            // Log any errors that occur
            Log::error($e);
        }
    }
}
