<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Base\Utils;

/**
 * Description of AccountingBase
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author nazca <comercial@nazcanetworks.com>
 */
abstract class AccountingBase
{

    use Utils;

    protected $database;
    protected $divisaTools;
    protected $dateFrom;
    protected $dateTo;

    abstract protected function getData();

    abstract protected function processLine($line);

    abstract public static function generate($dateFrom, $dateTo);

    public function __construct($dateFrom, $dateTo)
    {
        $this->database = new DataBase();
        $this->divisaTools = new DivisaTools();

        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    /**
     *
     * @param string $date
     * @param string $add
     * 
     * @return string
     */
    protected function addToDate($date, $add)
    {
        return \date('d-m-Y', strtotime($add, strtotime($date)));
    }
}
