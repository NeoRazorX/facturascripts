<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model\LogMessage;

final class Logger
{
    const LEVEL_CRITICAL = 'critical';
    const LEVEL_DEBUG = 'debug';
    const LEVEL_ERROR = 'error';
    const LEVEL_INFO = 'info';
    const LEVEL_NOTICE = 'notice';
    const LEVEL_WARNING = 'warning';
    const MAX_ITEMS = 5000;
    const SAVE_METHOD_DB = 'db';
    const SAVE_METHOD_FILE = 'file';

    /**
     * Los canales actuales para el registro de mensajes.
     *
     * @var array
     */
    private $current_channels = ['master'];

    /**
     * El contexto adicional para el registro de mensajes.
     *
     * @var array
     */
    private static $current_context = [];

    /**
     * La lista de mensajes de registro.
     *
     * @var array
     */
    private static $data = [];

    /**
     * Indica si el registro está deshabilitado.
     *
     * @var bool
     */
    private static $disabled = false;

    /**
     * El nombre del último archivo de registro guardado.
     *
     * @var string|null
     */
    private static $last_log_filename;

    /**
     * El modo de guardado actual para los mensajes de registro.
     *
     * @var string
     */
    private static $save_method = 'db'; // 'db' or 'file'

    /** @var Translator */
    private $translator;

    public function __construct(array $channels = [])
    {
        if (empty($channels)) {
            $this->current_channels = ['master'];
        } else {
            // Filtrar canales vacíos y reemplazarlos con 'master'
            $mapped_channels = array_map(function ($channel) {
                return empty($channel) ? 'master' : $channel;
            }, $channels);
            // Eliminar duplicados
            $this->current_channels = array_values(array_unique($mapped_channels));
        }

        $this->translator = new Translator();
    }

    /**
     * Crea un nuevo logger con un canal específico.
     *
     * @param string $name
     * @return self
     */
    public static function channel(string $name): self
    {
        return new self([$name]);
    }

    /**
     * Limpia todos los mensajes de registro y el contexto actual.
     */
    public static function clear(): void
    {
        self::$data = [];
        self::$current_context = [];
    }

    /**
     * Limpia los mensajes de registro del canal especificado.
     *
     * @param string $channel
     */
    public static function clearChannel(string $channel): void
    {
        foreach (self::$data as $key => $value) {
            if ($value['channel'] === $channel) {
                unset(self::$data[$key]);
            }
        }

        self::$data = array_values(self::$data); // reindexar el array
    }

    /**
     * Limpia el contexto actual.
     */
    public static function clearContext(): void
    {
        self::$current_context = [];
    }

    public function critical(string $message, array $context = []): self
    {
        return $this->log(self::LEVEL_CRITICAL, $message, $context);
    }

