<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\Model\Settings;

/**
 * Una clase con funciones útiles para el desarrollo de FacturaScripts.
 */
class Tools
{
    const ASCII = [
        'Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z', 'Č' => 'C', 'č' => 'c', 'Ć' => 'C',
        'ć' => 'c', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U',
        'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i',
        'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'ü' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', 'Ŕ' => 'R',
        'ŕ' => 'r'
    ];
    const DATE_STYLE = 'd-m-Y';
    const DATETIME_STYLE = 'd-m-Y H:i:s';
    const HOUR_STYLE = 'H:i:s';
    const HTML_CHARS = ['<', '>', '"', "'"];
    const HTML_REPLACEMENTS = ['&lt;', '&gt;', '&quot;', '&#39;'];

    /** @var array */
    private static $settings;

    /**
     * Convierte los caracteres especiales de una cadena en sus equivalentes ASCII.
     *
     * @param string $text La cadena de texto que se desea convertir.
     *
     * @return string La cadena de texto con los caracteres especiales convertidos a ASCII.
     */
    public static function ascii(string $text): string
    {
        return strtr($text, self::ASCII);
    }

    /**
     * Convierte un tamaño en bytes a una representación legible con unidades apropiadas.
     *
     * Esta función toma un tamaño en bytes y lo convierte en una cadena legible, utilizando
     * las unidades GB, MB, KB o bytes, dependiendo del tamaño. También permite especificar
     * el número de decimales a mostrar en la salida.
     *
     * @param int|float $size El tamaño en bytes que se desea convertir.
     * @param int $decimals El número de decimales a mostrar (por defecto es 2).
     *
     * @return string Una cadena que representa el tamaño en la unidad más adecuada (GB, MB, KB o bytes).
     */
    public static function bytes($size, int $decimals = 2): string
    {
        if ($size >= 1073741824) {
            return self::number($size / 1073741824, $decimals) . ' GB';
        } elseif ($size >= 1048576) {
            return self::number($size / 1048576, $decimals) . ' MB';
        } elseif ($size >= 1024) {
            return self::number($size / 1024, $decimals) . ' KB';
        } elseif ($size > 1) {
            return self::number($size, $decimals) . ' bytes';
        } elseif ($size == 1) {
            return self::number(1, $decimals) . ' byte';
        }

        return self::number(0, $decimals) . ' bytes';
    }

    /**
     * Obtiene el valor de la configuración basada en una clave dada.
     * Normalmente estas contantes se definen en el config.php
     *
     * Esta función busca una constante de configuración que coincida con una clave
     * específica en tres posibles formas: la clave tal cual, la clave en mayúsculas,
     * y la clave en mayúsculas precedida por 'FS_'. Si alguna de estas constantes está
     * definida, retorna su valor. De lo contrario, retorna un valor por defecto.
     *
     * @param string $key La clave de la configuración que se desea obtener.
     * @param mixed $default El valor por defecto que se retornará si no se encuentra ninguna constante (por defecto es null).
     *
     * @return mixed El valor de la constante encontrada, o el valor por defecto si no se encuentra ninguna.
     */
    public static function config(string $key, $default = null)
    {
        $constants = [$key, strtoupper($key), 'FS_' . strtoupper($key)];
        foreach ($constants as $constant) {
            if (defined($constant)) {
                return constant($constant);
            }
        }

        return $default;
    }

    /**
     * Formatea una fecha según el estilo de fecha definido en la clase.
     *
     * Esta función devuelve una fecha formateada de acuerdo con el formato
     * de fecha predefinido (`self::DATE_STYLE`). Si no se proporciona una
     * fecha, se utiliza la fecha y hora actual.
     *
     * @param string|null $date La fecha a formatear en formato de cadena (opcional). Si es null o está vacía, se usa la fecha actual.
     *
     * @return string La fecha formateada según el estilo definido.
     */
    public static function date(?string $date = null): string
    {
        return empty($date) ? date(self::DATE_STYLE) : date(self::DATE_STYLE, strtotime($date));
    }

    /**
     * Realiza una operación sobre una fecha y devuelve el resultado formateado.
     *
     * Esta función toma una fecha y una operación (como "+1 day", "-2 months", etc.),
     * aplica la operación a la fecha dada, y devuelve la nueva fecha formateada
     * según el estilo de fecha definido (`self::DATE_STYLE`).
     *
     * @param string $date La fecha inicial en formato de cadena.
     * @param string $operation La operación a realizar sobre la fecha (ej. "+1 day", "-2 weeks").
     *
     * @return string La fecha resultante después de aplicar la operación, formateada según el estilo definido.
     */
    public static function dateOperation(string $date, string $operation): string
    {
        return date(self::DATE_STYLE, strtotime($date . ' ' . $operation));
    }

