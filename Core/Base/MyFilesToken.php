<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base;

/**
 * Description of MyFilesToken
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class MyFilesToken
{

    /**
     * 
     * @param string $path
     * @param bool   $permanent
     *
     * @return string
     */
    public static function get(string $path, bool $permanent): string
    {
        $init = \FS_DB_NAME . \FS_DB_PASS;
        $date = \date('d-m-Y');
        return $permanent ? \sha1($init . $path . $date) : \sha1($init . $path);
    }

    /**
     * 
     * @param string $path
     * @param string $token
     *
     * @return bool
     */
    public static function validate(string $path, string $token): bool
    {
        return $token === static::get($path, true) || $token === static::get($path, false);
    }
}
