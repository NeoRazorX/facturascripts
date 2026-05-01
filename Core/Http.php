<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core;

/**
 * Cliente HTTP minimalista basado en cURL con API fluida.
 *
 * Se usa siempre a través de los factories estáticos `Http::get()`, `Http::post()`, `Http::put()`,
 * `Http::patch()`, `Http::delete()` o `Http::postJson()`. La petición se construye de forma
 * encadenable (`setHeader()`, `setBearerToken()`, `setTimeout()`, ...) y se ejecuta de manera
 * perezosa: la primera llamada a un getter (`body()`, `status()`, `headers()`, `json()`, ...)
 * dispara `exec()` y cachea el resultado en la propia instancia. Llamar a getters posteriores
 * no lanza una nueva petición.
 *
 * Notas relevantes:
 * - Por defecto sigue redirecciones (`CURLOPT_FOLLOWLOCATION`) y timeout de 30 segundos.
 * - Las cabeceras de respuesta se almacenan en minúsculas para que `header()` sea case-insensitive.
 * - `getPostFields()` respeta el Content-Type: si es `multipart/form-data` se envía el array tal
 *   cual (cURL construye el body multipart); en otro caso se serializa con `http_build_query`.
 * - `ok()` solo considera 200/201/202 como éxito; el resto (incluido 204 No Content) cuentan como
 *   fallo. Si necesitas otra interpretación, usa `status()` directamente.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Http
{
    /** Cuerpo crudo de la respuesta tras `exec()`. @var string */
    protected $body;

    /** Mapa de opciones cURL que se aplicarán a la petición. @var array */
    private $curlOptions;

    /** Datos a enviar: array (se codifica según método y Content-Type) o string en bruto. @var mixed */
    protected $data;

    /** Mensaje de error devuelto por `curl_error()`, vacío si no hubo fallo a nivel de transporte. @var string */
    public $error;

    /** Indica si la petición ya se ha ejecutado, para evitar dispararla varias veces. @var bool */
    private $executed = false;

    /** Cabeceras de la petición saliente, ya formateadas como `"Clave: Valor"`. @var array */
    private $headers = [];

    /** Método HTTP de la petición (GET, POST, PUT, PATCH, DELETE). @var string */
    protected $method;

    /** Cabeceras de respuesta indexadas en minúsculas, cada una con sus valores como array. @var array */
    private $responseHeaders = [];

    /** Código HTTP de respuesta. 0 mientras no se haya ejecutado la petición. @var int */
    private $statusCode = 0;

    /** URL destino. Para GET/DELETE con `$data` array, los parámetros se añaden como query string al ejecutar. @var string */
    protected $url;

    /**
     * Construye una petición. Pensado para uso interno: en el código cliente, prefiérense los
     * factories estáticos (`Http::get()`, `Http::post()`, ...) que dejan el método explícito.
     *
     * @param string $method método HTTP en mayúsculas
     * @param string $url    URL destino
     * @param mixed  $data   array de campos o string en bruto a enviar
     */
    public function __construct(string $method, string $url, $data = [])
    {
        $this->method = $method;
        $this->url = $url;
        $this->data = $data;

        $this->curlOptions = [
            CURLOPT_AUTOREFERER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'FacturaScripts ' . Kernel::version()
        ];
    }

    /** Devuelve el cuerpo crudo de la respuesta, ejecutando la petición si aún no se había hecho. */
    public function body(): string
    {
        if (!$this->executed) {
            $this->exec();
        }

        return $this->body;
    }

    /**
     * Crea una petición DELETE. Si `$data` es array, sus claves se añaden como query string en la URL
     * (DELETE no lleva cuerpo). Si es string, se envía la URL tal cual y se ignora el dato.
     */
    public static function delete(string $url, $data = []): self
    {
        return new self('DELETE', $url, $data);
    }

    /** Devuelve el mensaje de error de cURL (cadena vacía si no hubo error). Ejecuta la petición si hace falta. */
    public function errorMessage(): string
    {
        if (!$this->executed) {
            $this->exec();
        }

        return $this->error;
    }

    /** Atajo de `!ok()`: true si la petición no terminó con un código 200/201/202. */
    public function failed(): bool
    {
        return !$this->ok();
    }

    /**
     * Crea una petición GET. Si `$data` es un array no vacío, se concatena como query string
     * a la URL al ejecutar la petición.
     */
    public static function get(string $url, $data = []): self
    {
        return new self('GET', $url, $data);
    }

    /**
     * Devuelve el primer valor de la cabecera de respuesta `$key` (case-insensitive), o cadena vacía si no existe.
     *
     * Si la cabecera apareció varias veces, se devuelve solo la primera ocurrencia. Para
     * obtenerlas todas, usar `headers()`.
     */
    public function header(string $key): string
    {
        if (!$this->executed) {
            $this->exec();
        }

        return array_key_exists(strtolower($key), $this->responseHeaders) ?
            $this->responseHeaders[strtolower($key)][0] :
            '';
    }

    /**
     * Devuelve todas las cabeceras de respuesta indexadas en minúsculas.
     *
     * Cada clave apunta a un array con todos los valores recibidos para esa cabecera (las que
     * aparecen una sola vez se devuelven igualmente como array de un elemento).
     */
    public function headers(): array
    {
        if (!$this->executed) {
            $this->exec();
        }

        return $this->responseHeaders;
    }

    /**
     * Decodifica el cuerpo de la respuesta como JSON.
     *
     * Si el cuerpo no es JSON válido, `json_decode` devuelve null sin lanzar excepción; el
     * llamador es responsable de comprobar el resultado.
     *
     * @param bool $associative true (por defecto) para arrays asociativos, false para objetos
     * @return mixed
     */
    public function json(bool $associative = true)
    {
        if (!$this->executed) {
            $this->exec();
        }

        return json_decode($this->body, $associative);
    }

    /** True si la respuesta tiene código 404. Ejecuta la petición si aún no se había ejecutado. */
    public function notFound(): bool
    {
        if (!$this->executed) {
            $this->exec();
        }

        return $this->statusCode === 404;
    }

    /**
     * True si la respuesta tiene código 200, 201 o 202.
     *
     * Otros 2xx (204 No Content, 206 Partial Content...) y todos los 3xx/4xx/5xx se consideran
     * fallo. Si tu integración necesita una interpretación distinta, usa `status()` directamente.
     */
    public function ok(): bool
    {
        if (!$this->executed) {
            $this->exec();
        }

        return in_array($this->statusCode, [200, 201, 202]);
    }

    /**
     * Crea una petición POST. Si `$data` es array, se enviará como `application/x-www-form-urlencoded`
     * salvo que se establezca un Content-Type distinto (multipart/form-data envía el array tal cual).
     */
    public static function post(string $url, $data = []): self
    {
        return new self('POST', $url, $data);
    }

    /** Atajo para POST con cuerpo JSON: serializa `$data` y fija la cabecera `Content-Type: application/json`. */
    public static function postJson(string $url, array $data = []): self
    {
        return self::post($url, json_encode($data))
            ->setHeader('Content-Type', 'application/json');
    }

    /** Crea una petición PATCH; el cuerpo se construye igual que en POST. */
    public static function patch(string $url, $data = []): self
    {
        return new self('PATCH', $url, $data);
    }

    /** Crea una petición PUT; el cuerpo se construye igual que en POST. */
    public static function put(string $url, $data = []): self
    {
        return new self('PUT', $url, $data);
    }

    /**
     * Guarda el cuerpo de la respuesta en `$filename`.
     *
     * Sólo escribe si el código de respuesta es exactamente 200; para 201/202 no guarda nada y
     * devuelve false. Devuelve también false si `file_put_contents` falla.
     */
    public function saveAs(string $filename): bool
    {
        if (!$this->executed) {
            $this->exec();
        }

        if ($this->statusCode !== 200) {
            return false;
        }

        return file_put_contents($filename, $this->body) !== false;
    }

    /** Añade una cabecera `Authorization: Bearer <token>` para autenticación OAuth/JWT. */
    public function setBearerToken(string $token): self
    {
        return $this->setHeader('Authorization', 'Bearer ' . $token);
    }

    /** Sobrescribe directamente una opción de cURL (constante `CURLOPT_*`). Usar con cuidado. */
    public function setCurlOption(int $option, $value): self
    {
        $this->curlOptions[$option] = $value;
        return $this;
    }

    /**
     * Define una cabecera de la petición.
     *
     * Internamente se almacena ya formateada (`"Clave: Valor"`) e indexada por el nombre original
     * de la cabecera, por lo que llamar dos veces con la misma clave (mismo case) sustituye el
     * valor anterior, pero usar otro case crearía una entrada distinta.
     */
    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $key . ': ' . $value;
        return $this;
    }

    /**
     * Sustituye por completo el conjunto de cabeceras de la petición.
     *
     * Espera el array ya en el formato interno (`["Clave" => "Clave: Valor"]`); si vas a fijarlas
     * una a una, usa `setHeader()`.
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    /** Establece el timeout total de la petición en segundos (atajo de `CURLOPT_TIMEOUT`). */
    public function setTimeout(int $timeout): self
    {
        return $this->setCurlOption(CURLOPT_TIMEOUT, $timeout);
    }

    /** Añade una cabecera `Token: <token>` (autenticación específica de algunas APIs internas). */
    public function setToken(string $token): self
    {
        return $this->setHeader('Token', $token);
    }

    /** Configura autenticación HTTP Basic mediante `CURLOPT_USERPWD`. */
    public function setUser(string $user, string $password): self
    {
        return $this->setCurlOption(CURLOPT_USERPWD, $user . ':' . $password);
    }

    /** Sustituye el User-Agent por defecto (que es `FacturaScripts <version>`). */
    public function setUserAgent(string $userAgent): self
    {
        return $this->setCurlOption(CURLOPT_USERAGENT, $userAgent);
    }

    /** Devuelve el código HTTP de la respuesta. Ejecuta la petición si aún no se había hecho. */
    public function status(): int
    {
        if (!$this->executed) {
            $this->exec();
        }

        return $this->statusCode;
    }

    /**
     * Ejecuta la petición cURL y rellena `body`, `statusCode`, `error` y `responseHeaders`.
     *
     * Se invoca de forma perezosa la primera vez que se llama a un getter; las llamadas
     * posteriores no relanzan la petición porque el flag `executed` queda a true incluso si
     * cURL devuelve error. Las cabeceras de respuesta se capturan mediante `CURLOPT_HEADERFUNCTION`
     * y se indexan en minúsculas, almacenando todos los valores cuando una cabecera aparece varias
     * veces (típico en `Set-Cookie`).
     */
    protected function exec(): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, $this->curlOptions);

        // añadimos las cabeceras
        if (!empty($this->headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }

        switch ($this->method) {
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (is_array($this->data)) {
                    curl_setopt($ch, CURLOPT_URL, $this->url . '?' . http_build_query($this->data));
                    break;
                }
                curl_setopt($ch, CURLOPT_URL, $this->url);
                break;

            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_URL, $this->url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getPostFields());
                break;

            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_URL, $this->url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getPostFields());
                break;

            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_URL, $this->url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getPostFields());
                break;

            default:
                // GET
                if (is_array($this->data) && !empty($this->data)) {
                    curl_setopt($ch, CURLOPT_URL, $this->url . '?' . http_build_query($this->data));
                    break;
                }
                curl_setopt($ch, CURLOPT_URL, $this->url);
                break;
        }

        // guardamos las cabeceras de respuesta
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) {
                return $len;
            }
            $this->responseHeaders[strtolower(trim($header[0]))][] = trim($header[1]);
            return $len;
        });

        $this->body = curl_exec($ch);
        $this->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->error = curl_error($ch);

        $this->executed = true;
    }

    /**
     * Prepara el cuerpo a enviar para POST/PUT/PATCH según el Content-Type configurado.
     *
     * Si la cabecera `Content-Type` es `multipart/form-data`, se devuelven los datos sin codificar
     * para que cURL construya el cuerpo multipart automáticamente (necesario para subir ficheros
     * con `CURLFile`). Si no, los arrays se codifican con `http_build_query` y los strings se
     * envían tal cual (caso típico al enviar JSON ya serializado, como hace `postJson()`).
     */
    protected function getPostFields()
    {
        // si el Content-Type es multipart/form-data, devolvemos los datos sin codificar
        if (stripos($this->headers['Content-Type'] ?? '', 'multipart/form-data') !== false) {
            return $this->data;
        }

        // si data es un array, lo codificamos
        return is_array($this->data) ? http_build_query($this->data) : $this->data;
    }
}