    public function debug(string $message, array $context = []): self
    {
        if (Tools::config('debug') === false) {
            return $this;
        }

        return $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Deshabilita el registro de mensajes.
     *
     * @param bool $value
     */
    public static function disable(bool $value = true): void
    {
        self::$disabled = $value;
    }

    /**
     * Verifica si el registro de mensajes está deshabilitado.
     *
     * @return bool
     */
    public static function disabled(): bool
    {
        return self::$disabled;
    }

    public function error(string $message, array $context = []): self
    {
        return $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Devuelve los canales únicos utilizados en los mensajes de registro.
     *
     * @return array
     */
    public static function getChannels(): array
    {
        return self::$data ? array_unique(array_column(self::$data, 'channel')) : [];
    }

    /**
     * Devuelve el nombre del último archivo de registro guardado.
     *
     * @return string|null
     */
    public static function getLastLogFilename(): ?string
    {
        return self::$last_log_filename;
    }

    public function info(string $message, array $context = []): self
    {
        return $this->log(self::LEVEL_INFO, $message, $context);
    }

    public function notice(string $message, array $context = []): self
    {
        return $this->log(self::LEVEL_NOTICE, $message, $context);
    }

    /**
     * Devuelve los mensajes de registro filtrados por canal y niveles.
     * Si $num es 0, devuelve todos los mensajes. Si es mayor que 0, devuelve los primeros mensajes.
     * Y si es negativo, devuelve los últimos mensajes.
     *
     * @param string $channel
     * @param array $levels
     * @param int $num
     * @return array
     */
    public static function read(string $channel = '', array $levels = [], int $num = 0): array
    {
        if (empty(self::$data)) {
            return [];
        }

        $filtered = [];
        foreach (self::$data as $item) {
            if ($channel && $item['channel'] != $channel) {
                continue;
            }

            if (empty($levels) || in_array($item['level'], $levels)) {
                $filtered[] = $item;
            }
        }

        if ($num === 0) {
            return $filtered;
        }

        if ($num > 0) {
            return array_slice($filtered, 0, $num);
        }

        return array_slice($filtered, $num);
    }

    /**
     * Devuelve los mensajes de registro.
     * Si $num es 0, devuelve todos los mensajes. Si es mayor que 0, devuelve los primeros mensajes.
     * Y si es negativo, devuelve los últimos mensajes.
     *
     * @param int $num
     * @return array
     */
    public static function readAll(int $num = 0): array
    {
        if (empty(self::$data)) {
            return [];
        }

        if ($num === 0) {
            return self::$data;
        }

        if ($num > 0) {
            return array_slice(self::$data, 0, $num);
        }

        return array_slice(self::$data, $num);
    }

    /**
     * Devuelve los mensajes de registro filtrados por canal y niveles.
     * Si $num es 0, devuelve todos los mensajes. Si es mayor que 0, devuelve los primeros mensajes.
     * Y si es negativo, devuelve los últimos mensajes.
     *
     * @param string $channel
     * @param array $levels
     * @param int $num
     * @return array
     */
    public static function readChannel(string $channel, array $levels = [], int $num = 0): array
    {
        if (empty(self::$data)) {
            return [];
        }

        $filtered = [];
        foreach (self::$data as $item) {
            if ($item['channel'] !== $channel) {
                continue;
            }

            if (empty($levels) || in_array($item['level'], $levels)) {
                $filtered[] = $item;
            }
        }

        if ($num === 0) {
            return $filtered;
        }

        if ($num > 0) {
            return array_slice($filtered, 0, $num);
        }

        return array_slice($filtered, $num);
    }

    /**
     * Devuelve los mensajes de registro filtrados por nivel.
     * Si $num es 0, devuelve todos los mensajes. Si es mayor que 0, devuelve los primeros mensajes.
     * Y si es negativo, devuelve los últimos mensajes.
     *
     * @param string $level
     * @param int $num
     * @return array
     */
    public static function readLevel(string $level, int $num = 0): array
    {
        if (empty(self::$data)) {
            return [];
        }

        $filtered = [];
        foreach (self::$data as $item) {
            if ($item['level'] === $level) {
                $filtered[] = $item;
            }
        }

        if ($num === 0) {
            return $filtered;
        }

        if ($num > 0) {
            return array_slice($filtered, 0, $num);
        }

        return array_slice($filtered, $num);
    }

    /**
     * Guarda los mensajes de registro en la base de datos o en un archivo, según el modo de guardado actual.
     *
     * @return bool
     */
    public static function save(): bool
    {
        if (self::$disabled || empty(self::$data)) {
            return false;
        }

        // Guardamos en la base de datos si el modo es 'db'
        if (self::$save_method === self::SAVE_METHOD_DB && self::saveToDB()) {
            return true;
        }

        // En otro caso o error, guardamos en un archivo
        return self::saveToFile();
    }

    /**
     * Guarda los mensajes del canal especificado en la base de datos.
     *
     * @param string $channel
     * @return bool
     */
    public static function saveChannelToDB(string $channel): bool
    {
        if (self::$disabled || empty(self::$data)) {
            return false;
        }

        // Deshabilitamos temporalmente el logging para evitar bucles infinitos
        $previous_state = self::$disabled;
        self::$disabled = true;

        $saved = true;
        $saved_keys = [];

        foreach (self::$data as $key => $value) {
            if ($value['channel'] === $channel) {
                $log = self::createLogMessage($value);
                if ($log->save()) {
                    $saved_keys[] = $key;
                    continue;
                }

                $saved = false;
            }
        }

        // Restauramos el estado anterior del logging
        self::$disabled = $previous_state;

        // Eliminamos solo los mensajes guardados exitosamente
        if (!empty($saved_keys)) {
            foreach ($saved_keys as $key) {
                unset(self::$data[$key]);
            }
            self::$data = array_values(self::$data);
        }

        return $saved;
    }

    /**
     * Guarda los mensajes del canal especificado en un archivo.
     *
     * @param string $channel
     * @return bool
     */
    public static function saveChannelToFile(string $channel): bool
    {
        if (self::$disabled || empty(self::$data)) {
            return false;
        }

        $log_dir = Tools::folder('MyFiles');
        if (!is_dir($log_dir) && !mkdir($log_dir, 0755, true)) {
            return false;
        }

        self::$last_log_filename = $log_dir . '/log_' . Tools::slug($channel) . '_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.json';

        // Preparar datos para JSON del canal específico
        $json_data = [];

        foreach (self::$data as $value) {
            if ($value['channel'] === $channel) {
                $json_data[] = [
                    'timestamp' => Tools::timeToDateTime((int)$value['time']),
                    'level' => $value['level'],
                    'channel' => $value['channel'],
                    'message' => $value['message'],
                    'original' => $value['original'],
                    'count' => $value['count'],
                    'context' => $value['context']
                ];
            }
        }

        if (empty($json_data)) {
            return false;
        }

        // Guarda el contenido en el archivo JSON
        $content = json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $result = file_put_contents(self::$last_log_filename, $content, LOCK_EX) !== false;

        if ($result) {
            // Eliminar mensajes guardados del canal
            self::clearChannel($channel);
        }

        return $result;
    }

    /**
     * Establece el modo de guardado para los mensajes de registro.
     *
     * @param string $method 'db' o 'file'
     * @return bool
     */
    public static function saveMethod(string $method): bool
    {
        if (in_array($method, [self::SAVE_METHOD_DB, self::SAVE_METHOD_FILE])) {
            self::$save_method = $method;
            return true;
        }

        return false;
    }

    /**
     * Guarda todos los mensajes de registro en la base de datos.
     *
     * @return bool
     */
    public static function saveToDB(): bool
    {
        if (self::$disabled || empty(self::$data)) {
            return false;
        }

        // Deshabilitamos temporalmente el logging para evitar bucles infinitos
        $previous_state = self::$disabled;
        self::$disabled = true;

        $saved = true;
        $saved_keys = [];

        foreach (self::$data as $key => $value) {
            // Del canal master excluimos los que no sean error o critical
            if ($value['channel'] === 'master' && !in_array($value['level'], ['critical', 'error'])) {
                continue;
            }

            $log = self::createLogMessage($value);
            if ($log->save()) {
                $saved_keys[] = $key;
                continue;
            }

            $saved = false;
        }

        // Restauramos el estado anterior del logging
        self::$disabled = $previous_state;

        // Eliminamos solo los mensajes guardados exitosamente
        if (!empty($saved_keys)) {
            foreach ($saved_keys as $key) {
                unset(self::$data[$key]);
            }
            self::$data = array_values(self::$data);
        }

        return $saved;
    }

    /**
     * Guarda todos los mensajes de registro en un archivo.
     *
     * @return bool
     */
    public static function saveToFile(): bool
    {
        if (self::$disabled || empty(self::$data)) {
            return false;
        }

        $log_dir = Tools::folder('MyFiles');
        if (!is_dir($log_dir) && !mkdir($log_dir, 0755, true)) {
            return false;
        }

        self::$last_log_filename = $log_dir . '/log_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.json';

        // Preparar datos para JSON
        $json_data = [];
        foreach (self::$data as $value) {
            // Del canal master excluimos los que no sean error o critical
            if ($value['channel'] === 'master' && !in_array($value['level'], ['critical', 'error'])) {
                continue;
            }

            $json_data[] = [
                'timestamp' => Tools::timeToDateTime((int)$value['time']),
                'level' => $value['level'],
                'channel' => $value['channel'],
                'message' => $value['message'],
                'original' => $value['original'],
                'count' => $value['count'],
                'context' => $value['context']
            ];
        }

        // Guarda el contenido en el archivo JSON
        $content = json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $result = file_put_contents(self::$last_log_filename, $content, LOCK_EX) !== false;

        if ($result) {
            // Limpia los mensajes guardados
            self::clear();
        }

        return $result;
    }

    /**
     * Crea un nuevo logger con múltiples canales.
     *
     * @param array $channels
     * @return self
     */
    public static function stack(array $channels): self
    {
        return new self($channels);
    }

    public function warning(string $message, array $context = []): self
    {
        return $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Añade contexto adicional para los mensajes de registro.
     *
     * @param array $context
     */
    public static function withContext(array $context): void
    {
        self::$current_context = array_merge(self::$current_context, $context);
    }

    /**
     * Crea un objeto LogMessage a partir de un registro de datos.
     *
     * @param array $value
     * @return LogMessage
     */
    private static function createLogMessage(array $value): LogMessage
    {
        $log = new LogMessage();
        $log->channel = $value['channel'];
        $log->context = json_encode($value['context'], JSON_PRETTY_PRINT);
        $log->idcontacto = $value['context']['idcontacto'] ?? null;
        $log->ip = Session::getClientIp();
        $log->level = $value['level'];
        $log->message = $value['message'];
        $log->model = $value['context']['model-class'] ?? null;
        $log->modelcode = $value['context']['model-code'] ?? null;
        $log->nick = $value['context']['nick'] ?? Session::user()->nick;
        $log->time = Tools::timeToDateTime((int)$value['time']);
        $log->uri = $value['context']['uri'] ?? Session::get('uri');

        return $log;
    }

    private function log(string $level, string $message, array $context = []): self
    {
        if (self::$disabled || empty($message)) {
            return $this;
        }

        if (count(self::$data) >= self::MAX_ITEMS) {
            self::save();
        }

        $final_context = array_merge($context, self::$current_context);
        $trans_message = is_null($this->translator) ? $message : $this->translator->trans($message, $context);

        foreach ($this->current_channels as $channel) {
            // Si encontramos este mensaje en el log, incrementamos el contador
            foreach (self::$data as $key => $value) {
                if ($value['channel'] === $channel && $value['level'] === $level &&
                    $value['message'] === $trans_message &&
                    empty(array_diff_assoc($value['context'], $final_context)) &&
                    empty(array_diff_assoc($final_context, $value['context']))) {
                    self::$data[$key]['count']++;
                    continue 2;
                }
            }

            // Añadir mensaje
            self::$data[] = [
                'channel' => $channel,
                'context' => $final_context,
                'count' => 1,
                'level' => $level,
                'message' => $trans_message,
                'original' => $message,
                'time' => $context['time'] ?? microtime(true),
            ];
        }

        return $this;
    }
}
