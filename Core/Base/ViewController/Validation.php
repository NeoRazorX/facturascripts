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
namespace FacturaScripts\Core\Base\ViewController;

/**
 * Description of Validation
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Validation
{
    public static function validate($type, $value)
    {
        switch ($type) {
            case 'email':
                return preg_match('|^[\w\.-]{1,64}@[\w\.-]{1,252}\.\w{2,4})$i', $value);

            case 'int':
                return ((string) intval($value) == $value);
                
            default:
                return false;
        }
    }
}
