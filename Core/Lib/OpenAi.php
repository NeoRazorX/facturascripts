<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Http;
use FacturaScripts\Core\Tools;
use stdClass;

class OpenAi
{
    const AUDIO_SPEECH_URL = 'https://api.openai.com/v1/audio/speech';
    const CHAT_URL = 'https://api.openai.com/v1/chat/completions';
    const IMAGES_URL = 'https://api.openai.com/v1/images/generations';

    /** @var string */
    protected $api_key;

    /** @var int */
    protected $timeout = 60;

    /** @var int */
    protected $total_tokens = 0;

    public function __construct(string $api_key)
    {
        $this->api_key = $api_key;

        if (empty($this->api_key)) {
            Tools::log()->error('OpenAI API Key not found');
        }
    }

    public function audio(string $input, string $voice = 'alloy', string $format = 'mp3', string $model = 'tts-1'): string
    {
        $data = [
            'model' => $model,
            'input' => $input,
            'voice' => $voice,
            'response_format' => $format
        ];
        $response = Http::post(self::AUDIO_SPEECH_URL, json_encode($data))
            ->setHeader('Content-Type', 'application/json')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('audio speech error: ' . $response->status() . ' ' . $response->errorMessage());
            return '';
        }

        $json = $response->json();
        $url = $json['data'][0]['url'] ?? '';
        if (empty($url)) {
            Tools::log()->error('audio speech error: empty response');
            return '';
        }

        // descargamos el audio en MyFiles
        $filename = 'audio_' . uniqid() . '.' . $format;
        $audio = file_get_contents($url);
        if (file_put_contents(Tools::folder('MyFiles', $filename), $audio) === false) {
            Tools::log()->error('audio speech error: saving audio');
            return '';
        }

        return 'MyFiles/' . $filename;
    }

    public function audioHD(string $input, string $voice = 'alloy', string $format = 'mp3'): string
    {
        return $this->audio($input, $voice, $format, 'tts-1-hd');
    }

    public function chat(array $messages, string $user = '', string $model = 'gpt-3.5-turbo'): string
    {
        $params = new stdClass();
        $params->model = $model;
        $params->messages = $messages;
        if ($user) {
            $params->user = $user;
        }

        $response = Http::post(self::CHAT_URL, json_encode($params))
            ->setHeader('Content-Type', 'application/json')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('chatGPT error: ' . $response->status() . ' ' . $response->errorMessage());
            return '';
        }

        $json = $response->json();
        if (empty($json['choices'])) {
            Tools::log()->error('chatGPT error: empty response');
            return '';
        }

        $this->total_tokens = $json['usage']['total_tokens'];
        return $json['choices'][0]['message']['content'];
    }

    public function chatGpt35turbo(array $messages, string $user = ''): string
    {
        return $this->chat($messages, $user, 'gpt-3.5-turbo');
    }

    public function chatGpt4(array $messages, string $user = ''): string
    {
        return $this->chat($messages, $user, 'gpt-4');
    }

    public function chatGpt4turbo(array $messages, string $user = ''): string
    {
        return $this->chat($messages, $user, 'gpt-4-turbo-preview');
    }

    public function dalle2(string $prompt, int $width = 256, int $height = 256, $count = 1): string
    {
        return $this->image($prompt, $width, $height, $count, 'dall-e-2');
    }

    public function dalle3(string $prompt, int $width = 1024, int $height = 1024, $count = 1): string
    {
        return $this->image($prompt, $width, $height, $count, 'dall-e-3');
    }

    public function getTotalTokens(): int
    {
        return $this->total_tokens;
    }

    public function image(string $prompt, int $width = 256, int $height = 256, $count = 1, string $model = 'dall-e-2'): string
    {
        $data = [
            'model' => $model,
            'prompt' => $prompt,
            'n' => $count,
            'size' => $width . 'x' . $height,
        ];
        $response = Http::post(self::IMAGES_URL, json_encode($data))
            ->setHeader('Content-Type', 'application/json')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('dalle error: ' . $response->status() . ' ' . $response->errorMessage());
            return '';
        }

        // descargamos la imagen en MyFiles
        $json = $response->json();
        $url = $json['data'][0]['url'] ?? '';
        if (empty($url)) {
            Tools::log()->error('dalle error: no image url');
            return '';
        }

        $filename = 'image_' . uniqid() . '.png';
        $image = file_get_contents($url);
        if (file_put_contents(Tools::folder('MyFiles', $filename), $image) === false) {
            Tools::log()->error('dalle error: saving image');
            return '';
        }

        return 'MyFiles/' . $filename;
    }

    public static function init(string $api_key): self
    {
        return new OpenAi($api_key);
    }

    public function setSystemMessage(array &$messages, string $message): void
    {
        $messages[] = ['role' => 'system', 'content' => $message];
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function setUserMessage(array &$messages, string $message): void
    {
        $messages[] = ['role' => 'user', 'content' => $message];
    }
}
