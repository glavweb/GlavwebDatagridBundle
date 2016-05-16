<?php

/*
 * This file is part of the Glavweb DatagridBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\Command;

use Glavweb\DatagridBundle\Generator\ScopeGenerator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateScopeCommand
 *
 * @author Nilov Andrey <nilov@glavweb.ru>
 * @package Glavweb\DatagridBundle
 */
class GenerateScopeCommand extends ContainerAwareCommand
{
    /**
     * Configure
     */
    protected function configure()
    {
        $this
            ->setName('glavweb:data-schema:scope')
            ->setDescription('Generates a scope based on the given model class')
            ->addArgument('model', InputArgument::REQUIRED, 'The fully qualified model class')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container         = $this->getContainer();
        $modelClass        = $this->validateClass($input->getArgument('model'));
        $doctrine          = $this->getContainer()->get('doctrine');
        $skeletonDirectory = __DIR__ . '/../Resources/skeleton';

        // Fixture file
        try {
            $scopeGenerator = new ScopeGenerator(
                $container->get('kernel'),
                $doctrine,
                $container->getParameter('glavweb_datagrid.scope_dir'),
                $skeletonDirectory
            );

            $scopeGenerator->generate($modelClass);
            $output->writeln(sprintf(
                '%sThe scope files "<info>%s</info>" has been generated.',
                PHP_EOL,
                realpath($scopeGenerator->getTemplateFile())
            ));

        } catch (\Exception $e) {
            $this->writeError($output, $e->getMessage());
        }

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param string $message
     */
    protected function writeError(OutputInterface $output, $message)
    {
        $output->writeln(sprintf("\n<error>%s</error>", $message));
    }

    /**
     *
     * @param string $class
     * @return string
     * @throws \InvalidArgumentException
     */
    public function validateClass($class)
    {
        $class = str_replace('/', '\\', $class);

        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('The class "%s" does not exist.', $class));
        }

        return $class;
    }
}
