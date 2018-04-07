<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Description of WidgetItemImage
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class WidgetItemImage extends WidgetItem
{

    /**
     * Accepted values for the field associated to the widget
     *
     * @var array
     */
    public $values;

    /**
     * WidgetItemImage constructor.
     *
     * @param string $type
     */
    public function __construct($type)
    {
        parent::__construct();

        $this->type = $type;
        $this->values = [];
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
        $specialAttributes = $this->specialAttributes();

        switch ($this->type) {
            case 'thumbnail':
                $html = '<img name="' . $this->fieldName . '" src="' . $value . '" class="img-fluid img-thumbnail" '
                    . $specialAttributes . '/>';
                break;

            case 'picture':
                /// TODO: Pending to test
                /// @source: https://getbootstrap.com/docs/4.0/content/images/#picture
                $html = '​<picture>';
                foreach ($this->values as $srcset) {
                    $html .= '<source srcset="' . $srcset . '" type="image/svg+xml">';
                }
                $html .= '<img name="' . $this->fieldName . '" src="' . $value . '" class="img-fluid img-thumbnail" '
                    . $specialAttributes . '/></picture>';
                break;

            default:
                $html = $this->standardListHTMLWidget($value);
        }

        return $html;
    }

    /**
     * Generates the HTML code to display and edit the data in the Edit / EditList controller
     *
     * @param string $value
     *
     * @return string
     */
    public function getEditHTML($value)
    {
        $specialAttributes = $this->specialAttributes();

        switch ($this->type) {
            case 'thumbnail':
                $html = '<img name="' . $this->fieldName . '" src="' . $value . '" class="img-fluid img-thumbnail" '
                    . $specialAttributes . '/>';
                break;

            case 'picture':
                /// TODO: Pending to test
                /// @source: https://getbootstrap.com/docs/4.0/content/images/#picture
                $html = '​<picture>';
                foreach ($this->values as $srcset) {
                    $html .= '<source srcset="' . $srcset . '" type="image/svg+xml">';
                }
                $html .= '<img name="' . $this->fieldName . '" src="' . $value . '" class="img-fluid img-thumbnail" '
                    . $specialAttributes . '/></picture>';
                break;

            default:
                $html = $this->standardEditHTMLWidget($value, $specialAttributes);
        }

        return $html;
    }
}
