<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of WidgetTextarea
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class WidgetTextarea extends BaseWidget
{

    public function edit($model, $title = '', $description = '')
    {
        $this->setValue($model);
        $descriptionHtml = empty($description) ? '' : '<small class="form-text text-muted">' . static::$i18n->trans($description) . '</small>';
        $inputHtml = '<textarea name="' . $this->fieldname . '" class="form-control"' . $this->inputHtmlExtraParams() . '>' . $this->value . '</textarea>';
        $labelHtml = '<label>' . static::$i18n->trans($title) . '</label>';

        return '<div class="form-group">'
            . $labelHtml
            . $inputHtml
            . $descriptionHtml
            . '</div>';
    }
}
