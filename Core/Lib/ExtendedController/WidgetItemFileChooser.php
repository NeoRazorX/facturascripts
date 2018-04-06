<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  carlos@facturascripts.com
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

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This class manage all specific method for a WidgetItem of File Chooser type.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class WidgetItemFileChooser extends WidgetItem
{

    /**
     * WidgetItemFileChooser constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->type = 'filechooser';
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
        $html = $this->getIconHTML() . "<input type='file' name='" . $this->fieldName
            . "' value='" . $value . "' class='form-control-file' />";

        if (!empty($this->icon)) {
            $html .= '</div>';
        }

        return $html;
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
        return $this->standardListHTMLWidget($value);
    }

    /**
     * Return the max file size that can be uploaded.
     *
     * @return int
     */
    public function getMaxFileUpload()
    {
        return UploadedFile::getMaxFilesize() / 1024 / 1024;
    }
}
