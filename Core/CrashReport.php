<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * La clase que se encarga de gestionar los errores fatales.
 */
final class CrashReport
{
    public static function getErrorInfo(int $code, string $message, string $file, int $line): array
    {
        // calculamos un hash para el error, de forma que en la web podamos dar respuesta automáticamente
        $errorUrl = parse_url($_SERVER["REQUEST_URI"] ?? '', PHP_URL_PATH);
        $errorMessage = self::formatErrorMessage($message);
        $errorFile = str_replace(FS_FOLDER, '', $file);
        $errorHash = md5($code . $errorFile . $line . $errorMessage);
        $reportUrl = 'https://facturascripts.com/errores/' . $errorHash;
        $reportQr = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($reportUrl);

        return [
            'code' => $code,
            'message' => Tools::noHtml($errorMessage),
            'file' => $errorFile,
            'line' => $line,
            'fragment' => self::getErrorFragment($file, $line),
            'hash' => $errorHash,
            'url' => $errorUrl,
            'report_url' => $reportUrl,
            'report_qr' => $reportQr,
            'core_version' => Kernel::version(),
            'php_version' => phpversion(),
            'os' => PHP_OS,
            'plugin_list' => implode(',', Plugins::enabled()),
        ];
    }

    public static function init(): void
    {
        ob_start();

        register_shutdown_function('FacturaScripts\Core\CrashReport::shutdown');
    }

    public static function newToken(): string
    {
        $seed = Tools::config('db_name') . Tools::config('db_user') . Tools::config('db_password');
        return md5($seed . date('Y-m-d H'));
    }

    public static function save(array $info): void
    {
        // si no existe la carpeta MyFiles, no podemos guardar el archivo
        if (!is_dir(Tools::folder('MyFiles'))) {
            return;
        }

        // guardamos los datos en un archivo en MyFiles
        $file_name = 'crash_' . $info['hash'] . '.json';
        $file_path = Tools::folder('MyFiles', $file_name);
        if (file_exists($file_path)) {
            return;
        }

        file_put_contents($file_path, json_encode($info, JSON_PRETTY_PRINT));
    }

