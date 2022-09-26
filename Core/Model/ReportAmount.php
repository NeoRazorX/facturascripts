<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Model for amounts balance
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Jose Antonio Cuello <yopli2000@gmail.com>
 */
class ReportAmount extends Base\ReportAccounting
{
    use Base\ModelTrait;

    /** @var string */
    public $endcodsubaccount;

    /** @var bool */
    public $ignoreclosure;

    /** @var bool */
    public $ignoreregularization;

    /** @var int */
    public $level;

    /** @var string */
    public $startcodsubaccount;

    public function clear()
    {
        parent::clear();
        $this->ignoreclosure = true;
        $this->ignoreregularization = true;
        $this->level = 0;
    }

    public static function tableName(): string
    {
        return 'reportsamounts';
    }
}
