<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\Model;
use RuntimeException;
use Symfony\Component\Translation\Exception\InvalidArgumentException as TranslationInvalidArgumentException;

/**
 * Una serie de facturación o contabilidad, para tener distinta numeración
 * en cada serie.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Serie
{
    use Model;

    /**
     * Clave primaria. Varchar (2).
     * @var string
     */
    public $codserie;

    /**
     * Descripción de la serie de facturación
     * @var string
     */
    public $descripcion;

    /**
     * TRUE -> las facturas asociadas no encluyen IVA.
     * @var bool
     */
    public $siniva;

    /**
     * % de retención IRPF de las facturas asociadas.
     * @var float
     */
    public $irpf;

    /**
     * ejercicio para el que asignamos la numeración inicial de la serie.
     * @var string
     */
    public $codejercicio;

    /**
     * numeración inicial para las facturas de esta serie.
     * @var integer
     */
    public $numfactura;

    /**
     * Serie constructor.
     * @param array $data
     * @throws RuntimeException
     * @throws TranslationInvalidArgumentException
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'series', 'codserie');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }

    /**
     * TODO
     */
    public function clear()
    {
        $this->codserie = '';
        $this->descripcion = '';
        $this->siniva = false;
        $this->irpf = 0.00;
        $this->codejercicio = null;
        $this->numfactura = 1;
    }

    /**
     * Crea la consulta necesaria para crear una nueva serie en la base de datos.
     * @return string
     */
    public function install()
    {
        return 'INSERT INTO ' . $this->tableName() . ' (codserie,descripcion,siniva,irpf) VALUES '
                . "('A','SERIE A',FALSE,'0'),('R','RECTIFICATIVAS',FALSE,'0');";
    }

    /**
     * Devuelve la url donde ver/modificar la serie
     * @return string
     */
    public function url()
    {
        if ($this->codserie === null) {
            return 'index.php?page=contabilidad_series';
        }

        return 'index.php?page=contabilidad_series#' . $this->codserie;
    }

    /**
     * Devuelve TRUE si la serie es la predeterminada de la empresa
     * @return bool
     */
    public function isDefault()
    {
        return ( $this->codserie === $this->defaultItems->codSerie() );
    }

    /**
     * Comprueba los datos de la serie, devuelve TRUE si son correctos
     * @return bool
     * @throws TranslationInvalidArgumentException
     */
    public function test()
    {
        $status = false;

        $this->codserie = trim($this->codserie);
        $this->descripcion = static::noHtml($this->descripcion);

        if ($this->numfactura < 1) {
            $this->numfactura = 1;
        }

        if (!preg_match('/^[A-Z0-9]{1,2}$/i', $this->codserie)) {
            $this->miniLog->alert($this->i18n->trans('serie-cod-invalid'));
        } elseif (!(strlen($this->descripcion) > 1) && !(strlen($this->descripcion) < 100)) {
            $this->miniLog->alert($this->i18n->trans('serie-desc-invalid'));
        } else {
            $status = true;
        }

        return $status;
    }
}