    public static function shutdown(): void
    {
        $error = error_get_last();
        if (!isset($error) || in_array($error['type'], [E_WARNING, E_NOTICE, E_DEPRECATED, E_CORE_ERROR, E_CORE_WARNING])) {
            return;
        }

        // limpiamos el buffer si es necesario
        if (ob_get_length() > 0) {
            ob_end_clean();
        }

        http_response_code(500);

        $info = self::getErrorInfo($error['type'], $error['message'], $error['file'], $error['line']);
        self::save($info);

        // comprobamos si el content-type es json
        if (isset($_SERVER['CONTENT_TYPE']) && 'application/json' === $_SERVER['CONTENT_TYPE']) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $error['message'], 'info' => $info]);
            return;
        }

        // comprobamos si el content-type es text/plain
        if (isset($_SERVER['CONTENT_TYPE']) && 'text/plain' === $_SERVER['CONTENT_TYPE']) {
            header('Content-Type: text/plain');
            echo $error['message'];
            return;
        }

        $messageParts = explode("\nStack trace:\n", $info['message']);

        echo '<!doctype html>'
            . '<html lang="en">'
            . '<head>'
            . '<meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>🚨 Fatal error ' . $info['hash'] . '</title>'
            . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"'
            . ' integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">'
            . '</head>'
            . '<body class="bg-danger">'
            . '<div class="container mt-5 mb-5">'
            . '<div class="row justify-content-center">'
            . '<div class="col-sm-8">'
            . '<div class="card shadow mb-4">'
            . '<div class="card-body">'
            . '<h1 class="h3 mt-0">🚨 Fatal error ' . $info['hash'] . '</h1>'
            . '<img src="' . $info['report_qr'] . '" alt="' . $info['hash'] . '" class="float-start pt-3 pe-3 pb-3">'
            . '<p>' . nl2br($messageParts[0]) . '</p>'
            . '<p class="mb-0"><b>Url</b>: ' . $info['url'] . '</p>';

        if (Tools::config('debug', false)) {
            echo '<p class="mb-0"><b>File</b>: ' . $info['file'] . ', <b>line</b>: ' . $info['line'] . '</p>';
        }

        echo '<p class="mb-0"><b>Hash</b>: ' . $info['hash'] . '</p>';

        if (Tools::config('debug', false)) {
            echo '<p class="mb-0"><b>Core</b>: ' . $info['core_version']
                . ', <b>plugins</b>: ' . implode(', ', Plugins::enabled()) . '<br/>'
                . '<b>PHP</b>: ' . $info['php_version'] . ', <b>OS</b>: ' . $info['os'] . '</p>';

            echo '<pre style="border: solid 1px grey; margin-top: 10px; margin-bottom: 0; padding: 5px">'
                . htmlspecialchars_decode($info['fragment']) . '</pre>';
        }

        echo '</div>';

        if (Tools::config('debug', false) && isset($messageParts[1])) {
            echo '<div class="table-responsive">'
                . '<table class="table table-striped mb-0">'
                . '<thead><tr><th>#</th><th>Trace</th></tr></thead>'
                . '<tbody>';

            $num = 1;
            $trace = explode("\n", $messageParts[1]);
            foreach (array_reverse($trace) as $value) {
                if (trim($value) === 'thrown' || substr($value, 3) === '{main}') {
                    continue;
                }

                echo '<tr><td>' . $num . '</td><td>' . substr($value, 3) . '</td></tr>';
                $num++;
            }

            echo '<tr><td>' . $num . '</td><td>' . $info['file'] . ':' . $info['line'] . '</td></tr>';
            echo '</tbody></table></div>';
        }

        echo '<div class="card-footer p-2">'
            . '<div class="row">'
            . '<div class="col">'
            . '<form method="post" action="' . $info['report_url'] . '" target="_blank">'
            . '<input type="hidden" name="error_code" value="' . $info['code'] . '">'
            . '<input type="hidden" name="error_message" value="' . $info['message'] . '">'
            . '<input type="hidden" name="error_file" value="' . $info['file'] . '">'
            . '<input type="hidden" name="error_line" value="' . $info['line'] . '">'
            . '<input type="hidden" name="error_hash" value="' . $info['hash'] . '">'
            . '<input type="hidden" name="error_url" value="' . $info['url'] . '">'
            . '<input type="hidden" name="error_core_version" value="' . $info['core_version'] . '">'
            . '<input type="hidden" name="error_plugin_list" value="' . $info['plugin_list'] . '">'
            . '<input type="hidden" name="error_php_version" value="' . $info['php_version'] . '">'
            . '<input type="hidden" name="error_os" value="' . $info['os'] . '">'
            . '<button type="submit" class="btn btn-secondary">' . self::trans('to-report') . '</button>'
            . '</form>'
            . '</div>';

        if (false === Tools::config('disable_deploy_actions', false)) {
            echo '<div class="col-auto">'
                . '<a href="' . Tools::config('route') . '/deploy?action=disable-plugins&token=' . self::newToken()
                . '" class="btn btn-light">' . self::trans('disable-plugins') . '</a> '
                . '<a href="' . Tools::config('route') . '/deploy?action=rebuild&token=' . self::newToken()
                . '" class="btn btn-light">' . self::trans('rebuild') . '</a> '
                . '</div>';
        }

        echo '</div>'
            . '</div>'
            . '</div>';

        // Añadimos el card con los últimos mensajes del log
        echo self::getLogCard();

        echo '</div>'
            . '</div>'
            . '</div>'
            . '</body>'
            . '</html>';
    }

    public static function validateToken(string $token): bool
    {
        return $token === self::newToken();
    }

    private static function formatErrorMessage(string $message): string
    {
        // quitamos el folder de las rutas
        $message = str_replace(FS_FOLDER, '', $message);

        // partimos por la traza
        $messageParts = explode("Stack trace:", $message);

        // si hay error de json, lo añadimos al mensaje
        if (json_last_error()) {
            $messageParts[0] .= "\n" . json_last_error_msg();
        }

        // ahora volvemos a unir el mensaje
        return implode("\nStack trace:", $messageParts);
    }

    private static function getLogCard(): string
    {
        $logMessages = MiniLog::read();
        if (empty($logMessages)) {
            return '';
        }

        // Obtenemos solo los últimos 10 mensajes
        $lastMessages = array_slice($logMessages, -10);

        $html = '<div class="card shadow mb-4">'
            . '<div class="card-body">'
            . '<h2 class="h4 mb-0">📃 ' . self::trans('recent-log-messages') . '</h2>'
            . '</div>'
            . '<div class="table-responsive">'
            . '<table class="table table-sm table-striped mb-0">'
            . '<thead>'
            . '<tr>'
            . '<th>' . self::trans('channel') . '</th>'
            . '<th>' . self::trans('level') . '</th>'
            . '<th>' . self::trans('message') . '</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody>';

        foreach ($lastMessages as $logEntry) {
            $levelClass = '';
            switch ($logEntry['level']) {
                case 'critical':
                case 'error':
                    $levelClass = 'text-danger';
                    break;
                case 'warning':
                    $levelClass = 'text-warning';
                    break;
                case 'info':
                    $levelClass = 'text-info';
                    break;
                case 'debug':
                    $levelClass = 'text-secondary';
                    break;
            }

            $html .= '<tr>'
                . '<td><small>' . $logEntry['channel'] . '</small></td>'
                . '<td class="' . $levelClass . '"><small><b>' . strtoupper($logEntry['level']) . '</b></small></td>'
                . '<td><small>' . Tools::noHtml($logEntry['message']) . '</small></td>'
                . '</tr>';
        }

        $html .= '</tbody></table>'
            . '</div>'
            . '</div>';

        return $html;
    }

    private static function trans(string $code): string
    {
        $translations = [
            'es_ES' => [
                'to-report' => 'Enviar informe',
                'disable-plugins' => 'Desactivar plugins',
                'rebuild' => 'Reconstruir',
                'recent-log-messages' => 'Últimos mensajes del log',
                'level' => 'Nivel',
                'message' => 'Mensaje',
                'channel' => 'Canal',
            ],
        ];

        $lang = Tools::config('lang', 'es_ES');
        return $translations[$lang][$code] ?? $code;
    }

    protected static function getErrorFragment($file, $line, $linesToShow = 10): string
    {
        // leemos el archivo
        $content = file_get_contents($file);
        $lines = explode("\n", $content);

        // calculamos el fragmento
        $startLine = ($line - ($linesToShow / 2)) - 1;
        $start = max($startLine, 0);
        $length = $linesToShow + 1;

        $errorFragment = array_slice($lines, $start, $length, true);
        $result = [];
        foreach ($errorFragment as $index => $value) {
            $lineNumber = $index + 1;

            // marcamos la línea del error
            if ($lineNumber === $line) {
                $result[] = '<span style="padding-top: 0.1rem; padding-bottom: 0.1rem; '
                    . 'background-color: yellow">' . str_pad($lineNumber, 3, ' ', STR_PAD_LEFT) . '    ' . htmlspecialchars($value) . '</span>';
                continue;
            }

            $result[] = str_pad($lineNumber, 3, ' ', STR_PAD_LEFT) . '    ' . htmlspecialchars($value);
        }

        return implode("\n", $result);
    }
}
