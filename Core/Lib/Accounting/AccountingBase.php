<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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
}
