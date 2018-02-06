<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
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
namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Lib\RandomDataGenerator;
use FacturaScripts\Core\Model;

/**
 * TODO
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
abstract class ModelDataGeneratorClass
{

    /**
     * Provides direct access to the database.
     *
     * @var Base\DataBase
     */
    protected $db;

    /**
     * Contains generated ejercicios
     *
     * @var Model\Ejercicio
     */
    protected $ejercicio;

    /**
     * Contains generated empresas
     *
     * @var Model\Empresa
     */
    protected $empresa;

    /**
     * Provides access to the data generator
     *
     * @var RandomDataGenerator\DataGeneratorTools
     */
    protected $tools;

    /**
     * Return the new model name.
     *
     * @return mixed[]
     */
    abstract public function getList();

    /**
     * Return the new model name.
     *
     * @return string
     */
    abstract public function newModel();

    /**
     * Constructor. Initialize everything needed and randomize.
     *
     * @param Model\Empresa $empresa
     */
    public function __construct($empresa)
    {
        $this->db = new Base\DataBase();
        $this->empresa = $empresa;
        $this->ejercicio = new Model\Ejercicio();
        $this->tools = new RandomDataGenerator\DataGeneratorTools();
        $this->tools->loadData($this->getList(), $this->newModel(), true);
    }

    /**
     * Devuelve listados de datos del model indicado.
     *
     * @param string $modelName
     * @param string $tableName
     * @param string $functionName
     * @param bool   $recursivo
     *
     * @return array
     */
    protected function randomModel($modelName, $tableName, $functionName = 'generate', $recursivo = true)
    {
        $lista = [];

        $sql = 'SELECT * FROM ' . $tableName . ' ORDER BY ';
        $sql .= strtolower(FS_DB_TYPE) === 'mysql' ? 'RAND()' : 'random()';

        $data = $this->db->selectLimit($sql, 100);
        if (!empty($data)) {
            foreach ($data as $d) {
                $lista[] = new $modelName($d);
            }
        } elseif ($recursivo) {
            $this->{$functionName}();
            $lista = $this->randomModel($modelName, $tableName, $functionName, false);
        }

        return $lista;
    }
}
