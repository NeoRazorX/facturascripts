<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Exception;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\Import\CSVImport;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\CuentaEspecial;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Subcuenta;
use ParseCsv\Csv;
use SimpleXMLElement;

/**
 * Importa un plan contable (cuentas y subcuentas) en un ejercicio desde un fichero
 * CSV o XML, dentro de una transacción. Actualiza primero la tabla de cuentas
 * especiales y, si algo falla, revierte todos los cambios.
 *
 * @author       Carlos García Gómez      <carlos@facturascripts.com>
 * @author       Raul Jimenez             <comercial@nazcanetworks.com>
 * @collaborator Daniel Fernández Giménez <contacto@danielfg.es>
 */
class AccountingPlanImport
{
    /**
     * @var DataBase
     */
    protected $dataBase;

    /**
     * Ejercicio sobre el que se importa el plan contable.
     *
     * @var Ejercicio
     */
    protected $exercise;

    public function __construct()
    {
        $this->dataBase = new DataBase();
        $this->exercise = new Ejercicio();

        // forzamos la comprobación/creación de tablas antes de abrir la transacción,
        // porque dentro de la transacción los CREATE TABLE no se pueden hacer
        new Cuenta();
        new CuentaEspecial();
        new Subcuenta();
    }

    /**
     * Importa el plan contable desde un fichero CSV en el ejercicio indicado.
     * Todo el proceso se ejecuta en una transacción: si algo falla, se revierte.
     */
    public function importCSV(string $filePath, string $codejercicio): bool
    {
        if (false === $this->exercise->load($codejercicio)) {
            Tools::log()->error('exercise-not-found');
            return false;
        }

        if (false === file_exists($filePath)) {
            Tools::log()->warning('file-not-found', ['%fileName%' => $filePath]);
            return false;
        }

        try {
            $this->dataBase->beginTransaction();

            $this->updateSpecialAccounts();
            if (false === $this->processCsvData($filePath)) {
                $this->dataBase->rollback();
                return false;
            }

            $this->dataBase->commit();
            return true;
        } catch (Exception $exp) {
            $this->dataBase->rollback();
            Tools::log()->error($exp->getLine() . ' -> ' . $exp->getMessage());
            return false;
        }
    }

    /**
     * Importa el plan contable desde un fichero XML en el ejercicio indicado,
     * procesando en orden grupos, epígrafes, cuentas y subcuentas dentro de una
     * transacción. Si algo falla, se revierte todo.
     */
    public function importXML(string $filePath, string $codejercicio): bool
    {
        if (false === $this->exercise->load($codejercicio)) {
            Tools::log()->error('exercise-not-found');
            return false;
        }

        $data = $this->getData($filePath);
        if (is_array($data) || $data->count() == 0) {
            return false;
        }

        try {
            $this->dataBase->beginTransaction();

            $this->updateSpecialAccounts();
            if (false === $this->importEpigrafeGroup($data->grupo_epigrafes)) {
                $this->dataBase->rollback();
                return false;
            }
            if (false === $this->importEpigrafe($data->epigrafes)) {
                $this->dataBase->rollback();
                return false;
            }
            if (false === $this->importCuenta($data->cuenta)) {
                $this->dataBase->rollback();
                return false;
            }
            if (false === $this->importSubcuenta($data->subcuenta)) {
                $this->dataBase->rollback();
                return false;
            }

            $this->dataBase->commit();
            return true;
        } catch (Exception $exp) {
            $this->dataBase->rollback();
            Tools::log()->error($exp->getLine() . ' -> ' . $exp->getMessage());
            return false;
        }
    }

    /**
     * Crea una cuenta en el ejercicio si no existe. Si ya existe con ese código,
     * la deja como está (no actualiza descripción ni cuenta especial).
     */
    protected function createAccount(string $code, string $definition, ?string $parentCode = '', ?string $codcuentaesp = ''): bool
    {
        $account = new Cuenta();
        $where = [
            Where::eq('codejercicio', $this->exercise->codejercicio),
            Where::eq('codcuenta', $code)
        ];
        if ($account->loadWhere($where)) {
            return true;
        }

        $account->codcuenta = $code;
        $account->codcuentaesp = empty($codcuentaesp) ? null : $codcuentaesp;
        $account->codejercicio = $this->exercise->codejercicio;
        $account->descripcion = $definition;
        $account->parent_codcuenta = empty($parentCode) ? null : $parentCode;
        return $account->save();
    }

    /**
     * Crea una subcuenta en el ejercicio si no existe, ajustando además la
     * longitud de subcuenta del ejercicio al tamaño del primer código importado.
     */
    protected function createSubaccount(string $code, string $description, string $parentCode, ?string $codcuentaesp = ''): bool
    {
        $subaccount = new Subcuenta();
        $where = [
            Where::eq('codejercicio', $this->exercise->codejercicio),
            Where::eq('codsubcuenta', $code)
        ];
        if ($subaccount->loadWhere($where)) {
            return true;
        }

        // alineamos la longitud de subcuenta del ejercicio con el código que se está importando
        if ($this->exercise->longsubcuenta != strlen($code)) {
            $this->exercise->longsubcuenta = strlen($code);
            if (false === $this->exercise->save()) {
                return false;
            }
        }

        $subaccount->codcuenta = $parentCode;
        $subaccount->codcuentaesp = empty($codcuentaesp) ? null : $codcuentaesp;
        $subaccount->codejercicio = $this->exercise->codejercicio;
        $subaccount->codsubcuenta = $code;
        $subaccount->descripcion = $description;
        return $subaccount->save();
    }