    /**
     * Formatea una fecha y hora según el estilo de fecha y hora definido en la clase.
     *
     * Esta función devuelve una fecha y hora formateada de acuerdo con el formato
     * predefinido (`self::DATETIME_STYLE`). Si no se proporciona una fecha, se utiliza
     * la fecha y hora actual.
     *
     * @param string|null $date La fecha y hora a formatear en formato de cadena (opcional). Si es null o está vacía, se usa la fecha y hora actual.
     *
     * @return string La fecha y hora formateadas según el estilo definido.
     */
    public static function dateTime(?string $date = null): string
    {
        return empty($date) ? date(self::DATETIME_STYLE) : date(self::DATETIME_STYLE, strtotime($date));
    }

    /**
     * Realiza una operación sobre una fecha y hora, y devuelve el resultado formateado.
     *
     * Esta función toma una fecha y hora, y una operación (como "+1 hour", "-30 minutes", etc.),
     * aplica la operación a la fecha y hora dadas, y devuelve la nueva fecha y hora formateadas
     * según el estilo de fecha y hora definido (`self::DATETIME_STYLE`).
     *
     * @param string $date La fecha y hora inicial en formato de cadena.
     * @param string $operation La operación a realizar sobre la fecha y hora (ej. "+1 hour", "-30 minutes").
     *
     * @return string La fecha y hora resultantes después de aplicar la operación, formateadas según el estilo definido.
     */
    public static function dateTimeOperation(string $date, string $operation): string
    {
        return date(self::DATETIME_STYLE, strtotime($date . ' ' . $operation));
    }

    /**
     * Corrige el texto HTML reemplazando secuencias específicas de HTML por sus caracteres correspondientes.
     *
     * Reemplaza las secuencias HTML definidas en `self::HTML_REPLACEMENTS`('&lt;', '&gt;', '&quot;', '&#39;')
     * con los caracteres correspondientes definidos en `self::HTML_CHARS`('<', '>', '"', "'").
     *
     * @param string|null $text El texto HTML a corregir. Puede ser nulo.
     *
     * @return string|null El texto corregido, o nulo si el texto de entrada es nulo.
     */
    public static function fixHtml(?string $text = null): ?string
    {
        return $text === null ?
            null :
            str_replace(self::HTML_REPLACEMENTS, self::HTML_CHARS, trim($text));
    }

    public static function floatcmp($f1, $f2, $precision = 10, $round = false): bool
    {
        if ($round || false === function_exists('bccomp')) {
            return abs($f1 - $f2) < 6 / 10 ** ($precision + 1);
        }

        return bccomp((string)$f1, (string)$f2, $precision) === 0;
    }

    /**
     * Construye una ruta de carpeta a partir de los nombres de carpetas proporcionados.
     *
     * @param string ...$folders Nombres de carpetas que se deben concatenar.
     *
     * @return string La ruta de la carpeta resultante.
     */
    public static function folder(...$folders): string
    {
        if (empty($folders)) {
            return self::config('folder') ?? '';
        }

        // eliminamos barras al incio y al final
        $folders = array_map(function($folder) {
            return ltrim(rtrim($folder, '/\\'), '/\\');
        }, $folders);

        array_unshift($folders, self::config('folder'));
        return implode(DIRECTORY_SEPARATOR, $folders);
    }

    /**
     * Verifica si una carpeta existe y, si no existe, la crea.
     *
     * @param string $folder La ruta de la carpeta a verificar o crear.
     *
     * @return bool `true` si la carpeta existe o se creó exitosamente, `false` en caso contrario.
     */
    public static function folderCheckOrCreate(string $folder): bool
    {
        return is_dir($folder) || mkdir($folder, 0777, true);
    }

    /**
     * Copia el contenido de una carpeta a otra, incluyendo subcarpetas y archivos.
     *
     * @param string $src La ruta de la carpeta de origen que se debe copiar.
     * @param string $dst La ruta de la carpeta de destino donde se copiarán los archivos.
     *
     * @return bool `true` si la operación de copia se completó exitosamente.
     */
    public static function folderCopy(string $src, string $dst): bool
    {
        static::folderCheckOrCreate($dst);

        $folder = opendir($src);
        while (false !== ($file = readdir($folder))) {
            if ($file === '.' || $file === '..') {
                continue;
            } elseif (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
                static::folderCopy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
            } else {
                copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
            }
        }

        closedir($folder);
        return true;
    }

