<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Description of InvoiceTrait
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait PasswordTrait
{

    /**
     * New password.
     *
     * @var string
     */
    public $newPassword;

    /**
     * Repeated new password.
     *
     * @var string
     */
    public $newPassword2;

    /**
     * Password hashed with password_hash()
     *
     * @var string
     */
    public $password;

    abstract public function primaryColumnValue();

    abstract protected static function toolBox();

    /**
     * Asigns the new password to the user.
     *
     * @param string $value
     */
    public function setPassword($value)
    {
        $this->password = password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * Verifies password. It also rehash the password if needed.
     *
     * @param string $value
     *
     * @return bool
     */
    public function verifyPassword($value)
    {
        if (password_verify($value, $this->password)) {
            if (password_needs_rehash($this->password, PASSWORD_DEFAULT)) {
                $this->setPassword($value);
            }

            return true;
        }

        return false;
    }

    /**
     * 
     * @return bool
     */
    protected function testPassword(): bool
    {
        if (isset($this->newPassword, $this->newPassword2) && $this->newPassword !== '' && $this->newPassword2 !== '') {
            if ($this->newPassword !== $this->newPassword2) {
                $this->toolBox()->i18nLog()->warning('different-passwords', ['%userNick%' => $this->primaryColumnValue()]);
                return false;
            }

            $this->setPassword($this->newPassword);
        }

        return true;
    }
}