    /**
     * Devuelve el contenido del XML como SimpleXMLElement, o un array vacío si el
     * fichero no existe.
     *
     * @param string $filePath
     *
     * @return SimpleXMLElement|array
     */
    protected function getData(string $filePath)
    {
        if (file_exists($filePath)) {
            return simplexml_load_string(file_get_contents($filePath));
        }

        return [];
    }

    /**
     * Importa las cuentas del XML (nodo <cuenta>) creándolas bajo su epígrafe.
     */
    protected function importCuenta(SimpleXMLElement $data): bool
    {
        foreach ($data as $xmlAccount) {
            $item = (array)$xmlAccount;
            if (false === $this->createAccount($item['codcuenta'], base64_decode($item['descripcion']), $item['codepigrafe'], $item['idcuentaesp'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Importa los epígrafes del XML (nodo <epigrafes>) creándolos bajo su grupo.
     */
    protected function importEpigrafe(SimpleXMLElement $data): bool
    {
        foreach ($data as $xmlEpigrafeElement) {
            $item = (array)$xmlEpigrafeElement;
            if (false === $this->createAccount($item['codepigrafe'], base64_decode($item['descripcion']), $item['codgrupo'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Importa los grupos del XML (nodo <grupo_epigrafes>) como cuentas raíz.
     */
    protected function importEpigrafeGroup(SimpleXMLElement $data): bool
    {
        foreach ($data as $xmlEpigrafeGroup) {
            $item = (array)$xmlEpigrafeGroup;
            if (false === $this->createAccount($item['codgrupo'], base64_decode($item['descripcion']))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Importa las subcuentas del XML (nodo <subcuenta>) bajo su cuenta padre.
     */
    protected function importSubcuenta(SimpleXMLElement $data): bool
    {
        foreach ($data as $xmlSubaccountElement) {
            $item = (array)$xmlSubaccountElement;
            if (false === $this->createSubaccount($item['codsubcuenta'], base64_decode($item['descripcion']), $item['codcuenta'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Lee el CSV (código, descripción, cuenta especial) y, deduciendo los niveles
     * de la jerarquía por la longitud de los códigos, crea cuentas para la longitud
     * mínima e intermedia, y subcuentas para la longitud máxima. El padre de cada
     * código se localiza buscando otro código que sea prefijo suyo.
     */
    protected function processCsvData(string $filePath): bool
    {
        $csv = new Csv();
        $csv->auto($filePath);

        // Verificar que el CSV tenga al menos 2 columnas (código y descripción)
        if (count($csv->titles) < 2) {
            Tools::log()->warning('csv-file-must-have-at-least-2-columns');
            return false;
        }

        $length = [];
        $accountPlan = [];
        foreach ($csv->data as $value) {
            $key0 = $value[$csv->titles[0]] ?? $value[0];
            if (strlen($key0) > 0) {
                $accountPlan[$key0] = [
                    'descripcion' => $value[$csv->titles[1]] ?? $value[1],
                    'codcuentaesp' => $value[$csv->titles[2]] ?? $value[2]
                ];
                $length[] = strlen($key0);
            }
        }

        $lengths = array_unique($length);
        sort($lengths);

        // Verificar que haya al menos 2 longitudes diferentes (cuentas y subcuentas)
        if (count($lengths) < 2) {
            Tools::log()->warning('accounting-plan-must-have-at-least-2-levels');
            return false;
        }

        $minLength = min($lengths);
        $maxLength = max($lengths);
        $keys = array_keys($accountPlan);
        ksort($accountPlan);

        foreach ($accountPlan as $key => $value) {
            switch (strlen($key)) {
                case $minLength:
                    $ok = $this->createAccount($key, $value['descripcion'], '', $value['codcuentaesp']);
                    break;

                case $maxLength:
                    $parentCode = $this->searchParent($keys, $key);
                    $ok = $this->createSubaccount($key, $value['descripcion'], $parentCode, $value['codcuentaesp']);
                    break;

                default:
                    $parentCode = $this->searchParent($keys, $key);
                    $ok = $this->createAccount($key, $value['descripcion'], $parentCode, $value['codcuentaesp']);
                    break;
            }

            if (false === $ok) {
                return false;
            }
        }

        return true;
    }

    /**
     * Devuelve el código padre del código indicado: el código más largo de la lista
     * que sea prefijo del actual (sin incluirlo a sí mismo).
     */
    protected function searchParent(array &$accountCodes, string $account): string
    {
        $parentCode = '';
        foreach ($accountCodes as $code) {
            $strCode = (string)$code;
            if ($strCode === $account) {
                continue;
            } elseif (strpos($account, $strCode) === 0 && strlen($strCode) > strlen($parentCode)) {
                $parentCode = $code;
            }
        }

        return $parentCode;
    }

    /**
     * Actualiza la tabla de cuentas especiales desde el CSV de datos por defecto
     * (Core/Data) antes de importar el plan, para que los códigos de cuenta
     * especial referenciados estén disponibles.
     */
    protected function updateSpecialAccounts(): void
    {
        $sql = CSVImport::updateTableSQL(CuentaEspecial::tableName());
        if (!empty($sql) && $this->dataBase->tableExists(CuentaEspecial::tableName())) {
            $this->dataBase->exec($sql);
        }
    }
}
