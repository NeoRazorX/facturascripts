<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Description of CreateControllerCommand
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class CreateControllerCommand extends Command
{
    protected function configure()
    {
        $this->setName('app:create-controller')
            ->setDescription('Crea un nuevo Controlador')
            ->setHelp('Este comando permite crear un nuevo controlador de FS2018')
            ->addArgument('controller_name',InputArgument::OPTIONAL, 'El nombre del controlador.');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $output->writeln([
            'Controller Create',
            '=================',
            'Nombre del controlador: '.$input->getArgument('controller_name'),
        ]);
        
        $controlador_name = $input->getArgument('controller_name');
        
        if(!$input->getArgument('controller_name')){
            $question = new Question('Ingresa el nombre del Controllador: ', 'FS2018');
            $controlador_name = $helper->ask($input, $output, $question);
        }
        
        $elegir_controlador = new ChoiceQuestion(
            '¿Que tipo de Controlador se va crear?', array('PanelController', 'EditController', 'ListController'), 1
        );
        
        $elegir_controlador->setErrorMessage('El Tipo de Controlador no es válido');
        
        $tipo_controlador = $helper->ask($input, $output, $elegir_controlador);

        $output->writeln('Se va crear el controlador '.$controlador_name.' del tipo '.$tipo_controlador);
        //$output->writeln('Aquí deberas elegir que tipo de Controller se creará');
    }
}
