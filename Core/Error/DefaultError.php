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
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class DefaultError extends ErrorController
{
    public function run(): void
    {
        $this->save();

        http_response_code(500);

        $title = '🚨 Error ' . $this->info['hash'];

        if ($this->exception instanceof SyntaxError) {
            $content = '<h2>Twig syntax error</h2>'
                . '<p>' . $this->exception->getRawMessage() . '</p>'
                . '<p><b>File</b>: ' . $this->info['file']
                . ', <b>line</b>: ' . $this->info['line'] . '</p>';
        } elseif ($this->exception instanceof RuntimeError) {
            $content = '<h2>Twig runtime error</h2>'
                . '<p>' . $this->exception->getRawMessage() . '</p>'
                . '<p><b>File</b>: ' . $this->info['file']
                . ', <b>line</b>: ' . $this->info['line'] . '</p>';
        } elseif ($this->exception instanceof LoaderError) {
            $content = '<h2>Twig loader error</h2>'
                . '<p>' . $this->exception->getRawMessage() . '</p>'
                . '<p><b>File</b>: ' . $this->info['file']
                . ', <b>line</b>: ' . $this->info['line'] . '</p>';
        } else {
            $content = '<p>' . $this->exception->getMessage() . '</p>'
                . '<p><b>File</b>: ' . $this->info['file']
                . ', <b>line</b>: ' . $this->info['line'] . '</p>';
        }

        echo $this->html(
            $title,
            $this->htmlContainer(
                '<h1 class="h3 text-white mb-4">' . $title . '</h1>'
                . $this->htmlErrorCard($content, true, $this->canShowDeployButtons())
                . $this->htmlCodeFragmentCard()
                . $this->htmlLogCard()
            )
        );
    }
}
