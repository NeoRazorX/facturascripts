<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Validator;

trait EmailAndPhonesTrait
{
    /** @var string */
    public $email;

    /** @var string */
    public $telefono1;

    /** @var string */
    public $telefono2;

    protected function testEmailAndPhones(): bool
    {
        if ($this->email !== null) {
            $this->email = Tools::noHtml(mb_strtolower($this->email, 'UTF8'));
        }

        $this->telefono1 = Tools::noHtml($this->telefono1) ?? '';
        $this->telefono2 = Tools::noHtml($this->telefono2) ?? '';

        if (empty($this->email)) {
            $this->email = '';
        } elseif (false === Validator::email($this->email)) {
            Tools::log()->warning('not-valid-email', ['%email%' => $this->email]);
            $this->email = '';
            return false;
        }

        return true;
    }
}
