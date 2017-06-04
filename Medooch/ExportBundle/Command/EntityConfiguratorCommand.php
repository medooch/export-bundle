<?php
/**
 * Copyright (c) 2017.
 */

namespace Medooch\Bundles\ExportBundle\Command;

use Medooch\Components\Helper\Yml\YamlManipulator;
use Sensio\Bundle\GeneratorBundle\Command\AutoComplete\EntitiesAutoCompleter;
use Sensio\Bundle\GeneratorBundle\Command\GenerateDoctrineCrudCommand;
use Sensio\Bundle\GeneratorBundle\Command\Validators;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class EntityConfiguratorCommand
 * @package Medooch\Bundles\ExportBundle\Command
 */
class EntityConfiguratorCommand extends GenerateDoctrineCrudCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('medooch:export:configure')
            ->setDescription('Configure entity in the config.yml')->setDefinition(array(
                new InputArgument('entity', InputArgument::OPTIONAL, 'The entity class name to initialize (shortcut notation)'),
                new InputOption('entity', '', InputOption::VALUE_REQUIRED, 'The entity class name to initialize (shortcut notation)'),
            ));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();

        if ($input->isInteractive()) {
            $question = new ConfirmationQuestion($questionHelper->getQuestion('Do you confirm generation', 'yes', '?'), true);
            if (!$questionHelper->ask($input, $output, $question)) {
                $output->writeln('<error>Command aborted</error>');
                return 1;
            }
        }

        $entity = Validators::validateEntityName($input->getOption('entity'));
        list($bundle, $entity) = $this->parseShortcutNotation($entity);

        try {
            $this->entityClass = $this->getContainer()->get('doctrine')->getAliasNamespace($bundle) . '\\' . $entity;
            $metadata = $this->getEntityMetadata($this->entityClass);
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf('Entity "%s" does not exist in the "%s" bundle. Create it with the "doctrine:generate:entity" command and then execute this command again.', $entity, $bundle));
        }

        $this->updateCrudConfigurations($entity, $metadata[0]);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {

        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to the Export Configuration generator');

        // namespace
        $output->writeln(array(
            '',
            'This command helps you generate auto configuration for an entity.',
            '',
            'First, give the name of the existing entity for which you want to generate a export configuration',
            '(use the shortcut notation like <comment>AcmeBlogBundle:Post</comment>)',
            '',
        ));

        if ($input->hasArgument('entity') && $input->getArgument('entity') != '') {
            $input->setOption('entity', $input->getArgument('entity'));
        }

        $question = new Question($questionHelper->getQuestion('The Entity shortcut name', $input->getOption('entity')), $input->getOption('entity'));
        $question->setValidator(array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateEntityName'));


        $autocompleter = new EntitiesAutoCompleter($this->getContainer()->get('doctrine')->getManager());
        $autocompleteEntities = $autocompleter->getSuggestions();
        $question->setAutocompleterValues($autocompleteEntities);
        $entity = $questionHelper->ask($input, $output, $question);

        $input->setOption('entity', $entity);
        list($bundle, $entity) = $this->parseShortcutNotation($entity);

        try {
            $entityClass = $this->getContainer()->get('doctrine')->getAliasNamespace($bundle) . '\\' . $entity;
            $this->getEntityMetadata($entityClass);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Entity "%s" does not exist in the "%s" bundle. You may have mistyped the bundle name or maybe the entity doesn\'t exist yet (create it first with the "doctrine:generate:entity" command).', $entity, $bundle));
        }
    }

    /**
     * @param $entity
     * @param $metadata
     */
    private function updateCrudConfigurations($entity, $metadata)
    {
        /** add exporter configuration */
        $configurationFile = $this->getContainer()->get('kernel')->getRootDir() . '/config/config.yml';
        $parameters = YamlManipulator::getParameters($configurationFile);
        if (!array_key_exists('export', $parameters)) {
            $parameters['export'] = [];
        }
        if (!array_key_exists('entities', $parameters['export'])) {
            $parameters['export']['entities'] = [];
        }
        $fields = [];
        foreach ($metadata->fieldMappings as $field => $fieldMapping) {
            if (in_array($fieldMapping['type'], ['string', 'integer', 'boolean']))
                $fields[] = 'e.' . $field;
        }
        if (!array_key_exists(strtolower($entity), $parameters['export']['entities'])) {
            $parameters['export']['entities'][strtolower($entity)]['class'] = $this->entityClass;
            $parameters['export']['entities'][strtolower($entity)]['query'] = ['select' => $fields];
        }
        YamlManipulator::updateParameters($configurationFile, $parameters);
    }
}
