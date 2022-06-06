<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model;

/**
 * Defines which accounts must be used to generate the different accounting reports.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Balance extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var string
     */
    public $codbalance;

    /**
     * Description 4 of the balance.
     *
     * @var string
     */
    public $descripcion4ba;

    /**
     * Description 4 of the balance.
     *
     * @var string
     */
    public $descripcion4;

    /**
     * Level 4 of the balance.
     *
     * @var string
     */
    public $nivel4;

    /**
     * Description 3 of the balance.
     *
     * @var string
     */
    public $descripcion3;

    /**
     * Order 3 of the balance.
     *
     * @var string
     */
    public $orden3;

    /**
     * Level 2 of the balance.
     *
     * @var string
     */
    public $nivel3;

    /**
     * Description 2 of the balance.
     *
     * @var string
     */
    public $descripcion2;

    /**
     * Level 2 of the balance.
     *
     * @var int
     */
    public $nivel2;

    /**
     * Description 1 of the balance.
     *
     * @var string
     */
    public $descripcion1;

    /**
     * Level 1 of the balance.
     *
     * @var string
     */
    public $nivel1;

    /**
     * Nature of the balance.
     *
     * @var string
     */
    public $naturaleza;

    public static function primaryColumn(): string
    {
        return 'codbalance';
    }

    public static function tableName(): string
    {
        return 'balances';
    }

    public function test(): bool
    {
        // escapamos el html
        $this->codbalance = self::toolBox()::utils()::noHtml($this->codbalance);
        $this->descripcion1 = self::toolBox()::utils()::noHtml($this->descripcion1);
        $this->descripcion2 = self::toolBox()::utils()::noHtml($this->descripcion2);
        $this->descripcion3 = self::toolBox()::utils()::noHtml($this->descripcion3);
        $this->descripcion4 = self::toolBox()::utils()::noHtml($this->descripcion4);
        $this->descripcion4ba = self::toolBox()::utils()::noHtml($this->descripcion4ba);
        $this->naturaleza = self::toolBox()::utils()::noHtml($this->naturaleza);

        // comprobamos que tenga un código válido
        if (empty($this->codbalance) || 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,15}$/i', $this->codbalance)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codbalance, '%column%' => 'codbalance', '%min%' => '1', '%max%' => '15']
            );
            return false;
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListReportAccounting?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
