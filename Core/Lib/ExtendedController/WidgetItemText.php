<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\ExtendedController;

/**
 * Description of WidgetItemText
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetItemText extends WidgetItem
{

    /**
     * WidgetItemText constructor.
     *
     * @param string $type
     */
    public function __construct($type)
    {
        parent::__construct();

        $this->type = $type;
    }

    /**
     * Formats text to a given maximum length
     *
     * @param string $txt
     * @param int    $len
     *
     * @return string
     */
    private function getTextResume($txt, $len = 60)
    {
        if (mb_strlen($txt) < $len) {
            return $txt;
        }

        return mb_substr($txt, 0, $len - 3) . '...';
    }

    /**
     * Generates the HTML code to display the data in the List controller
     *
     * @param string $value
     *
     * @return string
     */
    public function getListHTML($value)
    {
        if ($value === null || $value === '') {
            return '';
        }
        $txt = $this->getTextResume($value);

        return $this->standardListHTMLWidget($value, $txt);
    }

    /**
     * Generates the HTML code to display and edit  the data in the Edit / EditList controller
     *
     * @param string $value
     *
     * @return string
     */
    public function getEditHTML($value)
    {
        $specialAttributes = $this->specialAttributes();

        switch ($this->type) {
            case 'bbcode':
                $html = '<textarea name="' . $this->fieldName . '" class="form-control bbcode" rows="10" '
                    . $specialAttributes . '>' . $value . '</textarea>';
                break;
            
            case 'html':
                $html = '<textarea name="' . $this->fieldName . '" class="form-control htmleditor" rows="10" '
                    . $specialAttributes . '>' . $value . '</textarea>';
                break;

            case 'textarea':
                $html = '<textarea name="' . $this->fieldName . '" class="form-control" rows="3" '
                    . $specialAttributes . '>' . $value . '</textarea>';
                break;

            default:
                $html = $this->standardEditHTMLWidget($value, $specialAttributes);
        }

        return $html;
    }
}
