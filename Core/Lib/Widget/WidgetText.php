<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Description of WidgetText
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class WidgetText extends BaseWidget
{

    /**
     * Indicates the maximum length of characters.
     * 0 -> indeterminate
     *
     * @var int
     */
    protected $maxlength;

    /**
     * Class constructor
     *
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->maxlength = $data['maxlength'] ?? 0;
    }

    /**
     * Add extra attributes to html input field
     *
     * @return string
     */
    protected function inputHtmlExtraParams()
    {
        $params = $this->maxlength > 0 ? ' maxlength="' . $this->maxlength . '"' : '';
        return $params . parent::inputHtmlExtraParams();
    }
}
