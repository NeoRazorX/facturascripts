<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DocumentStitcher
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class DocumentStitcher extends Base\Controller
{
    /**
     * Model name source.
     *
     * @var string
     */
    public $modelName;

    /**
     * Array of document primary keys.
     *
     * @var array
     */
    public $codes;

    /**
     * Array of documents.
     *
     * @var array
     */
    public $docs;

    /**
     * Array of lines of documents.
     *
     * @var array
     */
    public $linesDocs;

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'sales';
        $pageData['title'] = 'document-stitcher';
        $pageData['icon'] = 'fa-thumb-tack';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response                   $response
     * @param Model\User                 $user
     * @param Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // Read model received
        $this->modelName = $this->request->get('model', '');

        // Operations with data, before execute action
        if (!$this->execPrevious($this->modelName)) {
            return;
        }

        // Store action to execute
        $action = $this->request->get('action', '');
        // Operations with data, after execute action
        $this->execAfterAction($action);
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $modelName
     *
     * @return bool
     */
    protected function execPrevious($modelName)
    {
        switch ($modelName) {
            case 'AlbaranCliente':
                /// no break
            case 'AlbaranProveedor':
                /// no break
            case 'PedidoCliente':
                /// no break
            case 'PedidoProveedor':
                /// no break
            case 'PresupuestoCliente':
                /// no break
            case 'PresupuestoProveedor':
                $this->codes = \explode(',', trim($this->request->get('codes', [])));
                if (empty($this->codes)) {
                    $this->miniLog->alert('no-codes-received');
                    return false;
                }

                foreach ($this->codes as $code) {
                    $modelClass = 'FacturaScripts\\Dinamic\\Model\\' . $modelName;
                    $doc = new $modelClass();
                    if ($doc->loadFromCode($code)) {
                        $this->docs[$code] = $doc;
                        $this->linesDocs[$code] = $doc->getLines();
                    }
                }
                break;

            default:
                $this->miniLog->alert('no-modelname-data-received-or-not-supported');
                break;
        }

        return true;
    }

    /**
     * Runs the controller actions after data read.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'submit':
                $this->generateNextDocument();
                break;
        }
        return true;
    }

    /**
     * @return bool
     */
    private function generateNextDocument()
    {
        $data = $this->request->request->all();
        $modelName = $data['model'];
        $codes = $data['code'];
        $modelClass = 'FacturaScripts\\Dinamic\\Model\\' . $modelName;
        $docSource = new $modelClass();
        $lineModelClass = 'FacturaScripts\\Dinamic\\Model\\Linea' . $modelName;
        $lines = [];
        foreach ($codes as $code) {
            $line = new $lineModelClass();
            if ($line->loadFromCode($code)) {
                $lines[] = $line;
            }
        }
        $primaryKey = $docSource->primaryColumn();
        $docSource->loadFromCode($line->{$primaryKey});

        // Look for EstadoDocumento as user was selected 'Aprobado' TODO: must be a translatable string
        $docStatus = new Model\EstadoDocumento();
        $where = [
            new Base\DataBase\DataBaseWhere('tipodoc', $modelName),
            new Base\DataBase\DataBaseWhere('nombre', 'Aprobado')
        ];
        if (!$docStatus->loadFromCode('', $where)) {
            return false;
        }
        $destModelClass = 'FacturaScripts\\Dinamic\\Model\\' . $docStatus->generadoc;
        $destLineModelClass = 'FacturaScripts\\Dinamic\\Model\\Linea' . $docStatus->generadoc;
        $docDestiny = new $destModelClass();
        if ($this->assignDocumentValues($docSource, $docDestiny, $docStatus)) {
            foreach ($lines as $pos => $lineSource) {
                $lineDestiny = new $destLineModelClass();
                $this->assignLineValues($lineSource, $lineDestiny, $docSource, $docDestiny, $docStatus, $pos);
            }

            $businessDocTools = new BusinessDocumentTools();
            $businessDocTools->recalculate($docDestiny);
            if ($docDestiny->save()) {
                $this->miniLog->notice(
                    $this->i18n->trans(
                        'document-generated-successfully',
                        [
                            '%code%' => $docDestiny->codigo,
                            '%url%' => $docDestiny->url()
                        ]
                    )
                );
                return true;
            }

            $this->miniLog->error($this->i18n->trans('document-not-generated'));
        }

        return false;
    }

    /**
     * Assign document values from one to another.
     *
     * @param Model\Base\BusinessDocument $docSource
     * @param Model\Base\BusinessDocument $docDestiny
     * @param Model\EstadoDocumento       $docStatus
     *
     * @return bool
     */
    private function assignDocumentValues(&$docSource, &$docDestiny, $docStatus)
    {
        $status = false;
        $this->setCommonValues($docSource, $docDestiny, $docStatus);

        $className = \str_replace('FacturaScripts\Dinamic\Model\\', '', \get_class($docDestiny));
        switch ($className) {
            case 'AlbaranCliente':
                $this->setCustomerValues($docSource, $docDestiny);
                $status = $docDestiny->save();
                $docSource->idfactura = $docDestiny->idfactura;
                break;
            case 'AlbaranProveedor':
                $this->setSupplierValues($docSource, $docDestiny);
                $status = $docDestiny->save();
                $docSource->idfactura = $docDestiny->idfactura;
                break;
            case 'PedidoCliente':
                $this->setCustomerValues($docSource, $docDestiny);
                $status = $docDestiny->save();
                $docSource->idalbaran = $docDestiny->idalbaran;
                break;
            case 'PedidoProveedor':
                $this->setSupplierValues($docSource, $docDestiny);
                $status = $docDestiny->save();
                $docSource->idalbaran = $docDestiny->idalbaran;
                break;
            case 'PresupuestoCliente':
                $this->setCustomerValues($docSource, $docDestiny);
                $status = $docDestiny->save();
                break;
            case 'PresupuestoProveedor':
                $this->setSupplierValues($docSource, $docDestiny);
                $status = $docDestiny->save();
                break;
            case 'FacturaCliente':
                $this->setCustomerValues($docSource, $docDestiny);
                $status = $docDestiny->save();
                break;
            case 'FacturaProveedor':
                $this->setSupplierValues($docSource, $docDestiny);
                $status = $docDestiny->save();
                break;
        }

        $docSource->save();
        return $status;
    }

    /**
     * @param Model\Base\BusinessDocument $docSource
     * @param Model\Base\BusinessDocument $docDestiny
     * @param Model\EstadoDocumento       $docStatus
     */
    private function setCommonValues(&$docSource, &$docDestiny, $docStatus)
    {
        // Values from document
        $docDestiny->cifnif = $docSource->cifnif;
        $docDestiny->codagente = $docSource->codagente;
        $docDestiny->codalmacen = $docSource->codalmacen;
        $docDestiny->coddivisa = $docSource->coddivisa;
        $docDestiny->codejercicio = $docSource->codejercicio;
        $docDestiny->codpago = $docSource->codpago;
        $docDestiny->codserie = $docSource->codserie;
        $docDestiny->editable = $docSource->editable;
        $docDestiny->fecha = date('d-m-Y');
        $docDestiny->hora = date('H:i:s');
        $docDestiny->idempresa = $docSource->idempresa;
        $docDestiny->numero = $docSource->numero;
        $docDestiny->observaciones = $docSource->observaciones;
        $docDestiny->tasaconv = $docSource->tasaconv;
        // Values from document status
        $docDestiny->editable = $docStatus->editable;
        $docDestiny->idestado = $docStatus->idestado;
    }

    /**
     * Set specific values for customers documents.
     *
     * @param Model\Base\BusinessDocument $docSource
     * @param Model\Base\BusinessDocument $docDestiny
     */
    private function setCustomerValues(&$docSource, &$docDestiny)
    {
        $docDestiny->nombrecliente = $docSource->nombrecliente;
        $docDestiny->apartado = $docSource->apartado;
        $docDestiny->apartadoenv = $docSource->apartadoenv;
        $docDestiny->apellidosenv = $docSource->apellidosenv;
        $docDestiny->ciudad = $docSource->ciudad;
        $docDestiny->ciudadenv = $docSource->ciudadenv;
        $docDestiny->codcliente = $docSource->codcliente;
        $docDestiny->coddir = $docSource->coddir;
        $docDestiny->codigoenv = $docSource->codigoenv;
        $docDestiny->codpais = $docSource->codpais;
        $docDestiny->codpaisenv = $docSource->codpaisenv;
        $docDestiny->codpostal = $docSource->codpostal;
        $docDestiny->codpostalenv = $docSource->codpostalenv;
        $docDestiny->codtrans = $docSource->codtrans;
        $docDestiny->direccion = $docSource->direccion;
        $docDestiny->direccionenv = $docSource->direccionenv;
        $docDestiny->nombreenv = $docSource->nombreenv;
        $docDestiny->numero2 = $docSource->numero2;
        $docDestiny->porcomision = $docSource->porcomision;
        $docDestiny->provincia = $docSource->provincia;
        $docDestiny->provinciaenv = $docSource->provinciaenv;
    }

    /**
     * Set specific values for suppliers documents.
     *
     * @param Model\Base\BusinessDocument $docSource
     * @param Model\Base\BusinessDocument $docDestiny
     */
    private function setSupplierValues(&$docSource, &$docDestiny)
    {
        $docDestiny->nombre = $docSource->nombre;
        $docDestiny->codproveedor = $docSource->codproveedor;
        $docDestiny->numproveedor = $docSource->numproveedor;
    }

    /**
     * Assign line values from one document to another.
     *
     * @param Model\Base\BusinessDocumentLine $lineSource
     * @param Model\Base\BusinessDocumentLine $lineDestiny
     * @param Model\Base\BusinessDocument     $docSource
     * @param Model\Base\BusinessDocument     $docDestiny
     * @param Model\EstadoDocumento           $docStatus
     * @param int                             $pos
     *
     * @return bool
     */
    private function assignLineValues(&$lineSource, &$lineDestiny, $docSource, $docDestiny, $docStatus, $pos = 0)
    {
        // Values from lines
        $lineDestiny->cantidad = $lineSource->cantidad;
        $lineDestiny->codcombinacion = $lineSource->codcombinacion;
        $lineDestiny->codimpuesto = $lineSource->codimpuesto;
        $lineDestiny->descripcion = $lineSource->descripcion;
        $lineDestiny->iva = $lineSource->iva;
        $lineDestiny->dtopor = $lineSource->dtopor;
        $lineDestiny->idlinea = $pos;
        $lineDestiny->irpf = $lineSource->irpf;
        $lineDestiny->orden = $pos;
        $lineDestiny->pvpsindto = $lineSource->pvpsindto;
        $lineDestiny->pvptotal = $lineSource->pvptotal;
        $lineDestiny->pvpunitario = $lineSource->pvpunitario;
        $lineDestiny->recargo = $lineSource->recargo;
        $lineDestiny->referencia = $lineSource->referencia;

        // Value from document status
        $lineDestiny->actualizastock = $docStatus->actualizastock;

        $className = \str_replace('FacturaScripts\Dinamic\Model\\', '', \get_class($docDestiny));

        if (strpos($className, 'Cliente') !== false) {
            // Must respect this values when the new document is generated?
            $lineDestiny->mostrar_cantidad = $lineSource->mostrar_cantidad;
            $lineDestiny->mostrar_precio = $lineSource->mostrar_precio;
            $lineDestiny->orden = $lineSource->orden + $pos;
        }

        switch ($className) {
            case 'AlbaranCliente':
                //$lineDestiny->porcomision = $lineSource->porcomision;
            case 'AlbaranProveedor':
                $lineDestiny->idalbaran = $docDestiny->idalbaran;
                $lineDestiny->idlineapedido = $lineSource->idlinea;
                $lineDestiny->idpedido = $docSource->idpedido;
                break;
            case 'PedidoCliente':
                //$lineDestiny->porcomision = $lineSource->porcomision;
                /// no break
            case 'PedidoProveedor':
                $lineDestiny->idpedido = $docDestiny->idpedido;
                $lineDestiny->idlineapresupuesto = $lineSource->idlinea;
                $lineDestiny->idpresupuesto = $docSource->idpresupuesto;
                break;
            case 'PresupuestoCliente':
                //$lineDestiny->porcomision = $lineSource->porcomision;
                /// no break
            case 'PresupuestoProveedor':
                $lineDestiny->idpresupuesto = $docDestiny->idpresupuesto;
                break;
            case 'FacturaCliente':
                $lineDestiny->porcomision = $lineSource->porcomision;
                /// no break
            case 'FacturaProveedor':
                $lineDestiny->idfactura = $docDestiny->idfactura;
                $lineDestiny->idlineaalbaran = $lineSource->idlinea;
                $lineDestiny->idalbaran = $docSource->idalbaran;
                break;
        }

        return $lineDestiny->save();
    }
}
