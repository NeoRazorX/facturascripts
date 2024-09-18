<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Error;

use FacturaScripts\Core\Template\ErrorController;
use FacturaScripts\Core\Tools;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class DefaultError extends ErrorController
{
    public function run(): void
    {
        $this->setSaveCrash(true);

        http_response_code(500);

        if ($this->exception instanceof SyntaxError) {
            $title = 'Twig syntax error';
            $body = '<h1>' . $title . '</h1>'
                . '<p>' . $this->exception->getRawMessage() . '</p>'
                . '<p><b>File</b>: ' . $this->exception->getFile()
                . ', <b>line</b>: ' . $this->exception->getLine() . '</p>';

            echo $this->htmlCard($title, $body, 'bg-danger');
            return;
        }

        if ($this->exception instanceof RuntimeError) {
            $title = 'Twig runtime error';
            $body = '<h1>' . $title . '</h1>'
                . '<p>' . $this->exception->getRawMessage() . '</p>'
                . '<p><b>File</b>: ' . $this->exception->getFile()
                . ', <b>line</b>: ' . $this->exception->getLine() . '</p>';

            echo $this->htmlCard($title, $body, 'bg-danger');
            return;
        }

        if ($this->exception instanceof LoaderError) {
            $title = 'Twig loader error';
            $body = '<h1>' . $title . '</h1>'
                . '<p>' . $this->exception->getRawMessage() . '</p>'
                . '<p><b>File</b>: ' . $this->exception->getFile()
                . ', <b>line</b>: ' . $this->exception->getLine() . '</p>';

            echo $this->htmlCard($title, $body, 'bg-danger');
            return;
        }

        $title = 'Internal error #' . $this->exception->getCode();
        $body = '<h1>' . $title . '</h1>'
            . '<p>' . $this->exception->getMessage() . '</p>'
            . '<p><b>File</b>: ' . $this->exception->getFile()
            . ', <b>line</b>: ' . $this->exception->getLine() . '</p>';

        $table = $this->getTrace();

        echo $this->htmlCard($title, $body, 'bg-danger', $table);
    }

    protected function getTrace(): string
    {
        $table = '';
        if (Tools::config('debug', false)) {
            $table .= '<div class="table-responsive">'
                . '<table class="table table-striped mb-0">'
                . '<thead><tr><th>#</th></th><th>Trace</th></tr></thead>'
                . '<tbody>';

            foreach (array_reverse($this->exception->getTrace()) as $num => $trace) {
                $text = isset($trace['file']) ?
                    $this->removePathFromFile($trace['file']) . ':' . $trace['line'] :
                    '[internal function]: ' . $trace['class'] . $trace['type'] . $trace['function'];

                $table .= '<tr><td>' . (1 + $num) . '</td><td>' . $text . '</td></tr>';
            }

            $table .= '</tbody></table></div>';
        }

        return $table;
    }

    protected function removePathFromFile(string $file): string
    {
        return substr($file, 1 + strlen(Tools::folder()));
    }
}