    /**
     * Elimina una carpeta y su contenido recursivamente.
     *
     * @param string $folder La ruta de la carpeta o archivo que se debe eliminar.
     *
     * @return bool `true` si la carpeta o archivo fue eliminado exitosamente, `false` en caso contrario.
     */
    public static function folderDelete(string $folder): bool
    {
        if (is_dir($folder) && false === is_link($folder)) {
            $files = array_diff(scandir($folder), ['.', '..']);
            foreach ($files as $file) {
                self::folderDelete($folder . DIRECTORY_SEPARATOR . $file);
            }

            return rmdir($folder);
        }

        return !file_exists($folder) || unlink($folder);
    }

    /**
     * Calcula el tamaño total de una carpeta y su contenido, excluyendo archivos y carpetas especificados.
     *
     * @param string $folder La ruta de la carpeta cuyo tamaño se desea calcular.
     * @param array $exclude Lista de archivos y carpetas a excluir del cálculo. Por defecto, incluye `.DS_Store` y `.well-known`.
     *
     * @return int El tamaño total de la carpeta y su contenido en bytes.
     */
    public static function folderSize(string $folder, array $exclude = ['.DS_Store', '.well-known']): int
    {
        $size = 0;
        $scan = scandir($folder, SCANDIR_SORT_ASCENDING);
        if (false === is_array($scan)) {
            return $size;
        }

        $exclude[] = '.';
        $exclude[] = '..';
        $files = array_diff($scan, $exclude);
        foreach ($files as $file) {
            $newFile = $folder . DIRECTORY_SEPARATOR . $file;
            if (is_dir($newFile)) {
                $size += static::folderSize($newFile, $exclude);
            } else {
                $size += filesize($newFile);
            }
        }

        return $size;
    }

    /**
     * Escanea el contenido de una carpeta y opcionalmente sus subcarpetas, excluyendo archivos y carpetas especificados.
     *
     * @param string $folder La ruta de la carpeta que se debe escanear.
     * @param bool $recursive Indica si se debe realizar una búsqueda recursiva en las subcarpetas. Por defecto es `false`.
     * @param array $exclude Lista de archivos y carpetas a excluir del resultado. Por defecto incluye `.DS_Store` y `.well-known`.
     *
     * @return array Un array con los nombres de archivos y carpetas encontrados, incluyendo rutas relativas si se busca recursivamente.
     */
    public static function folderScan(string $folder, bool $recursive = false, array $exclude = ['.DS_Store', '.well-known']): array
    {
        $scan = scandir($folder, SCANDIR_SORT_ASCENDING);
        if (false === is_array($scan)) {
            return [];
        }

        $exclude[] = '.';
        $exclude[] = '..';
        $rootFolder = array_diff($scan, $exclude);
        if (false === $recursive) {
            return $rootFolder;
        }

        $result = [];
        foreach ($rootFolder as $item) {
            $newItem = $folder . DIRECTORY_SEPARATOR . $item;
            $result[] = $item;
            if (is_dir($newItem)) {
                foreach (static::folderScan($newItem, true, $exclude) as $item2) {
                    $result[] = $item . DIRECTORY_SEPARATOR . $item2;
                }
            }
        }

        return $result;
    }

    /**
     * Obtiene la hora actual o la hora de una fecha específica en formato determinado.
     *
     * @param string|null $date La fecha y hora a formatear. Si se proporciona, se usa para obtener la hora correspondiente. Si es nulo o vacío, se usa la hora actual.
     *
     * @return string La hora formateada en el formato especificado por `self::HOUR_STYLE`.
     */
    public static function hour(?string $date = null): string
    {
        return empty($date) ? date(self::HOUR_STYLE) : date(self::HOUR_STYLE, strtotime($date));
    }

    /**
     * Crea una instancia de `Translator` para gestionar la traducción en el idioma especificado.
     *
     * @param string|null $lang El código del idioma para la traducción. Si se proporciona, se usa para configurar el traductor. Si es nulo, se usa un valor predeterminado.
     *
     * @return Translator Una instancia de `Translator` configurada con el idioma especificado.
     */
    public static function lang(?string $lang = ''): Translator
    {
        return new Translator($lang);
    }

    /**
     * Crea una instancia de `MiniLog` para registrar mensajes, con soporte para traducción.
     *
     * @param string $channel El nombre del canal de registro. Si se proporciona, se usa para identificar el canal de registro en `MiniLog`.
     *
     * @return MiniLog Una instancia de `MiniLog` configurada con el canal y el traductor.
     */
    public static function log(string $channel = ''): MiniLog
    {
        $translator = new Translator();
        return new MiniLog($channel, $translator);
    }

