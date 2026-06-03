<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use CURLFile;
use FacturaScripts\Core\Http;
use FacturaScripts\Core\Tools;
use stdClass;
use Throwable;

class OpenAi
{
    const ASSISTANTS_URL = 'https://api.openai.com/v1/assistants';
    const AUDIO_SPEECH_URL = 'https://api.openai.com/v1/audio/speech';
    const AUDIO_TRANSCRIPT_URL = 'https://api.openai.com/v1/audio/transcriptions';
    const CHAT_URL = 'https://api.openai.com/v1/chat/completions';
    const FILES_URL = 'https://api.openai.com/v1/files';
    const IMAGES_URL = 'https://api.openai.com/v1/images/generations';
    const THREADS_URL = 'https://api.openai.com/v1/threads';
    const VECTOR_URL = 'https://api.openai.com/v1/vector_stores';

    /** @var string */
    protected $api_key;

    /** @var int */
    protected $timeout = 60;

    /** @var int */
    protected $total_tokens = 0;

    /**
     * Inicializa el cliente con la API Key de OpenAI.
     */
    public function __construct(string $api_key)
    {
        $this->api_key = $api_key;

        if (empty($this->api_key)) {
            Tools::log()->error('OpenAI API Key not found');
        }
    }

    /**
     * Crea un nuevo asistente con los parámetros indicados.
     */
    public function assistantCreate(array $params): array
    {
        $response = Http::post(self::ASSISTANTS_URL, json_encode($params))
            ->setHeader('OpenAI-Beta', 'assistants=v2')
            ->setHeader('Content-Type', 'application/json')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('chatGPT assistant create error: ' . $response->status() . ' '
                . $response->errorMessage() . ' ' . $response->body());
            return [];
        }

