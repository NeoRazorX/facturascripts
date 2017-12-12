<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

namespace FacturaScripts\Core\Base\ExtendedController;

/**
 * Description of WidgetItemText
 *
 * @author Nazca Networks <comercial@nazcanetworks.com>
 */
class WidgetItemImage extends WidgetItem
{
    /**
     * Class constructor
     *
     * @param string $type
     */
    public function __construct($type)
    {
        parent::__construct();

        $this->type = $type;
    }

    

    /**
     * Generates the HTML code to display the data in the List controller
     *
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
            case 'image':
               /*https://www.bootply.com/O0Q66tkatA
/*
                * 
                * <div class="container">
    <div class="picture-container">
        <div class="picture">
            <img src="https://lh3.googleusercontent.com/LfmMVU71g-HKXTCP_QWlDOemmWg4Dn1rJjxeEsZKMNaQprgunDTtEuzmcwUBgupKQVTuP0vczT9bH32ywaF7h68mF-osUSBAeM6MxyhvJhG6HKZMTYjgEv3WkWCfLB7czfODidNQPdja99HMb4qhCY1uFS8X0OQOVGeuhdHy8ln7eyr-6MnkCcy64wl6S_S6ep9j7aJIIopZ9wxk7Iqm-gFjmBtg6KJVkBD0IA6BnS-XlIVpbqL5LYi62elCrbDgiaD6Oe8uluucbYeL1i9kgr4c1b_NBSNe6zFwj7vrju4Zdbax-GPHmiuirf2h86eKdRl7A5h8PXGrCDNIYMID-J7_KuHKqaM-I7W5yI00QDpG9x5q5xOQMgCy1bbu3St1paqt9KHrvNS_SCx-QJgBTOIWW6T0DHVlvV_9YF5UZpN7aV5a79xvN1Gdrc7spvSs82v6gta8AJHCgzNSWQw5QUR8EN_-cTPF6S-vifLa2KtRdRAV7q-CQvhMrbBCaEYY73bQcPZFd9XE7HIbHXwXYA=s200-no" class="picture-src" id="wizardPicturePreview" title="">
            <input type="file" id="wizard-picture" class="">
        </div>
         <h6 class="">Choose Picture</h6>

    </div>
</div>
                */
                $fieldName = '"' . $this->fieldName . '"';
                $html = $this->getIconHTML();
                 $html ='<div class="container">'
                    .'<div class="image-container">'
                    .'<div class="image" style="cursor:pointer;">'
                    .'<img src="'.$value==""?"default-img": $value.'" class="picture" name="'. 
                    $fieldName .'" id="'. $fieldName.'" '
                    . 'onchange="">'
                    .'<input type="file" id="" class="">'
                    .'</div>'
                    .'<h6> choose-image</h6>'
                    .'</div>'
                    .'</div>';

                if (!empty($this->icon)) {
                    $html .= '</div>';
                }
                break;
            case 'gallery':
                break;
            default:
                $html = $this->standardEditHTMLWidget($value, $specialAttributes);
        }

        return $html;
    }
}

