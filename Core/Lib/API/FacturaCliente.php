<?php


namespace FacturaScripts\Core\Lib\API;


use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Tools;

class FacturaCliente extends APIModel
{
    /**
     * Returns an associative array with the resources, where the index is
     * the public name of the resource.
     *
     * @return array
     */
    public function getResources(): array
    {
        return [
            'facturacliente' => [
                'API' => 'FacturaScripts\Dinamic\Lib\API\FacturaCliente',
                'Name' => 'FacturaCliente'
            ]
        ];
    }

    public function doPOST(): bool
    {
        $model = new \FacturaScripts\Core\Model\FacturaCliente();
        $field = 'idfactura';
        $values = $this->request->request->all();

        $param0 = empty($this->params) ? '' : $this->params[0];
        $code = $values[$field] ?? $param0;
        if ($model->loadFromCode($code)) {
            $this->setError(Tools::lang()->trans('duplicate-record'), $model->toArray());
            return false;
        } elseif (empty($values)) {
            $this->setError(Tools::lang()->trans('no-data-received-form'));
            return false;
        }

        foreach ($values as $key => $value) {
            $model->{$key} = $value;
        }

        // Asignamos Subject
        if ($this->request->request->has('codcliente')) {
            $cliente = new Cliente();
            $cliente->loadFromCode($this->request->request->get('codcliente'));
            if ($cliente) {
                $model->setSubject($cliente);
            }
        }

        // GUARDAMOS MODELO
        if (false === $model->save()) {
            $message = Tools::lang()->trans('record-save-error');
            foreach (Tools::log()->read('', ['critical', 'error', 'info', 'notice', 'warning']) as $log) {
                $message .= ' - ' . $log['message'];
            }

            $this->setError($message, $model->toArray());
            return false;
        }

        // GUARDAMOS LINEAS
        if ($this->request->request->has('lineas')) {
            foreach ($this->request->request->get('lineas') as $linea) {
                $datosLinea = json_decode($linea, true);

                $linea = null;

                if (isset($datosLinea['referencia'])) {
                    $linea = $model->getNewProductLine($datosLinea['referencia']);
                } else {
                    $linea = $model->getNewLine();
                    $linea->pvpunitario = $datosLinea['pvpunitario'];
                    $linea->descripcion = $datosLinea['descripcion'];
                }

                if (!is_null($linea)) {
                    $linea->idfactura = $model->primaryColumnValue();
                    $linea->cantidad = $datosLinea['cantidad'];

                    if (false === $linea->save()) {
                        $message = Tools::lang()->trans('line-save-error');
                        foreach (Tools::log()->read('', ['critical', 'error', 'info', 'notice', 'warning']) as $log) {
                            $message .= ' - ' . $log['message'];
                        }

                        $this->setError($message, $model->toArray());
                        return false;
                    }
                }
            }
        }


        $this->setOk(Tools::lang()->trans('record-updated-correctly'), $model->toArray());
        return true;
    }
}