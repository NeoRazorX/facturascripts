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

namespace FacturaScripts\Core\Template;

use Exception;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Contract\ErrorControllerInterface;
use FacturaScripts\Core\CrashReport;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;

abstract class ErrorController implements ErrorControllerInterface
{
    /** @var Exception */
    protected $exception;

    /** @var array */
    protected $info;

    /** @var Response */
    private $response;

    /** @var string */
    protected $url;

    public function __construct(Exception $exception, string $url = '')
    {
        $this->exception = $exception;

        $this->info = CrashReport::getErrorInfo(
            $exception->getCode(),
            $exception->getMessage() . "\nStack trace:\n" . $exception->getTraceAsString(),
            $exception->getFile(),
            $exception->getLine()
        );

        $this->url = $url;
    }

    protected function canShowDebugInfo(): bool
    {
        return Tools::config('debug', false);
    }

    protected function canShowDeployButtons(): bool
    {
        if (Tools::config('disable_deploy_actions', false)) {
            return false;
        }

        // comprobamos si existen las cookies de login
        if (isset($_COOKIE['fsNick']) && isset($_COOKIE['fsLogkey'])) {
            return true;
        }

        // si el dominio es localhost, también mostramos los botones
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($host, 'localhost') !== false) {
            return true;
        }

        return false;
    }

    protected function html(string $title, string $body): string
    {
        return '<!doctype html>'
            . '<html lang="en">'
            . '<head>'
            . '<meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . $title . '</title>'
            . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"'
            . ' integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">'
            . '</head>'
            . '<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">'
            . $body
            . '</body>'
            . '</html>';
    }

    protected function htmlCodeFragmentCard(): string
    {
        $fragment = CrashReport::getErrorFragment($this->exception->getFile(), $this->exception->getLine(), 10, true);
        if (!$this->canShowDebugInfo() || empty($fragment)) {
            return '';
        }

        $html = '<div class="card shadow mb-4">'
            . '<div class="card-body">'
            . '<h2 class="h5 mb-3">📑 ' . $this->info['file'] . '</h2>'
            . '<pre style="border: solid 1px #dee2e6; margin-bottom: 0; padding: 10px; background-color: #f8f9fa; border-radius: 4px; overflow-x: auto">'
            . $fragment . '</pre>'
            . '</div>';

        $messageParts = explode("\nStack trace:\n", Tools::noHtml($this->info['message']));
        if (isset($messageParts[1])) {
            $html .= '<div class="table-responsive">'
                . '<table class="table table-striped mb-0">'
                . '<thead><tr><th>#</th><th>Trace</th></tr></thead>'
                . '<tbody>';

            $num = 1;
            $trace = explode("\n", $messageParts[1]);
            foreach (array_reverse($trace) as $value) {
                if (trim($value) === 'thrown' || substr($value, 3) === '{main}') {
                    continue;
                }

                $html .= '<tr><td>' . $num . '</td><td>' . substr($value, 3) . '</td></tr>';
                $num++;
            }

            $html .= '<tr><td>' . $num . '</td><td>' . $this->info['file'] . ':' . $this->info['line'] . '</td></tr>';
            $html .= '</tbody></table></div>';
        }

        $html .= '</div>';

        return $html;
    }

    protected function htmlErrorCard(string $content, bool $report_btn = false, bool $action_buttons = false): string
    {
        $form = '';
        if ($report_btn) {
            $form = '<form method="post" action="' . Tools::noHtml($this->info['report_url']) . '" target="_blank">'
                . '<input type="hidden" name="error_code" value="' . Tools::noHtml($this->info['code']) . '">'
                . '<input type="hidden" name="error_message" value="' . Tools::noHtml($this->info['message']) . '">'
                . '<input type="hidden" name="error_file" value="' . Tools::noHtml($this->info['file']) . '">'
                . '<input type="hidden" name="error_line" value="' . Tools::noHtml($this->info['line']) . '">'
                . '<input type="hidden" name="error_hash" value="' . Tools::noHtml($this->info['hash']) . '">'
                . '<input type="hidden" name="error_url" value="' . Tools::noHtml($this->info['url']) . '">'
                . '<input type="hidden" name="error_core_version" value="' . Tools::noHtml($this->info['core_version']) . '">'
                . '<input type="hidden" name="error_plugin_list" value="' . Tools::noHtml($this->info['plugin_list']) . '">'
                . '<input type="hidden" name="error_php_version" value="' . Tools::noHtml($this->info['php_version']) . '">'
                . '<input type="hidden" name="error_os" value="' . Tools::noHtml($this->info['os']) . '">'
                . '<button type="submit" class="btn btn-secondary">' . Tools::trans('to-report') . '</button>'
                . '</form>';
        }

        $buttons = '';
        if ($action_buttons) {
            $buttons = '<div class="col-auto">'
                . '<a href="' . Tools::config('route') . '/deploy?action=disable-plugins&token=' . CrashReport::newToken()
                . '" class="btn btn-outline-secondary">' . Tools::trans('disable-plugins') . '</a> '
                . '</div>'
                . '<div class="col-auto">'
                . '<a href="' . Tools::config('route') . '/deploy?action=rebuild&token=' . CrashReport::newToken()
                . '" class="btn btn-outline-secondary">' . Tools::trans('rebuild') . '</a> '
                . '</div>';
        }

        $qr = '';
        if ($report_btn) {
            $qr = '<img src="' . $this->info['report_qr'] . '" class="float-end" alt="QR" />';
        }

        return '<div class="card shadow mb-4">'
            . '<div class="card-body">' . $qr . $content . '</div>'
            . '<div class="card-footer p-2">'
            . '<div class="row">'
            . '<div class="col">' . $form . '</div>'
            . $buttons
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected function htmlContainer(string $content): string
    {
        return '<div class="container mt-5 mb-5">'
            . '<div class="row justify-content-center">'
            . '<div class="col-sm-12">'
            . $content
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected function htmlLogCard(): string
    {
        if (!$this->canShowDebugInfo()) {
            return '';
        }

        $logMessages = MiniLog::read();
        if (empty($logMessages)) {
            return '';
        }

        // Obtenemos solo los últimos 10 mensajes
        $lastMessages = array_slice($logMessages, -10);

        $html = '<div class="card shadow mb-4">'
            . '<div class="card-body">'
            . '<h2 class="h5 mb-0">📃 ' . Tools::trans('logs') . '</h2>'
            . '</div>'
            . '<div class="table-responsive">'
            . '<table class="table table-sm table-striped mb-0">'
            . '<thead>'
            . '<tr>'
            . '<th>' . Tools::trans('channel') . '</th>'
            . '<th>' . Tools::trans('level') . '</th>'
            . '<th>' . Tools::trans('message') . '</th>'
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
                . '<td><small>' . Tools::noHtml($logEntry['channel']) . '</small></td>'
                . '<td class="' . $levelClass . '"><small><b>' . strtoupper($logEntry['level']) . '</b></small></td>'
                . '<td><small>' . Tools::noHtml($logEntry['message']) . '</small></td>'
                . '</tr>';
        }

        $html .= '</tbody></table>'
            . '</div>'
            . '</div>';

        return $html;
    }

    protected function response(): Response
    {
        if (null === $this->response) {
            $this->response = new Response();
        }

        return $this->response;
    }

    protected function save(): void
    {
        CrashReport::save($this->info);
    }
}
