<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * Personalize the numeration and code of sale and purchase documents.
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class SecuenciaDocumento extends ModelClass
{
    use ModelTrait;

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

    /** @var bool */
    private static $pattern_test = false;

    /** @var string */
    public $tipodoc;

    /** @var bool */
    public $usarhuecos;

    public function clear()
    {
        parent::clear();
        $this->idempresa = Tools::settings('default', 'idempresa');
        $this->inicio = 1;
        $this->longnumero = 6;
        $this->numero = 1;
        $this->patron = '{EJE}{SERIE}{0NUM}';
        $this->usarhuecos = false;
    }

    public function disablePatternTest(bool $disable): void
    {
        self::$pattern_test = !$disable;
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
        // escapamos el html
        $this->patron = Tools::noHtml($this->patron);

        if (empty($this->idempresa)) {
            $this->idempresa = Tools::settings('default', 'idempresa');
        }

        if (empty($this->inicio) || $this->inicio < 1) {
            $this->inicio = 1;
        }

        if ($this->inicio > $this->numero) {
            $this->numero = $this->inicio;
        }

        // si usar huecos es false, tipodoc es FacturaCliente y el país predeterminado es España, mostramos aviso
        if (!$this->usarhuecos && 'FacturaCliente' === $this->tipodoc && 'ESP' === Tools::settings('default', 'codpais')) {
            Tools::log()->error('use-holes-invoices-esp');
        }

        return parent::test() && $this->testPatron();
    }

    public function url(string $type = 'auto', string $list = 'EditSettings?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function generateCode(): string
    {
        return strtr($this->patron, [
            '{FECHA}' => Tools::date(),
            '{HORA}' => Tools::hour(),
            '{FECHAHORA}' => Tools::dateTime(Tools::date() . ' ' . Tools::hour()),
            '{ANYO}' => date('Y'),
            '{DIA}' => date('d'),
            '{EJE}' => $this->codejercicio,
            '{EJE2}' => substr($this->codejercicio ?? '', -2),
            '{MES}' => date('m'),
            '{NUM}' => $this->numero,
            '{SERIE}' => $this->codserie,
            '{0NUM}' => str_pad($this->numero, $this->longnumero, '0', STR_PAD_LEFT),
            '{0SERIE}' => str_pad($this->codserie, 2, '0', STR_PAD_LEFT),
            '{NOMBREMES}' => Tools::lang()->trans('month-' . date('m'))
        ]);
    }

    protected function testPatron(): bool
    {
        if (false === self::$pattern_test) {
            return true;
        }

        if (empty($this->patron)) {
            Tools::log()->warning('empty-pattern');
            return false;
        }

        // si el patrón no tiene número, mostramos un aviso
        if (false === strpos($this->patron, '{NUM}') && false === strpos($this->patron, '{0NUM}')) {
            Tools::log()->warning('pattern-without-number');
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
        if (empty($this->codejercicio) && !$found) {
            Tools::log()->warning('pattern-without-year');
        }

        // si el patrón no tiene serie, mostramos un aviso
        if (false === strpos($this->patron, '{SERIE}') && false === strpos($this->patron, '{0SERIE}')) {
            Tools::log()->warning('pattern-without-serie');
        }

        // si el patrón generado tiene más de 20 caracteres, no dejamos guardar
        if (strlen($this->generateCode()) > 20) {
            Tools::log()->warning('pattern-too-long');
            return false;
        }

        return true;
    }
}
