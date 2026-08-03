<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Widget;

use FacturaScripts\Core\Tools;

/**
 * Widget para campos de texto de una sola línea, con soporte para
 * longitud mínima y máxima.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Jose Antonio Cuello  <yopli2000@gmail.com>
 */
class WidgetText extends BaseWidget
{
    /**
     * Longitud máxima de caracteres.
     * 0 -> sin límite
     *
     * @var int
     */
    protected $maxlength;

    /**
     * Longitud mínima de caracteres.
     * 0 -> sin límite
     *
     * @var int
     */
    protected $minlength;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->maxlength = (int)($data['maxlength'] ?? $data['max'] ?? 0);
        $this->minlength = (int)($data['minlength'] ?? $data['min'] ?? 0);
    }

    /**
     * Añade atributos extra al input HTML.
     *
     * @return string
     */
    protected function inputHtmlExtraParams(): string
    {
        $params = '';

        if ($this->maxlength > 0) {
            $params .= ' maxlength="' . $this->maxlength . '"';
        }

        if ($this->minlength > 0) {
            $params .= ' minlength="' . $this->minlength . '"';
        }

        return $params . parent::inputHtmlExtraParams();
    }

    protected function show()
    {
        return $this->value === null ? '-' : Tools::noHtml((string)$this->value);
    }
}
