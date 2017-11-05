<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

/**
 * El agente/empleado es el que se asocia a un albarán, factura o caja.
 * Cada usuario puede estar asociado a un agente, y un agente puede
 * estar asociado a varios usuarios o a ninguno.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Agente
{

    use Base\ModelTrait;
    use Base\ContactInformation;

    /**
     * Clave primaria. Varchar (10).
     *
     * @var int
     */
    public $codagente;

    /**
     * Identificador fiscal (CIF/NIF).
     *
     * @var string
     */
    public $dnicif;

    /**
     * Nombre del agente o empleado.
     *
     * @var string
     */
    public $nombre;

    /**
     * Apellidos del agente o empleado.
     *
     * @var string
     */
    public $apellidos;

    /**
     * Nº de la seguridad social.
     *
     * @var string
     */
    public $seg_social;

    /**
     * cargo en la empresa.
     *
     * @var string
     */
    public $cargo;

    /**
     * Cuenta bancaria
     *
     * @var string
     */
    public $banco;

    /**
     * Fecha de nacimiento.
     *
     * @var string
     */
    public $f_nacimiento;

    /**
     * Fecha de alta en la empresa.
     *
     * @var string
     */
    public $f_alta;

    /**
     * Fecha de baja en la empresa.
     *
     * @var string
     */
    public $f_baja;

    /**
     * Porcentaje de comisión del agente. Se utiliza en presupuestos, pedidos, albaranes y facturas.
     *
     * @var float|int
     */
    public $porcomision;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'agentes';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codagente';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->clearContactInformation();

        $this->codagente = null;
        $this->nombre = '';
        $this->apellidos = '';
        $this->dnicif = '';
        $this->porcomision = 0.00;
        $this->seg_social = null;
        $this->banco = null;
        $this->cargo = null;
        $this->f_alta = date('d-m-Y');
        $this->f_baja = null;
        $this->f_nacimiento = date('d-m-Y');
    }

    /**
     * Devuelve nombre + apellidos del agente.
     *
     * @return string
     */
    public function fullName()
    {
        return $this->nombre . ' ' . $this->apellidos;
    }

    /**
     * Comprueba los datos del empleado/agente, devuelve TRUE si son correctos
     *
     * @return bool
     */
    public function test()
    {
        $this->apellidos = self::noHtml($this->apellidos);
        $this->banco = self::noHtml($this->banco);
        $this->cargo = self::noHtml($this->cargo);
        $this->ciudad = self::noHtml($this->ciudad);
        $this->codpostal = self::noHtml($this->codpostal);
        $this->direccion = self::noHtml($this->direccion);
        $this->dnicif = self::noHtml($this->dnicif);
        $this->email = self::noHtml($this->email);
        $this->nombre = self::noHtml($this->nombre);
        $this->provincia = self::noHtml($this->provincia);
        $this->seg_social = self::noHtml($this->seg_social);
        $this->telefono = self::noHtml($this->telefono);

        if (!(strlen($this->nombre) > 1) && !(strlen($this->nombre) < 50)) {
            $this->miniLog->alert($this->i18n->trans('agent-name-between-1-50'));

            return false;
        }

        if ($this->codagente === null) {
            $this->codagente = $this->newCode();
        }

        return true;
    }

    /**
     * Crea la consulta necesaria para crear un nuevo agente en la base de datos.
     *
     * @return string
     */
    public function install()
    {
        return 'INSERT INTO ' . $this->tableName() . ' (codagente,nombre,apellidos,dnicif)'
            . " VALUES ('1','Paco','Pepe','00000014Z');";
    }
}