    /**
     * Formatea un número como una cadena de texto representando una cantidad de dinero con un símbolo de moneda.
     *
     * @param float|null $number El número que se debe formatear. Si es nulo, se mostrará `0`.
     * @param string $coddivisa El código de la divisa para obtener el símbolo. Por defecto es una cadena vacía.
     * @param int|null $decimals El número de decimales a mostrar. Si es nulo, se usa el valor de configuración.
     *
     * @return string La cantidad de dinero formateada con el símbolo de la divisa.
     */
    public static function money(?float $number, string $coddivisa = '', ?int $decimals = null): string
    {
        if (empty($coddivisa)) {
            $coddivisa = self::settings('default', 'coddivisa', '');
        }
        if ($decimals === null) {
            $decimals = self::settings('default', 'decimals', 2);
        }

        $symbol = Divisas::get($coddivisa)->simbolo;
        $currencyPosition = self::settings('default', 'currency_position', 'right');
        return $currencyPosition === 'right' ?
            self::number($number, $decimals) . ' ' . $symbol :
            $symbol . ' ' . self::number($number, $decimals);
    }

    /**
     * Elimina los caracteres HTML de un texto reemplazándolos con sus correspondientes entidades HTML.
     *
     * Reemplaza los caracteres HTML definidos en `self::HTML_CHARS`('<', '>', '"', "'")
     * con sus correspondientes representaciones en `self::HTML_REPLACEMENTS`('&lt;', '&gt;', '&quot;', '&#39;').
     *
     * @param string|null $text El texto del que se deben eliminar los caracteres HTML. Puede ser nulo.
     *
     * @return string|null El texto con los caracteres HTML reemplazados, o `null` si el texto original era nulo.
     */
    public static function noHtml(?string $text): ?string
    {
        return $text === null ?
            null :
            str_replace(self::HTML_CHARS, self::HTML_REPLACEMENTS, trim($text));
    }

