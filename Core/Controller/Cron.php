<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Exception;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Contract\ControllerInterface;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\WorkQueue;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\AlbaranProveedor;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;
use FacturaScripts\Dinamic\Model\CronJob;
use FacturaScripts\Dinamic\Model\Fabricante;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\LogMessage;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PedidoProveedor;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Dinamic\Model\PresupuestoProveedor;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\ReciboCliente;
use FacturaScripts\Dinamic\Model\ReciboProveedor;
use FacturaScripts\Dinamic\Model\WorkEvent;

class Cron implements ControllerInterface
{
    public function __construct(string $className, string $url = '')
    {
    }

    public function getPageData(): array
    {
        return [];
    }

    public function run(): void
    {
        header('Content-Type: text/plain');
        $this->echoLogo();

        Tools::log('cron')->notice('starting-cron');
        echo PHP_EOL . PHP_EOL . Tools::lang()->trans('starting-cron');
        ob_flush();

        // ejecutamos el cron de cada plugin
        $this->runPlugins();

        // ejecutamos los trabajos del core
        $this->runCoreJobs();

        // ejecutamos la cola de trabajos
        $this->runWorkQueue();

        // mostramos los mensajes del log
        $levels = ['critical', 'error', 'info', 'notice', 'warning'];
        foreach (Tools::log()::read('', $levels) as $message) {
            // si el canal no es master o database, no lo mostramos
            if (!in_array($message['channel'], ['master', 'database'])) {
                continue;
            }

            echo PHP_EOL . $message['message'];
            ob_flush();
        }

        // mensaje de finalización
        $context = [
            '%timeNeeded%' => Kernel::getExecutionTime(3),
            '%memoryUsed%' => $this->getMemorySize(memory_get_peak_usage())
        ];
        echo PHP_EOL . PHP_EOL . Tools::lang()->trans('finished-cron', $context) . PHP_EOL . PHP_EOL;
        Tools::log()->notice('finished-cron', $context);
    }

