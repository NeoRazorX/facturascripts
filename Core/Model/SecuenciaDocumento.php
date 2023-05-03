<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Personalize the numeration and code of sale and purchase documents.
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class SecuenciaDocumento extends Base\ModelClass
{
    use Base\ModelTrait;

    /** @var string */
    public $codejercicio;

    /** @var string */
    public $codserie;

    /** @var int */
    public $idempresa;

    /** @var int */
    public $idsecuencia;

    /** @var int */
    public $inicio;

    /** @var int */
    public $longnumero;

    /** @var int */
    public $numero;

    /** @var string */
    public $patron;

    /** @var string */
    public $tipodoc;

    /** @var bool */
    public $usarhuecos;

    public function clear()
    {
        parent::clear();
        $this->inicio = 1;
        $this->longnumero = 6;
        $this->numero = 1;
        $this->patron = '{EJE}{SERIE}{0NUM}';
        $this->usarhuecos = false;
    }

    public function install(): string
    {
        // needed dependencies
        new Ejercicio();
        new Serie();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idsecuencia';
    }

    public static function tableName(): string
    {
        return 'secuencias_documentos';
    }

    public function test(): bool
    {
        if (empty($this->idempresa)) {
            $this->idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa');
        }

        if (empty($this->inicio) || $this->inicio < 1) {
            $this->inicio = 1;
        }

        if ($this->inicio > $this->numero) {
            $this->numero = $this->inicio;
        }

        return parent::test() && $this->testPatron();
    }

    public function url(string $type = 'auto', string $list = 'EditSettings?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function testPatron(): bool
    {
        $this->patron = $this->toolBox()->utils()->noHtml($this->patron);
        if (empty($this->patron)) {
            $this->toolBox()->i18nLog()->warning('empty-pattern');
            return false;
        }

        // si el patrón no tiene número, mostramos un aviso
        if (false === strpos($this->patron, '{NUM}') && false === strpos($this->patron, '{0NUM}')) {
            $this->toolBox()->i18nLog()->warning('pattern-without-number');
            return false;
        }

        // si el patrón no tiene ejercicio o fecha, mostramos un aviso
        $codes = ['{EJE}', '{EJE2}', '{ANYO}', '{FECHA}', '{FECHAHORA}'];
        $found = false;
        foreach ($codes as $code) {
            if (false !== strpos($this->patron, $code)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $this->toolBox()->i18nLog()->warning('pattern-without-year');
        }

        // si el patrón no tiene serie, mostramos un aviso
        if (false === strpos($this->patron, '{SERIE}')) {
            $this->toolBox()->i18nLog()->warning('pattern-without-serie');
        }

        return true;
    }
}
