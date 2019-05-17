<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;

/**
 * Base class for creation of accounting processes
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class AccountingClass extends AccountingAccounts
{

    /**
     *
     * @var ModelClass
     */
    protected $document;

    /**
     * Multi-language translator.
     *
     * @var Translator
     */
    protected $i18n;

    /**
     * Manage the log of all controllers, models and database.
     *
     * @var MiniLog
     */
    protected $miniLog;

    /**
     * Class constructor and initializate auxiliar model class.
     */
    public function __construct()
    {
        parent::__construct();
        $this->i18n = new Translator();
        $this->miniLog = new MiniLog();
    }

    /**
     * Method to launch the accounting process
     *
     * @param ModelClass $model
     */
    public function generate($model)
    {
        $this->document = $model;
        $this->exercise->idempresa = $model->idempresa ?? AppSettings::get('default', 'idempresa');
    }
}