    /**
     * Formatea un número a una cadena con la configuración de decimales y separadores especificados.
     *
     * @param float|null $number El número que se debe formatear. Si es nulo, se usa 0.
     * @param int|null $decimals El número de decimales que se deben mostrar. Si es nulo, se usa el valor de configuración.
     *
     * @return string El número formateado como una cadena.
     */
    public static function number(?float $number, ?int $decimals = null): string
    {
        if ($decimals === null) {
            $decimals = self::settings('default', 'decimals', 2);
        }

        // cargamos la configuración
        $decimalSeparator = self::settings('default', 'decimal_separator', '.');
        $thousandsSeparator = self::settings('default', 'thousands_separator', ' ');

        return number_format($number ?? 0, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Genera una cadena aleatoria para usar como contraseña.
     *
     * Esta función crea una contraseña de longitud especificada usando un conjunto de caracteres alfanuméricos y símbolos especiales.
     *
     * @param int $length La longitud deseada para la contraseña. Por defecto es 10.
     *
     * @return string La contraseña generada.
     */
    public static function password(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.+-*¿?¡!#$%&/()=;:_,<>@';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Obtiene el valor de una configuración específica de un grupo, con un valor predeterminado si no está definido.
     *
     * @param string $group El nombre del grupo de configuración.
     * @param string $key La clave dentro del grupo de configuración.
     * @param mixed $default Valor predeterminado a devolver si la clave no está definida. Por defecto es `null`.
     *
     * @return mixed El valor de la clave en el grupo de configuración, o el valor predeterminado si la clave no está definida.
     */
    public static function settings(string $group, string $key, $default = null)
    {
        // cargamos las opciones si no están cargadas
        if (null === self::$settings) {
            self::settingsLoad();
        }

        // si no tenemos la clave, añadimos el valor predeterminado
        if (!isset(self::$settings[$group][$key])) {
            self::$settings[$group][$key] = $default;
        }

        return self::$settings[$group][$key];
    }

    /**
     * Limpia la configuración almacenada y borra el caché de configuración.
     *
     * @return void
     */
    public static function settingsClear(): void
    {
        Cache::delete('tools-settings');
        self::$settings = null;
    }

    /**
     * Guarda la configuración actual en la base de datos.
     *
     * @return bool `true` si todos los grupos de configuración se guardan correctamente, `false` en caso contrario.
     */
    public static function settingsSave(): bool
    {
        foreach (self::$settings as $key => $properties) {
            $model = new Settings();
            $model->name = $key;
            $model->properties = $properties;
            if (false === $model->save()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Establece un valor en la configuración de un grupo específico.
     *
     * @param string $group El nombre del grupo de configuración al que se debe asignar el valor.
     * @param string $key La clave dentro del grupo de configuración donde se debe asignar el valor.
     *
     * @param mixed $value El valor que se debe asignar a la clave dentro del grupo.
     */
    public static function settingsSet(string $group, string $key, $value): void
    {
        // cargamos las opciones si no están cargadas
        if (null === self::$settings) {
            self::settingsLoad();
        }

        // asignamos el valor
        self::$settings[$group][$key] = $value;
    }

    /**
     * Obtiene la URL base del sitio web.
     *
     * @return string La URL base del sitio web.
     */
    public static function siteUrl(): string
    {
        $url = self::settings('default', 'site_url', '');
        if (!empty($url)) {
            return $url;
        }

        if (false === array_key_exists('HTTP_HOST', $_SERVER)) {
            return 'http://localhost';
        }

        $url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $url .= '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        return substr($url, 0, strrpos($url, '/'));
    }

    /**
     * Convierte un texto en un slug amigable para URLs.
     *
     * @param string $text El texto que se debe convertir en slug.
     * @param string $separator El carácter que se usará para reemplazar los espacios y caracteres no alfanuméricos. Por defecto es '-'.
     * @param int $maxLength La longitud máxima del slug. Si es 0, no se aplica límite. Por defecto es 0.
     *
     * @return string El slug generado.
     */
    public static function slug(string $text, string $separator = '-', int $maxLength = 0): string
    {
        $text = self::ascii($text);
        $text = preg_replace('/[^A-Za-z0-9]+/', $separator, $text);
        $text = preg_replace('/' . $separator . '{2,}/', $separator, $text);
        $text = trim($text, $separator);
        $text = strtolower($text);
        return $maxLength > 0 ?
            substr($text, 0, $maxLength) :
            $text;
    }

    /**
     * Genera una cadena de caracteres aleatorios de una longitud especificada.
     *
     * Esta función crea una cadena de caracteres aleatorios usando números y letras mayúsculas y minúsculas.
     * La longitud de la cadena generada es determinada por el parámetro `$length`.
     *
     * @param int $length La longitud deseada para la cadena aleatoria. Por defecto es 10.
     *
     * @return string La cadena aleatoria generada.
     */
    public static function randomString(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Corta un texto a una longitud específica y añade un marcador de truncamiento si es necesario.
     *
     * @param string|null $text El texto que se debe truncar. Puede ser nulo.
     * @param int $length La longitud máxima permitida para el texto truncado. Por defecto es 50.
     * @param string $break El marcador de truncamiento que se añadirá al final del texto truncado. Por defecto es '...'.
     *
     * @return string El texto truncado con el marcador de truncamiento añadido si es necesario.
     */
    public static function textBreak(?string $text, int $length = 50, string $break = '...'): string
    {
        if ($text === null) {
            return '';
        }

        if (strlen($text) <= $length) {
            return trim($text);
        }

        // separamos el texto en palabras
        $words = explode(' ', trim($text));
        $result = '';
        foreach ($words as $word) {
            if (strlen($result . ' ' . $word . $break) <= $length) {
                $result .= $result === '' ? $word : ' ' . $word;
                continue;
            }

            $result .= $break;
            break;
        }

        return $result;
    }

    /**
     * Convierte un timestamp Unix en una cadena de fecha.
     *
     * Esta función utiliza un formato de fecha definido en `self::DATE_STYLE`.
     *
     * @param int $time El timestamp Unix que se debe convertir.
     *
     * @return string La representación de la fecha en el formato especificado.
     */
    public static function timeToDate(int $time): string
    {
        return date(self::DATE_STYLE, $time);
    }

    /**
     * Convierte un timestamp Unix en una cadena de fecha y hora.
     *
     * Esta función utiliza un formato de fecha y hora definido en `self::DATETIME_STYLE`.
     *
     * @param int $time El timestamp Unix que se debe convertir.
     *
     * @return string La representación de la fecha y hora en el formato especificado.
     */
    public static function timeToDateTime(int $time): string
    {
        return date(self::DATETIME_STYLE, $time);
    }

    private static function settingsLoad(): void
    {
        if ('' === Tools::config('db_name', '')) {
            self::$settings = [];
            return;
        }

        self::$settings = Cache::remember('tools-settings', function () {
            $settings = [];

            $model = new Settings();
            foreach ($model->all([], [], 0, 0) as $item) {
                $settings[$item->name] = $item->properties;
            }

            return $settings;
        });
    }
}
