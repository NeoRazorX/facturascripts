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
 * Model for balances reports
 *
 * @author Jose Antonio Cuello <yopli2000@gmail.com>
 */
class ReportBalance extends Base\ReportAccounting
{

    use Base\ModelTrait;

    const TYPE_SHEET = 'balance-sheet';
    const TYPE_PROFIT = 'profit-and-loss';
    const TYPE_INCOME = 'income-and-expenses';
    const SUBTYPE_ABBREVIATED = 'abbreviated';
    const SUBTYPE_NORMAL = 'normal';

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $subtype;

    public function clear()
    {
        parent::clear();
        $this->type = self::TYPE_SHEET;
        $this->subtype = self::SUBTYPE_ABBREVIATED;
    }

    public static function tableName(): string
    {
        return 'reportsbalance';
    }

    public static function typeList(): array
    {
        $i18n = self::toolBox()->i18n();
        return [
            ['value' => self::TYPE_SHEET, 'title' => $i18n->trans(self::TYPE_SHEET)],
            ['value' => self::TYPE_PROFIT, 'title' => $i18n->trans(self::TYPE_PROFIT)],
            ['value' => self::TYPE_INCOME, 'title' => $i18n->trans(self::TYPE_INCOME)]
        ];
    }

    public static function subtypeList(): array
    {
        $i18n = self::toolBox()->i18n();
        return [
            ['value' => self::SUBTYPE_ABBREVIATED, 'title' => $i18n->trans(self::SUBTYPE_ABBREVIATED)],
            ['value' => self::SUBTYPE_NORMAL, 'title' => $i18n->trans(self::SUBTYPE_NORMAL)]
        ];
    }
}
