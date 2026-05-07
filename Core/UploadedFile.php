<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Representa un fichero subido por el cliente a través de un formulario multipart/form-data.
 *
 * Esta clase es el equivalente interno de FacturaScripts a la `UploadedFile` de Symfony: envuelve
 * una entrada de `$_FILES` y añade utilidades de validación (extensiones bloqueadas, comprobación
 * de MIME real, verificación de imágenes mediante GD) y de movimiento del fichero temporal a su
 * destino final.
 *
 * Por seguridad bloquea de raíz cualquier extensión asociada a ejecución de PHP en el servidor
 * (php, phar, phtml, etc.), incluso si el upload en sí es correcto: `isValid()` devolverá false
 * para esos ficheros y `getErrorMessage()` mostrará un mensaje específico.
 *
 * El flag `$test` permite usar la clase en tests sin pasar por `is_uploaded_file()` ni
 * `move_uploaded_file()` (que requieren un upload HTTP real): cuando está activo se usan
 * `rename()` y se asume que el fichero es válido a efectos de subida.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class UploadedFile
{
    /** Extensiones bloqueadas para evitar la ejecución de código PHP servido desde uploads. */
    private const BLOCKED_EXTENSIONS = ['phar', 'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'pht', 'phtml', 'phps'];

    /** Extensiones admitidas como imagen válida en `isValidImage()`. */
    private const IMAGE_EXTENSIONS = ['gif', 'jpeg', 'jpg', 'png', 'webp'];

    /** Tipos MIME admitidos como imagen válida en `isValidImage()`. */
    private const IMAGE_MIME_TYPES = ['image/gif', 'image/jpeg', 'image/png', 'image/webp'];

    /**
     * Código de error de la subida. Coincide con las constantes UPLOAD_ERR_* de PHP
     * (UPLOAD_ERR_OK, UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_NO_FILE, etc.).
     *
     * @var int
     */
    public $error;

    /** Nombre original del fichero tal y como lo envió el cliente (no se debe confiar en él). @var string */
    public $name;

    /** Tamaño del fichero en bytes según lo reportado por PHP. @var int */
    public $size;

    /**
     * Si es true, la instancia se trata como un upload simulado: se omite la comprobación
     * `is_uploaded_file()` y los movimientos se hacen con `rename()`. Pensado solo para tests.
     *
     * @var bool
     */
    public $test = false;

    /** Ruta temporal donde PHP ha guardado el fichero subido. @var string */
    public $tmp_name;

    /** Tipo MIME declarado por el cliente (no fiable; usar `getMimeType()` para el real). @var string */
    public $type;

    /**
     * Construye la instancia a partir de un array compatible con una entrada de `$_FILES`.
     *
     * Solo se asignan claves que coincidan con propiedades públicas existentes; el resto se ignoran.
     * Si un valor llega como array (caso típico de uploads múltiples donde PHP entrega arrays
     * paralelos), se toma el primer elemento, de modo que la instancia siempre representa
     * un único fichero.
     *
     * @param array $data datos en formato `$_FILES['campo']` (o un subárbol equivalente)
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                if (is_array($value)) {
                    $value = $value[0];
                }
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Devuelve la extensión del nombre original del fichero (sin el punto), o cadena vacía si no hay nombre.
     *
     * Se obtiene del nombre proporcionado por el cliente, por lo que no garantiza que el contenido
     * real coincida con esa extensión. Para validar que un fichero es realmente una imagen usar
     * `isValidImage()`, que combina extensión, MIME real y decodificación con GD.
     */
    public function extension(): string
    {
        return is_null($this->name) ?
            '' :
            pathinfo($this->name, PATHINFO_EXTENSION);
    }

    /**
     * Tipo MIME declarado por el cliente en la cabecera del formulario.
     *
     * No es fiable: el cliente puede manipularlo. Usar `getMimeType()` para obtener el MIME
     * detectado a partir del contenido real del fichero.
     */
    public function getClientMimeType(): string
    {
        return $this->type ?? '';
    }

    /**
     * @return string
     * @deprecated replaced by extension() method
     */
    public function getClientOriginalExtension(): string
    {
        return $this->extension();
    }

    /** Nombre original del fichero tal como lo envió el cliente, o cadena vacía si no hay. */
    public function getClientOriginalName(): string
    {
        return $this->name ?? '';
    }

    /**
     * Devuelve un mensaje legible describiendo el estado de la subida.
     *
     * Si el fichero tiene una extensión bloqueada, se devuelve un mensaje específico aunque la
     * subida en sí no haya tenido errores: la comprobación de extensión bloqueada tiene prioridad
     * sobre el código `UPLOAD_ERR_*`.
     */
    public function getErrorMessage(): string
    {
        if ($this->hasBlockedExtension()) {
            return 'Executable PHP-related files are not allowed.';
        }

        return match ($this->error) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            default => 'Unknown upload error.',
        };
    }

    /**
     * Tamaño máximo de subida permitido por la configuración de PHP, en bytes.
     *
     * Es el menor entre `post_max_size` y `upload_max_filesize`. Si alguna de las dos directivas
     * está vacía o a 0 (ilimitado), se sustituye por PHP_INT_MAX para que no haga ganar a la
     * comparación incorrectamente. El valor devuelto siempre es un entero.
     */
    public static function getMaxFilesize(): int
    {
        $postMax = self::parseFilesize(ini_get('post_max_size'));
        $uploadMax = self::parseFilesize(ini_get('upload_max_filesize'));

        return min($postMax ?: PHP_INT_MAX, $uploadMax ?: PHP_INT_MAX);
    }

    /**
     * Tipo MIME real del fichero, detectado leyendo el contenido del fichero temporal con
     * `mime_content_type()`. Devuelve cadena vacía si no hay fichero temporal o no se puede leer.
     *
     * A diferencia de `getClientMimeType()`, este valor no se puede falsificar desde el cliente.
     */
    public function getMimeType(): string
    {
        if (is_null($this->tmp_name) || false === is_file($this->tmp_name)) {
            return '';
        }

        $mime = mime_content_type($this->tmp_name);
        return is_string($mime) ? $mime : '';
    }

    /** Ruta del fichero temporal donde PHP guardó el upload, o cadena vacía si no hay. */
    public function getPathname(): string
    {
        return $this->tmp_name ?? '';
    }

    /** Alias de `getPathname()` por compatibilidad con la API de Symfony. */
    public function getRealPath(): string
    {
        return $this->getPathname();
    }

    /** Tamaño del fichero en bytes según lo reportado por PHP en la subida. */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Indica si el fichero proviene realmente de un upload HTTP.
     *
     * En modo `$test` siempre devuelve true, lo que permite simular subidas en pruebas
     * unitarias sin necesidad de que el fichero haya pasado por el SAPI de PHP.
     */
    public function isUploaded(): bool
    {
        return $this->test || (!is_null($this->tmp_name) && is_uploaded_file($this->tmp_name));
    }

    /**
     * Comprueba que el fichero es seguro y se ha subido correctamente.
     *
     * Para ser válido debe cumplir las tres condiciones: extensión no bloqueada, código de error
     * `UPLOAD_ERR_OK` y haber sido realmente subido (`isUploaded()`).
     */
    public function isValid(): bool
    {
        return false === $this->hasBlockedExtension() &&
            $this->error === UPLOAD_ERR_OK &&
            $this->isUploaded();
    }

    /**
     * Comprueba si el fichero es una imagen válida y segura.
     *
     * La validación es estricta y se hace en cuatro pasos: subida correcta (`isValid()`),
     * extensión en la lista blanca, MIME real (no el del cliente) en la lista blanca y, si la
     * extensión GD está disponible, decodificación con `imagecreatefromstring()` para confirmar
     * que el contenido es realmente una imagen procesable. Si GD no está cargada, se acepta la
     * imagen tras superar las validaciones previas.
     */
    public function isValidImage(): bool
    {
        if (false === $this->isValid()) {
            return false;
        }

        if (false === in_array(strtolower($this->extension()), self::IMAGE_EXTENSIONS, true)) {
            return false;
        }

        if (false === in_array($this->getMimeType(), self::IMAGE_MIME_TYPES, true)) {
            return false;
        }

        if (false === function_exists('imagecreatefromstring')) {
            return true;
        }

        $contents = @file_get_contents($this->tmp_name);
        if (false === $contents) {
            return false;
        }

        $image = @imagecreatefromstring($contents);
        if (false === $image) {
            return false;
        }

        return true;
    }

    /**
     * Mueve el fichero subido al directorio `$destiny` con el nombre `$destinyName`.
     *
     * Devuelve false si el fichero no es válido (`isValid()`), sin tocar nada. Si `$destiny`
     * no termina en separador de directorio se añade uno, de modo que el llamador no necesita
     * preocuparse por la barra final. En modo `$test` se usa `rename()`; en producción se usa
     * `move_uploaded_file()`, que solo acepta ficheros realmente subidos vía HTTP.
     *
     * @param string $destiny     directorio destino (con o sin separador final)
     * @param string $destinyName nombre que tendrá el fichero en destino
     * @return bool true si el movimiento se realizó correctamente
     */
    public function move(string $destiny, string $destinyName): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if (substr($destiny, -1) !== DIRECTORY_SEPARATOR) {
            $destiny .= DIRECTORY_SEPARATOR;
        }

        return $this->test ?
            rename($this->tmp_name, $destiny . $destinyName) :
            move_uploaded_file($this->tmp_name, $destiny . $destinyName);
    }

    /**
     * Mueve el fichero subido a la ruta completa `$targetPath` (incluyendo nombre de fichero).
     *
     * Devuelve false si el fichero no es válido. En modo `$test` usa `rename()`; en producción
     * usa `move_uploaded_file()`. A diferencia de `move()`, aquí el llamador es responsable de
     * construir la ruta completa.
     */
    public function moveTo(string $targetPath): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        return $this->test ?
            rename($this->tmp_name, $targetPath) :
            move_uploaded_file($this->tmp_name, $targetPath);
    }

    /**
     * Convierte un valor de configuración tipo `post_max_size` ("8M", "1G", "512K", etc.) a bytes.
     *
     * Replica la lógica de PHP para parsear estos valores: la parte numérica admite prefijos
     * de base (0x para hex, 0 para octal) y el sufijo final (k/m/g/t) se aplica como múltiplo
     * de 1024 acumulativo mediante caída en cascada del switch. Devuelve 0 si la cadena está vacía.
     */
    private static function parseFilesize(string $size): int
    {
        if ('' === $size) {
            return 0;
        }

        $size = strtolower($size);

        $max = ltrim($size, '+');
        if (str_starts_with($max, '0x')) {
            $max = intval($max, 16);
        } elseif (str_starts_with($max, '0')) {
            $max = intval($max, 8);
        } else {
            $max = (int)$max;
        }

        switch (substr($size, -1)) {
            case 't':
                $max *= 1024;
            // no break
            case 'g':
                $max *= 1024;
            // no break
            case 'm':
                $max *= 1024;
            // no break
            case 'k':
                $max *= 1024;
        }

        return $max;
    }

    /** Indica si la extensión del fichero está en la lista negra de extensiones ejecutables como PHP. */
    private function hasBlockedExtension(): bool
    {
        return in_array(strtolower($this->extension()), self::BLOCKED_EXTENSIONS, true);
    }
}