        return $response->json();
    }

    /**
     * Devuelve los datos del asistente indicado.
     */
    public function assistantRead(string $idAssistant): array
    {
        $response = Http::get(self::ASSISTANTS_URL . '/' . $idAssistant)
            ->setHeader('OpenAI-Beta', 'assistants=v2')
            ->setHeader('Content-Type', 'application/json')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('chatGPT assistant read error: ' . $response->status() . ' '
                . $response->errorMessage() . ' ' . $response->body());
            return [];
        }

        return $response->json();
    }

    /**
     * Actualiza el asistente indicado con los parámetros recibidos.
     */
    public function assistantUpdate(string $idAssistant, array $params)
    {
        $response = Http::post(self::ASSISTANTS_URL . '/' . $idAssistant, json_encode($params))
            ->setHeader('OpenAI-Beta', 'assistants=v2')
            ->setHeader('Content-Type', 'application/json')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('chatGPT assistant update error: ' . $response->status() . ' '
                . $response->errorMessage() . ' ' . $response->body());
            return [];
        }

        return $response->json();
    }

    /**
     * Convierte texto en audio (texto a voz) y guarda el archivo en MyFiles, devolviendo su ruta.
     */
    public function audio(string $input, string $voice = 'alloy', string $format = 'mp3', string $model = 'gpt-4o-mini-tts'): string
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

        // guardamos el audio en MyFiles
        $filename = 'audio_' . uniqid() . '.' . $format;
        if ($response->saveAs(Tools::folder('MyFiles', $filename)) === false) {
            Tools::log()->error('audio speech error: saving audio');
            return '';
        }

        return 'MyFiles/' . $filename;
    }

    /**
     * Convierte texto en audio. Se mantiene por compatibilidad.
     *
     * @deprecated since 2026, gpt-4o-mini-tts no tiene variante HD. Usar audio() en su lugar.
     */
    public function audioHD(string $input, string $voice = 'alloy', string $format = 'mp3'): string
    {
        return $this->audio($input, $voice, $format, 'gpt-4o-mini-tts');
    }

    /**
     * Transcribe a texto el archivo de audio indicado.
     */
    public function audioTranscript(CURLFile $file, string $model = 'gpt-4o-transcribe'): string
    {
        $data = [
            'file' => $file,
            'model' => $model
        ];

        $response = Http::post(self::AUDIO_TRANSCRIPT_URL, $data)
            ->setHeader('Content-Type', 'multipart/form-data')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('audio transcript error: ' . $response->status() . ' '
                . $response->errorMessage() . ' ' . $response->body());
            return '';
        }

        return $response->json()['text'] ?? '';
    }

    /**
     * Envía una conversación al modelo de chat y devuelve la respuesta como texto.
     */
    public function chat(array $messages, string $user = '', string $model = 'gpt-5-mini'): string
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
            Tools::log()->error(
                'chatGPT error: ' . $response->status() . ' ' . $response->errorMessage(),
                $response->json() ?? []
            );
            return '';
        }

        $json = $response->json();
        if (empty($json['choices'])) {
            Tools::log()->error('chatGPT error: empty response. ' . $response->body());
            return '';
        }

        $this->total_tokens = $json['usage']['total_tokens'];
        return $json['choices'][0]['message']['content'];
    }

    /**
     * Envía una conversación al modelo de chat forzando un formato de respuesta y la devuelve como array.
     */
    public function chatJson(array $messages, array $response_format, string $user = '', string $model = 'gpt-5-mini'): array
    {
        $params = new stdClass();
        $params->model = $model;
        $params->messages = $messages;
        if ($user) {
            $params->user = $user;
        }
        $params->response_format = $response_format;

        $response = Http::post(self::CHAT_URL, json_encode($params))
            ->setHeader('Content-Type', 'application/json')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error(
                'chatGPT error: ' . $response->status() . ' ' . $response->errorMessage(),
                $response->json() ?? []
            );
            return [];
        }

        $json = $response->json();
        if (empty($json['choices'])) {
            Tools::log()->error('chatGPT error: empty response. ' . $response->body());
            return [];
        }

        $this->total_tokens = $json['usage']['total_tokens'];
        return json_decode($json['choices'][0]['message']['content'], true) ?? [];
    }


    /**
     * Elimina el archivo indicado de OpenAI.
     */
    public function fileDelete(string $id_file): bool
    {
        $response = Http::delete(self::FILES_URL . '/' . $id_file)
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('chatGPT delete file error: ' . $response->status() . ' ' . $response->errorMessage());
            return false;
        }

        return true;
    }

    /**
     * Devuelve la lista de archivos subidos a OpenAI.
     */
    public function fileList(): array
    {
        $response = Http::get(self::FILES_URL)
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('chatGPT file list error: ' . $response->status() . ' ' . $response->errorMessage());
            return [];
        }

        $json = $response->json();
        return empty($json) || empty($json['data']) ? [] : $json['data'];
    }

    /**
     * Devuelve los datos del archivo indicado.
     */
    public function fileRead(string $id_file): array
    {
        $response = Http::get(self::FILES_URL . '/' . $id_file)
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('chatGPT read file error: ' . $response->status() . ' ' . $response->errorMessage());
            return [];
        }

        return $response->json();
    }

    /**
     * Sube un archivo a OpenAI para el propósito indicado.
     */
    public function fileUpload(CURLFile $file, string $purpose = 'assistants'): array
    {
        $data = [
            'purpose' => $purpose,
            'file' => $file
        ];

        $response = Http::post(self::FILES_URL, $data)
            ->setHeader('Content-Type', 'multipart/form-data')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('chatGPT file upload error: ' . $response->status() . ' '
                . $response->errorMessage() . ' ' . $response->body());
            return [];
        }

        return $response->json();
    }

    /**
     * Devuelve el total de tokens consumidos en la última petición de chat.
     */
    public function getTotalTokens(): int
    {
        return $this->total_tokens;
    }

    /**
     * Genera una imagen a partir del prompt, la guarda en MyFiles y devuelve su ruta.
     */
    public function image(string $prompt, int $width = 1024, int $height = 1024, int $count = 1, string $model = 'gpt-image-2-mini', array $options = []): string
    {
        $resize = false;
        $data = [
            'model' => $model,
            'prompt' => $prompt,
            'n' => $count,
            'size' => $this->getImageSize($resize, $width, $height)
        ];

        // Añadir parámetros opcionales
        if (isset($options['output_format'])) {
            $data['output_format'] = $options['output_format'];
        }
        if (isset($options['output_compression'])) {
            $data['output_compression'] = $options['output_compression'];
        }
        if (isset($options['stream'])) {
            $data['stream'] = $options['stream'];
        }
        if (isset($options['content_moderation'])) {
            $data['content_moderation'] = $options['content_moderation'];
        }

        $response = Http::post(self::IMAGES_URL, json_encode($data))
            ->setHeader('Content-Type', 'application/json')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('image generation error: ' . $response->status() . ' ' . $response->errorMessage(), [
                'body' => $response->body(),
                'data_sent' => $data
            ]);
            return '';
        }

        // descargamos la imagen en MyFiles
        $json = $response->json();

        // Determinar la extensión del archivo según el formato de salida
        $format = $options['output_format'] ?? 'png';
        $file_name = 'image_' . uniqid() . '.' . $format;
        $file_path = 'MyFiles/' . $file_name;

        // gpt-image devuelve la imagen en base64
        if (false === isset($json['data'][0]['b64_json'])) {
            Tools::log()->error('image generation error: no image base64', [
                'response' => $json,
                'model' => $model
            ]);
            return '';
        }

        $image = base64_decode($json['data'][0]['b64_json']);
        if (file_put_contents($file_path, $image) === false) {
            Tools::log()->error('image generation error: saving image from base64');
            return '';
        }

        if ($resize) {
            $resized = $this->imageResize($file_path, $width, $height);

            if (!empty($resized)) {
                unlink($file_path);
                return $resized;
            }
        }

        return $file_path;
    }

    /**
     * Crea y devuelve una nueva instancia del cliente.
     */
    public static function init(string $api_key): self
    {
        return new OpenAi($api_key);
    }

    /**
     * Añade un mensaje de sistema a la conversación.
     */
    public function setSystemMessage(array &$messages, string $message): self
    {
        $messages[] = ['role' => 'system', 'content' => $message];

        return $this;
    }

    /**
     * Establece el tiempo máximo de espera (en segundos) de las peticiones.
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Añade un mensaje de usuario a la conversación.
     */
    public function setUserMessage(array &$messages, string $message): self
    {
        $messages[] = ['role' => 'user', 'content' => $message];

        return $this;
    }

    /**
     * Crea un nuevo hilo de conversación.
     */
    public function threadCreate(): array
    {
        $response = Http::post(self::THREADS_URL)
            ->setHeader('OpenAI-Beta', 'assistants=v2')
            ->setHeader('Content-Type', 'application/json')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('chatGPT thread create error: ' . $response->status() . ' '
                . $response->errorMessage() . ' ' . $response->body());
            return [];
        }

        return $response->json();
    }

    /**
     * Devuelve los mensajes del hilo indicado.
     */
    public function threadMessages(string $id_thread, string $id_run = ''): array
    {
        $data = empty($id_run) ? [] : ['run_id' => $id_run];
        $response = Http::get(self::THREADS_URL . '/' . $id_thread . '/messages', $data)
            ->setHeader('OpenAI-Beta', 'assistants=v2')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('chatGPT thread messages error: ' . $response->status() . ' '
                . $response->errorMessage() . ' ' . $response->body());
            return [];
        }

        return $response->json();
    }

    /**
     * Añade un mensaje al hilo indicado.
     */
    public function threadMessageCreate(array $message, string $id_thread): array
    {
        $response = Http::post(self::THREADS_URL . '/' . $id_thread . '/messages', json_encode($message))
            ->setHeader('OpenAI-Beta', 'assistants=v2')
            ->setHeader('Content-Type', 'application/json')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('chatGPT thread message create error: ' . $response->status() . ' '
                . $response->errorMessage() . ' ' . $response->body());
            return [];
        }

        return $response->json();
    }

    /**
     * Devuelve los datos del hilo indicado.
     */
    public function threadRead(string $id_thread): array
    {
        $response = Http::get(self::THREADS_URL . '/' . $id_thread)
            ->setHeader('OpenAI-Beta', 'assistants=v2')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('chatGPT thread read error: ' . $response->status() . ' '
                . $response->errorMessage() . ' ' . $response->body());
            return [];
        }

        return $response->json();
    }

    /**
     * Ejecuta el asistente indicado sobre el hilo.
     */
    public function threadRun(string $id_thread, string $id_assistant): array
    {
        $data = ['assistant_id' => $id_assistant];
        $response = Http::post(self::THREADS_URL . '/' . $id_thread . '/runs', json_encode($data))
            ->setHeader('OpenAI-Beta', 'assistants=v2')
            ->setHeader('Content-Type', 'application/json')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('chatGPT thread run error: ' . $response->status() . ' '
                . $response->errorMessage() . ' ' . $response->body());
            return [];
        }

        return $response->json();
    }

    /**
     * Envía los resultados de las herramientas requeridas por una ejecución en curso.
     */
    public function threadRunSubmitToolOutputs(string $id_thread, string $id_run, array $outputs): array
    {
        $data = ['tool_outputs' => $outputs];
        $response = Http::post(self::THREADS_URL . '/' . $id_thread . '/runs/' . $id_run . '/submit_tool_outputs', json_encode($data))
            ->setHeader('OpenAI-Beta', 'assistants=v2')
            ->setHeader('Content-Type', 'application/json')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('chatGPT thread run submit tool outputs error: ' . $response->status() . ' '
                . $response->errorMessage() . ' ' . $response->body());
            return [];
        }

        return $response->json();
    }

    /**
     * Devuelve el estado de la ejecución indicada del hilo.
     */
    public function threadRunRead(string $id_thread, string $id_run): array
    {
        $response = Http::get(self::THREADS_URL . '/' . $id_thread . '/runs/' . $id_run)
            ->setHeader('OpenAI-Beta', 'assistants=v2')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('chatGPT thread run read error: ' . $response->status() . ' '
                . $response->errorMessage() . ' ' . $response->body());
            return [];
        }

        return $response->json();
    }

    /**
     * Crea un nuevo almacén de vectores (vector store).
     */
    public function vectorCreate(array $data): array
    {
        $response = Http::post(self::VECTOR_URL, json_encode($data))
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('OpenAI-Beta', 'assistants=v2')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('vector create error: ' . $response->status() . ' ' . $response->errorMessage()
                . ' ' . $response->body());
            return [];
        }

        return $response->json();
    }

    /**
     * Devuelve los archivos del almacén de vectores indicado.
     */
    public function vectorFiles(string $idVector, array $data = []): array
    {
        $response = Http::get(self::VECTOR_URL . '/' . $idVector . '/files', $data)
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('OpenAI-Beta', 'assistants=v2')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('vector files error: ' . $response->status() . ' ' . $response->errorMessage()
                . ' ' . $response->body());
            return [];
        }

        return $response->json();
    }

    /**
     * Elimina un archivo del almacén de vectores indicado.
     */
    public function vectorFileDelete(string $idVector, string $idFile): bool
    {
        $response = Http::delete(self::VECTOR_URL . '/' . $idVector . '/files/' . $idFile)
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('OpenAI-Beta', 'assistants=v2')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('vector file delete error: ' . $response->status() . ' ' . $response->errorMessage()
                . ' ' . $response->body());
            return false;
        }

        return true;
    }

    /**
     * Devuelve los datos del almacén de vectores indicado.
     */
    public function vectorRead(string $idVector): array
    {
        $response = Http::get(self::VECTOR_URL . '/' . $idVector)
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('OpenAI-Beta', 'assistants=v2')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('vector read error: ' . $response->status() . ' ' . $response->errorMessage()
                . ' ' . $response->body());
            return [];
        }

        return $response->json();
    }

    /**
     * Añade un archivo al almacén de vectores indicado.
     */
    public function vectorFile(string $id_vector, string $id_file): array
    {
        $data = ['file_id' => $id_file];
        $response = Http::post(self::VECTOR_URL . '/' . $id_vector . '/files', json_encode($data))
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('OpenAI-Beta', 'assistants=v2')
            ->setBearerToken($this->api_key)
            ->setTimeOut($this->timeout);

        if ($response->failed()) {
            Tools::log()->error('vector file error: ' . $response->status() . ' ' . $response->errorMessage()
                . ' ' . $response->body());
            return [];
        }

        return $response->json();
    }

    /**
     * Devuelve un tamaño de imagen válido para el modelo; si no lo es, marca $resize para redimensionar después.
     */
    private function getImageSize(bool &$resize, int $width, int $height): string
    {
        // Tamaños soportados por GPT Image: 1024x1024, 1536x1024, 1024x1536
        $validSizes = [
            '1024x1024',
            '1536x1024',
            '1024x1536'
        ];

        $size = $width . 'x' . $height;
        if (!in_array($size, $validSizes)) {
            $resize = true;

            // Elegir el tamaño base según la orientación
            if ($width > $height) {
                // Horizontal/landscape
                return '1536x1024';
            } elseif ($height > $width) {
                // Vertical/portrait
                return '1024x1536';
            }

            // Cuadrado
            return '1024x1024';
        }

        return $size;
    }

    /**
     * Redimensiona la imagen al tamaño indicado y devuelve la ruta del nuevo archivo.
     */
    private function imageResize(string $filePath, int $width, int $height): string
    {
        try {
            $image = imagecreatefromstring(file_get_contents($filePath));
            $imageWidth = imagesx($image);
            $imageHeight = imagesy($image);

            $thumb = imagecreatetruecolor($width, $height);

            // Preservar transparencia para PNG
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            if ($ext === 'png') {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
                imagefilledrectangle($thumb, 0, 0, $width, $height, $transparent);
            }

            imagecopyresampled($thumb, $image, 0, 0, 0, 0, $width, $height, $imageWidth, $imageHeight);
            $thumbName = pathinfo($filePath, PATHINFO_FILENAME) . '_' . $width . 'x' . $height . '.' . $ext;
            $thumbFile = 'MyFiles/' . $thumbName;
            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($thumb, $thumbFile);
                    break;

                case 'png':
                    imagepng($thumb, $thumbFile);
                    break;

                case 'gif':
                    imagegif($thumb, $thumbFile);
                    break;
            }
        } catch (Throwable $th) {
            Tools::log('openai-image')->error('image resize error: ' . $th->getMessage());
            return '';
        }

        return $thumbFile;
    }
}
