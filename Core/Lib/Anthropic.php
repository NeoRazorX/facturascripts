<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Cliente para la API de Claude (Anthropic): conversación, respuesta JSON estructurada,
 * conteo de tokens y gestión de archivos.
 *
 * Mantiene la interfaz de OpenAi para que un plugin pueda cambiar de proveedor sin tocar su código:
 * la conversación se construye igual con setSystemMessage() y setUserMessage(), y los mensajes de
 * sistema se trasladan al campo `system` que exige la API de Claude.
 *
 * Los errores no lanzan excepciones: se registran en el log y el método devuelve un valor vacío
 * ('', [], 0 o false). No hay reintentos ni streaming. Con los modelos de FALLBACK_MODELS, las
 * peticiones de conversación activan el fallback del servidor (`fallbacks: default`), que repite en
 * otro modelo una petición rechazada por los filtros de seguridad cuando la categoría del rechazo lo
 * permite.
 *
 * Ejemplo de uso:
 *
 *     $messages = [];
 *     $claude = Anthropic::init($apiKey)
 *         ->setSystemMessage($messages, 'Eres un asistente contable.')
 *         ->setUserMessage($messages, 'Resume esta factura: ...');
 *     $text = $claude->chat($messages);
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class Anthropic
{
    /** Versión de la API enviada en la cabecera `anthropic-version`. */
    const API_VERSION = '2026-10-21';

    /** Endpoint de conteo de tokens. */
    const COUNT_TOKENS_URL = 'https://api.anthropic.com/v1/messages/count_tokens';

    /** Máximo de tokens de salida por defecto en las peticiones de conversación. */
    const DEFAULT_MAX_TOKENS = 16000;

    /** Modelo por defecto: el más equilibrado entre velocidad, calidad y precio. */
    const DEFAULT_MODEL = 'claude-sonnet-5-5';

    /** Cabecera beta que habilita el parámetro `fallbacks: default`. */
    const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

    /**
     * Modelos que admiten el fallback del servidor. Con cualquier otro modelo la petición se envía
     * sin fallback, para no depender de cómo responda la API a un parámetro que el modelo no admite.
     */
    const FALLBACK_MODELS = [
        'claude-fable-5',
        'claude-fable-5-1',
        'claude-fable-5-2',
        'claude-mythos-5-1',
        'claude-mythos-5-2',
        'claude-opus-5',
        'claude-opus-5-5',
        'claude-sonnet-5-5',
    ];

    /** Endpoint de la API de archivos. */
    const FILES_URL = 'https://api.anthropic.com/v1/files';

    /** Endpoint de conversación (Messages API). */
    const MESSAGES_URL = 'https://api.anthropic.com/v1/messages';

    /** @var string API key de Anthropic, enviada como token Bearer. */
    protected $api_key;

    /** @var int Tiempo máximo de espera de cada petición, en segundos. */
    protected $timeout = 60;

    /** @var int Tokens consumidos en la última petición de conversación. */
    protected $total_tokens = 0;

    /**
     * Inicializa el cliente con la API key de Anthropic.
     *
     * @param string $api_key clave de la API; si está vacía se registra un error, pero el objeto se crea igualmente
     */
    public function __construct(string $api_key)
    {
        $this->api_key = $api_key;

        if (empty($this->api_key)) {
            Tools::log()->error('Anthropic API Key not found');
        }
    }

    /**
     * Envía una conversación al modelo y devuelve su respuesta como texto.
     *
     * Los mensajes con rol `system` se extraen de la conversación y se envían en el campo `system`.
     * Si el modelo rechaza la petición, o la API devuelve un error, se registra en el log y se devuelve
     * una cadena vacía. Si la respuesta se corta por alcanzar `$max_tokens`, se registra un aviso y se
     * devuelve el texto truncado.
     *
     * @param array $messages conversación en formato `[['role' => 'user', 'content' => '...'], ...]`;
     *                        `content` puede ser un texto o un array de bloques (texto, imagen, documento)
     * @param string $user identificador opaco del usuario final (`metadata.user_id`) para que Anthropic
     *                     detecte abusos; nunca un email, nombre o dato personal
     * @param string $model ID del modelo, por ejemplo `claude-haiku-5-5` o `claude-opus-5-5`
     * @param int $max_tokens máximo de tokens que puede generar el modelo en la respuesta
     *
     * @return string
     */
    public function chat(array $messages, string $user = '', string $model = self::DEFAULT_MODEL, int $max_tokens = self::DEFAULT_MAX_TOKENS): string
    {
        $params = $this->getMessagesParams($messages, $user, $model, $max_tokens);
        return $this->getResponseText($this->sendMessage($params));
    }

    /**
     * Envía una conversación al modelo forzando una respuesta JSON que cumple un esquema y la
     * devuelve decodificada como array.
     *
     * Devuelve un array vacío si la API devuelve un error, si el modelo rechaza la petición o si la
     * respuesta no es un JSON válido (por ejemplo, porque se cortó al alcanzar `$max_tokens`).
     *
     * El esquema se normaliza con normalizeSchema() antes de enviarlo, que añade el
     * `additionalProperties: false` que la API exige en cada objeto. La API rechaza con un error 400
     * los esquemas recursivos y estas restricciones, que el esquema no debe incluir: `minimum`,
     * `maximum`, `multipleOf`, `minLength`, `maxLength` y las restricciones complejas de arrays.
     *
     * @param array $messages conversación, con el mismo formato que en chat()
     * @param array $output_format formato de salida (`output_config.format`), por ejemplo
     *                             `['type' => 'json_schema', 'schema' => ['type' => 'object', ...]]`
     * @param string $user identificador opaco del usuario final, como en chat()
     * @param string $model ID del modelo
     * @param int $max_tokens máximo de tokens que puede generar el modelo en la respuesta
     *
     * @return array
     */
    public function chatJson(array $messages, array $output_format, string $user = '', string $model = self::DEFAULT_MODEL, int $max_tokens = self::DEFAULT_MAX_TOKENS): array
    {
        $params = $this->getMessagesParams($messages, $user, $model, $max_tokens);
        $params['output_config'] = ['format' => $this->normalizeSchema($output_format)];

        $text = $this->getResponseText($this->sendMessage($params));
        if ($text === '') {
            return [];
        }

        $data = json_decode($text, true);
        if (!is_array($data)) {
            Tools::log()->error('Anthropic messages error: invalid JSON response', ['response' => $text]);
            return [];
        }

        return $data;
    }

    /**
     * Cuenta los tokens de entrada que consumiría una conversación, sin enviarla al modelo ni
     * generar coste. Útil para estimar el precio o comprobar que cabe en el contexto del modelo.
     *
     * Solo cuenta la conversación y las instrucciones de sistema: el esquema de chatJson() también
     * consume tokens de entrada, así que para esas llamadas el resultado se queda algo corto.
     *
     * @param array $messages conversación, con el mismo formato que en chat()
     * @param string $model ID del modelo con el que se contarán los tokens
     *
     * @return int tokens de entrada, o 0 si hay error
     */
    public function countTokens(array $messages, string $model = self::DEFAULT_MODEL): int
    {
        [$chat, $system] = $this->splitSystemMessages($messages);
        $params = ['model' => $model, 'messages' => $chat];
        if (!empty($system)) {
            $params['system'] = $system;
        }

        $response = $this->prepareRequest(Http::post(self::COUNT_TOKENS_URL, json_encode($params)));
        if ($response->failed()) {
            $this->logError('count tokens', $response);
            return 0;
        }

        return (int)($response->json()['input_tokens'] ?? 0);
    }

    /**
     * Elimina un archivo de la API de archivos.
     *
     * @param string $id_file ID del archivo (`file_...`)
     *
     * @return bool
     */
    public function fileDelete(string $id_file): bool
    {
        $response = $this->prepareRequest(Http::delete(self::FILES_URL . '/' . $id_file));
        if ($response->failed()) {
            $this->logError('file delete', $response);
            return false;
        }

        return true;
    }

    /**
     * Devuelve la primera página de archivos subidos, con el tamaño de página por defecto de la API.
     * Cada elemento contiene `id`, `filename`, `mime_type`, `size_bytes` y `created_at`.
     *
     * @return array
     */
    public function fileList(): array
    {
        $response = $this->prepareRequest(Http::get(self::FILES_URL));
        if ($response->failed()) {
            $this->logError('file list', $response);
            return [];
        }

        return $response->json()['data'] ?? [];
    }

    /**
     * Devuelve los metadatos de un archivo: `id`, `filename`, `mime_type`, `size_bytes`, `created_at`
     * y `downloadable`.
     *
     * @param string $id_file ID del archivo (`file_...`)
     *
     * @return array
     */
    public function fileRead(string $id_file): array
    {
        $response = $this->prepareRequest(Http::get(self::FILES_URL . '/' . $id_file));
        if ($response->failed()) {
            $this->logError('file read', $response);
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Sube un archivo para usarlo en varias conversaciones sin volver a enviarlo. El `id` devuelto se
     * referencia en un bloque de contenido, por ejemplo
     * `['type' => 'document', 'source' => ['type' => 'file', 'file_id' => $id]]`.
     *
     * @param CURLFile $file archivo a subir, con su tipo MIME
     *
     * @return array metadatos del archivo creado, o array vacío si hay error
     */
    public function fileUpload(CURLFile $file): array
    {
        $request = Http::post(self::FILES_URL, ['file' => $file]);
        $response = $this->prepareRequest($request, 'multipart/form-data');
        if ($response->failed()) {
            $this->logError('file upload', $response);
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Devuelve los tokens consumidos en la última llamada a chat() o chatJson(): entrada, salida,
     * escritura y lectura de caché. Si actuó el fallback, incluye los tokens de todos los intentos.
     *
     * @return int
     */
    public function getTotalTokens(): int
    {
        return $this->total_tokens;
    }

    /**
     * Crea y devuelve una nueva instancia del cliente.
     *
     * @param string $api_key clave de la API de Anthropic
     *
     * @return self
     */
    public static function init(string $api_key): self
    {
        return new Anthropic($api_key);
    }

    /**
     * Añade un mensaje de sistema (instrucciones para el modelo) a la conversación.
     *
     * @param array $messages conversación, que se modifica por referencia
     * @param string $message instrucciones de sistema
     *
     * @return self
     */
    public function setSystemMessage(array &$messages, string $message): self
    {
        $messages[] = ['role' => 'system', 'content' => $message];

        return $this;
    }

    /**
     * Establece el tiempo máximo de espera de las peticiones.
     *
     * @param int $timeout segundos
     *
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Añade un mensaje de usuario a la conversación.
     *
     * @param array $messages conversación, que se modifica por referencia
     * @param string $message texto del usuario
     *
     * @return self
     */
    public function setUserMessage(array &$messages, string $message): self
    {
        $messages[] = ['role' => 'user', 'content' => $message];

        return $this;
    }

    /**
     * Construye los parámetros comunes de una petición de conversación: modelo, máximo de tokens,
     * mensajes, instrucciones de sistema, usuario y, si el modelo está en FALLBACK_MODELS, fallback.
     *
     * @param array $messages conversación, con posibles mensajes de sistema
     * @param string $user identificador opaco del usuario final; si está vacío no se envía
     * @param string $model ID del modelo
     * @param int $max_tokens máximo de tokens de salida
     *
     * @return array
     */
    private function getMessagesParams(array $messages, string $user, string $model, int $max_tokens): array
    {
        [$chat, $system] = $this->splitSystemMessages($messages);

        $params = [
            'model' => $model,
            'max_tokens' => $max_tokens,
            'messages' => $chat,
        ];

        if (in_array($model, self::FALLBACK_MODELS, true)) {
            $params['fallbacks'] = 'default';
        }

        if (!empty($system)) {
            $params['system'] = $system;
        }

        if ($user) {
            $params['metadata'] = ['user_id' => $user];
        }

        return $params;
    }

    /**
     * Extrae el texto de una respuesta de conversación uniendo sus bloques `text`. Se descartan los
     * bloques de razonamiento (`thinking`) y el texto anterior al último bloque `fallback`, que
     * pertenece a un modelo que dejó de responder por un rechazo.
     *
     * @param array $json respuesta decodificada de la API, o array vacío
     *
     * @return string
     */
    private function getResponseText(array $json): string
    {
        $text = '';
        foreach ($json['content'] ?? [] as $block) {
            if ($block['type'] === 'fallback') {
                $text = '';
                continue;
            }

            if ($block['type'] === 'text') {
                $text .= $block['text'];
            }
        }

        return $text;
    }

    /**
     * Calcula los tokens consumidos a partir del bloque `usage` de la respuesta. La API no devuelve
     * un total, así que se suman entrada, salida, escritura y lectura de caché. Si existe
     * `iterations` (por ejemplo, cuando actuó el fallback), se suman las de cada intento.
     *
     * @param array $usage bloque `usage` de la respuesta
     *
     * @return int
     */
    private function getUsageTokens(array $usage): int
    {
        $entries = empty($usage['iterations']) ? [$usage] : $usage['iterations'];

        $total = 0;
        foreach ($entries as $entry) {
            $total += ($entry['input_tokens'] ?? 0)
                + ($entry['output_tokens'] ?? 0)
                + ($entry['cache_creation_input_tokens'] ?? 0)
                + ($entry['cache_read_input_tokens'] ?? 0);
        }

        return $total;
    }

    /**
     * Registra en el log una petición fallida, con el código HTTP, el tipo y mensaje de error de la
     * API y el `request-id`, que es el dato que pide el soporte de Anthropic.
     *
     * @param string $action descripción breve de la operación, para el mensaje del log
     * @param Http $response petición fallida
     */
    private function logError(string $action, Http $response): void
    {
        $json = $response->json();
        Tools::log()->error('Anthropic ' . $action . ' error: ' . $response->status() . ' ' . $response->errorMessage(), [
            'type' => $json['error']['type'] ?? '',
            'message' => $json['error']['message'] ?? $response->body(),
            'request_id' => $response->header('request-id'),
        ]);
    }

    /**
     * Prepara un esquema JSON para que la API lo acepte. Hace dos ajustes, recorriendo el esquema de
     * forma recursiva.
     *
     * 1. Añade `additionalProperties: false` a cada nodo de tipo `object` que no lo tenga, porque la
     * API lo exige en todos los objetos y no admite otro valor. Así un esquema escrito como en
     * cualquier otra API funciona sin cambios. Solo se considera tipo del nodo un `type` de texto o
     * una lista de tipos, para no confundirlo con una propiedad que se llame `type`.
     *
     * 2. Convierte en objetos JSON los arrays vacíos que deben serlo. En PHP un array vacío no distingue entre lista y objeto, y json_encode() siempre lo
     * convierte en lista (`[]`). En JSON Schema hay claves cuyo valor es siempre un objeto, así que un
     * esquema escrito en PHP como `['type' => 'object', 'properties' => []]` se enviaría como
     * `{"type":"object","properties":[]}` y la API lo rechazaría con un error 400, cuando lo correcto
     * es `{"type":"object","properties":{}}`. Por eso se sustituyen por `new stdClass()` (que
     * json_encode() serializa como `{}`) los arrays vacíos de las claves `properties`,
     * `patternProperties`, `$defs` y `definitions`. El resto de claves no se modifica: `required` o
     * `enum` vacíos son listas legítimas y deben seguir siendo `[]`.
     *
     * Un subesquema vacío en otra clave (por ejemplo `'items' => []`) se envía tal cual, porque no se
     * puede saber si es una lista o un objeto: en ese caso el plugin debe pasar `new stdClass()`.
     *
     * @param array $schema esquema o formato de salida a normalizar
     *
     * @return array
     */
    private function normalizeSchema(array $schema): array
    {
        foreach ($schema as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            if (empty($value) && in_array($key, ['$defs', 'definitions', 'patternProperties', 'properties'], true)) {
                $schema[$key] = new stdClass();
                continue;
            }

            $schema[$key] = $this->normalizeSchema($value);
        }

        $type = $schema['type'] ?? null;
        $isObject = is_string($type)
            ? $type === 'object'
            : is_array($type) && array_is_list($type) && in_array('object', $type, true);
        if ($isObject && !array_key_exists('additionalProperties', $schema)) {
            $schema['additionalProperties'] = false;
        }

        return $schema;
    }

    /**
     * Añade a la petición las cabeceras comunes (versión de la API, tipo de contenido y API key) y
     * el tiempo de espera.
     *
     * La cabecera debe llamarse exactamente `Content-Type`: Http la consulta con ese nombre para
     * enviar los datos sin codificar cuando es `multipart/form-data`.
     *
     * @param Http $request petición a preparar
     * @param string $content_type tipo de contenido del cuerpo
     *
     * @return Http
     */
    private function prepareRequest(Http $request, string $content_type = 'application/json'): Http
    {
        return $request
            ->setHeader('anthropic-version', self::API_VERSION)
            ->setHeader('Content-Type', $content_type)
            ->setBearerToken($this->api_key)
            ->setTimeout($this->timeout);
    }

    /**
     * Envía una petición de conversación y devuelve la respuesta decodificada.
     *
     * Si los parámetros incluyen `fallbacks`, añade la cabecera beta que lo habilita.
     * Actualiza los tokens consumidos. Devuelve un array vacío si la API devuelve un error, si la
     * respuesta no es válida o si el modelo rechazó la petición (`stop_reason` = `refusal`); en ese
     * caso registra la categoría y la explicación del rechazo. Si la respuesta se cortó por
     * `max_tokens`, registra un aviso y la devuelve igualmente.
     *
     * @param array $params parámetros de la Messages API
     *
     * @return array
     */
    private function sendMessage(array $params): array
    {
        $this->total_tokens = 0;

        $request = Http::post(self::MESSAGES_URL, json_encode($params));
        $response = $this->prepareRequest($request);
        if (isset($params['fallbacks'])) {
            $response->setHeader('anthropic-beta', self::FALLBACK_BETA);
        }

        if ($response->failed()) {
            $this->logError('messages', $response);
            return [];
        }

        $json = $response->json();
        if (!is_array($json)) {
            Tools::log()->error('Anthropic messages error: invalid response. ' . $response->body());
            return [];
        }

        $this->total_tokens = $this->getUsageTokens($json['usage'] ?? []);

        $stopReason = $json['stop_reason'] ?? '';
        if ($stopReason === 'refusal') {
            Tools::log()->error('Anthropic messages refusal: ' . ($json['stop_details']['explanation'] ?? ''), [
                'category' => $json['stop_details']['category'] ?? '',
                'model' => $json['model'] ?? '',
                'request_id' => $response->header('request-id'),
            ]);
            return [];
        }

        if ($stopReason === 'max_tokens') {
            Tools::log()->warning('Anthropic messages warning: response truncated by max_tokens', [
                'max_tokens' => $params['max_tokens'],
                'model' => $json['model'] ?? '',
            ]);
        }

        return $json;
    }

    /**
     * Separa los mensajes de sistema del resto de la conversación, porque la API de Claude los recibe
     * en el campo `system` y no admite el rol `system` como primer mensaje.
     *
     * @param array $messages conversación completa
     *
     * @return array `[$chat, $system]`: mensajes sin los de sistema y bloques de texto para `system`
     */
    private function splitSystemMessages(array $messages): array
    {
        $chat = [];
        $system = [];
        foreach ($messages as $message) {
            if (($message['role'] ?? '') !== 'system') {
                $chat[] = $message;
                continue;
            }

            if (is_array($message['content'])) {
                $system = array_merge($system, $message['content']);
                continue;
            }

            $system[] = ['type' => 'text', 'text' => $message['content']];
        }

        return [$chat, $system];
    }
}
