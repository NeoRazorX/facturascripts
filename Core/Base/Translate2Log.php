<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of TranslateLog
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Translate2Log extends MiniLog
{

    /**
     *
     * @var Translator
     */
    private static $i18n;

    /**
     * 
     * @return Translator
     */
    protected function i18n()
    {
        if (!isset(self::$i18n)) {
            self::$i18n = new Translator();
        }

        return self::$i18n;
    }

    /**
     * 
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    protected function log(string $level, string $message, array $context = [])
    {
        $translation = $this->i18n()->trans($message, $context);
        parent::log($level, $translation);
    }
}