    private function echoLogo(): void
    {
        if (PHP_SAPI === 'cli') {
            echo <<<END

  ______         _                    _____           _       _       
 |  ____|       | |                  / ____|         (_)     | |      
 | |__ __ _  ___| |_ _   _ _ __ __ _| (___   ___ _ __ _ _ __ | |_ ___ 
 |  __/ _` |/ __| __| | | | '__/ _` |\___ \ / __| '__| | '_ \| __/ __|
 | | | (_| | (__| |_| |_| | | | (_| |____) | (__| |  | | |_) | |_\__ \
 |_|  \__,_|\___|\__|\__,_|_|  \__,_|_____/ \___|_|  |_| .__/ \__|___/
                                                       | |            
                                                       |_|
END;
        }
    }

    private function getMemorySize(int $size): string
    {
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
        return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $unit[$i];
    }

    private function job(string $name): CronJob
    {
        $job = new CronJob();
        $where = [
            new DataBaseWhere('jobname', $name),
            new DataBaseWhere('pluginname', null, 'IS')
        ];
        if (false === $job->loadFromCode('', $where)) {
            // no se había ejecutado nunca, lo creamos
            $job->jobname = $name;
        }

        return $job;
    }

    protected function removeOldLogs(): void
    {
        $maxDays = Tools::settings('default', 'days_log_retention', 90);
        if ($maxDays <= 0) {
            return;
        }

        $minDate = Tools::dateTime('-' . $maxDays . ' days');
        echo PHP_EOL . PHP_EOL . Tools::lang()->trans('removing-logs-until', ['%date%' => $minDate]) . ' ... ';
        ob_flush();

        $query = LogMessage::table()
            ->whereNotEq('channel', 'audit')
            ->whereLt('time', $minDate);

        if (false === $query->delete()) {
            Tools::log('cron')->warning('old-logs-delete-error');
            return;
        }

        Tools::log('cron')->notice('old-logs-delete-ok');
    }

    protected function removeOldWorkEvents(): void
    {
        $maxDays = Tools::settings('default', 'days_log_retention', 90);
        if ($maxDays <= 0) {
            return;
        }

        $minDate = Tools::dateTime('-' . $maxDays . ' days');

        $query = WorkEvent::table()
            ->whereEq('done', true)
            ->whereLt('creation_date', $minDate);

        if (false === $query->delete()) {
            Tools::log('cron')->warning('old-work-events-delete-error');
            return;
        }

        Tools::log('cron')->notice('old-work-events-delete-ok');
    }

    protected function runCoreJobs(): void
    {
        $this->job('update-attached-relations')
            ->everyDayAt(0)
            ->run(function () {
                $this->updateAttachedRelations();
            });

        $this->job('update-families')
            ->everyDayAt(1)
            ->run(function () {
                $this->updateFamilies();
            });

        $this->job('update-manufacturers')
            ->everyDayAt(2)
            ->run(function () {
                $this->updateManufacturers();
            });

        $this->job('remove-old-logs')
            ->everyDayAt(3)
            ->run(function () {
                $this->removeOldLogs();
                $this->removeOldWorkEvents();
            });

        $this->job('update-receipts')
            ->everyDayAt(4)
            ->run(function () {
                $this->updateReceipts();
            });
    }

    protected function runPlugins(): void
    {
        foreach (Plugins::enabled() as $pluginName) {
            $cronClass = '\\FacturaScripts\\Plugins\\' . $pluginName . '\\Cron';
            if (false === class_exists($cronClass)) {
                continue;
            }

            echo PHP_EOL . Tools::lang()->trans('running-plugin-cron', ['%pluginName%' => $pluginName]) . ' ... ';
            Tools::log('cron')->notice('running-plugin-cron', ['%pluginName%' => $pluginName]);

            try {
                $cron = new $cronClass($pluginName);
                $cron->run();
            } catch (Exception $ex) {
                echo $ex->getMessage() . PHP_EOL;
                Tools::log()->error($ex->getMessage());
            }

            ob_flush();

            // si no se está ejecutando en modo cli y lleva más de 20 segundos, se detiene
            if (PHP_SAPI != 'cli' && Kernel::getExecutionTime() > 20) {
                echo PHP_EOL . PHP_EOL . Tools::lang()->trans('cron-timeout');
                break;
            }
        }
    }

    protected function runWorkQueue(): void
    {
        $max = 1000;
        while ($max > 0) {
            if (false === WorkQueue::run()) {
                break;
            }

            --$max;

            // si no se está ejecutando en modo cli y lleva más de 25 segundos, terminamos
            if (PHP_SAPI != 'cli' && Kernel::getExecutionTime() > 25) {
                echo PHP_EOL . PHP_EOL . Tools::lang()->trans('cron-timeout');
                return;
            }
        }
    }

    protected function updateAttachedRelations(): void
    {
        echo PHP_EOL . PHP_EOL . Tools::lang()->trans('updating-attached-relations') . ' ... ';
        ob_flush();

        // si no hay relaciones con archivos adjuntos, terminamos
        $relationModel = new AttachedFileRelation();
        if (0 === $relationModel->count()) {
            return;
        }

        // elegimos un modelo al azar
        $models = [
            new AlbaranCliente(), new FacturaCliente(), new PedidoCliente(), new PresupuestoCliente(),
            new AlbaranProveedor(), new FacturaProveedor(), new PedidoProveedor(), new PresupuestoProveedor()
        ];
        shuffle($models);
        echo $models[0]->modelClassName();
        ob_flush();

        // recorremos todos los documentos
        $limit = 100;
        $offset = 0;
        $orderBy = ['codigo' => 'ASC'];
        $documents = $models[0]->all([], $orderBy, 0, $limit);
        while (!empty($documents)) {
            foreach ($documents as $doc) {
                $where = [new DataBaseWhere('model', $doc->modelClassName())];
                $where[] = is_numeric($doc->primaryColumnValue()) ?
                    new DataBaseWhere('modelid|modelcode', $doc->primaryColumnValue()) :
                    new DataBaseWhere('modelcode', $doc->primaryColumnValue());

                $num = $relationModel->count($where);
                if ($num == $doc->numdocs) {
                    continue;
                }

                $doc->numdocs = $num;
                if (false === $doc->save()) {
                    Tools::log('cron')->error('record-save-error', [
                        '%model%' => $doc->modelClassName(),
                        '%id%' => $doc->primaryColumnValue()
                    ]);
                    break;
                }
            }

            $offset += $limit;
            $documents = $models[0]->all([], $orderBy, $offset, $limit);
        }
    }

    protected function updateFamilies(): void
    {
        echo PHP_EOL . PHP_EOL . Tools::lang()->trans('updating-families') . ' ... ';
        ob_flush();

        $producto = new Producto();

        // recorremos todas las familias para actualizar su contador de productos
        foreach (Familia::all([], [], 0, 0) as $familia) {
            $count = $producto->count([new DataBaseWhere('codfamilia', $familia->codfamilia)]);
            if ($familia->numproductos == $count) {
                continue;
            }

            $familia->numproductos = $count;
            $familia->save();
        }
    }

    protected function updateManufacturers(): void
    {
        echo PHP_EOL . PHP_EOL . Tools::lang()->trans('updating-manufacturers') . ' ... ';
        ob_flush();

        $producto = new Producto();

        // recorremos todos los fabricantes para actualizar su contador de productos
        foreach (Fabricante::all([], [], 0, 0) as $fabricante) {
            $count = $producto->count([new DataBaseWhere('codfabricante', $fabricante->codfabricante)]);
            if ($fabricante->numproductos == $count) {
                continue;
            }

            $fabricante->numproductos = $count;
            $fabricante->save();
        }
    }

    protected function updateReceipts(): void
    {
        echo PHP_EOL . PHP_EOL . Tools::lang()->trans('updating-receipts') . ' ... ';
        ob_flush();

        // recorremos todos los recibos de compra impagados con fecha anterior a hoy
        $where = [
            new DataBaseWhere('pagado', false),
            new DataBaseWhere('vencimiento', Tools::date(), '<')
        ];
        foreach (ReciboProveedor::all($where, [], 0, 0) as $recibo) {
            // si el código de factura ha cambiado, lo guardamos
            $factura = $recibo->getInvoice();
            if ($recibo->codigofactura != $factura->codigo) {
                $recibo->codigofactura = $factura->codigo;
            }

            // guardamos para que se actualice
            $recibo->save();
        }

        // recorremos todos los recibos de venta impagados con fecha anterior a hoy
        foreach (ReciboCliente::all($where, [], 0, 0) as $recibo) {
            // si el código de factura ha cambiado, lo guardamos
            $factura = $recibo->getInvoice();
            if ($recibo->codigofactura != $factura->codigo) {
                $recibo->codigofactura = $factura->codigo;
            }

            // guardamos para que se actualice
            $recibo->save();
        }
    }
}
