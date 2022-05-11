<?php

/**
 * Retailplace_AttributesUpdater
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AttributesUpdater\Console\Command;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Retailplace\AttributesUpdater\Model\UpdatersManagement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AttributeUpdaters
 */
class AttributeUpdaters extends Command
{
    /** @var string */
    public const COMMAND_NAME = 'retailplace:attribute-updaters:run';
    public const INPUT_KEY_UPDATERS = 'updaters';
    public const INPUT_KEY_LIST = 'list';
    public const INPUT_KEY_SKUS = 'skus';

    /** @var \Magento\Framework\App\State */
    private $state;

    /** @var \Retailplace\AttributesUpdater\Model\UpdatersManagement */
    private $updatersManagement;

    /**
     * QuoteAddressCompanyUpdate constructor.
     *
     * @param \Magento\Framework\App\State $state
     * @param \Retailplace\AttributesUpdater\Model\UpdatersManagement $updatersManagement
     * @param string|null $name
     */
    public function __construct(
        State $state,
        UpdatersManagement $updatersManagement,
        string $name = null
    ) {
        $this->state = $state;
        $this->updatersManagement = $updatersManagement;
        parent::__construct($name);
    }

    /**
     * Configure CLI Command
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);

        $this->addArgument(
            self::INPUT_KEY_UPDATERS,
            InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
            'Updaters list to run'
        );

        $this->addOption(
            self::INPUT_KEY_LIST,
            'l',
            InputOption::VALUE_NONE,
            'Show list of available Updaters'
        );

        $this->addOption(
            self::INPUT_KEY_SKUS,
            's',
            InputOption::VALUE_OPTIONAL,
            'Skus list to update'
        );

        $this->setDescription('Update Mirakl Attributes.');

        parent::configure();
    }

    /**
     * Execute Update Groups Command
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $skus = [];
        $this->processArea();
        if ($input->getOption(self::INPUT_KEY_LIST)) {
            foreach ($this->updatersManagement->getNames() as $updaterName) {
                $output->writeln($updaterName);
            }
        } else {
            $updaters = $this->updatersManagement->getUpdaters($input->getArgument(self::INPUT_KEY_UPDATERS));
            $output->writeln(__('<info>Start %1 Updater(s)</info>', count($updaters)));

            /** @var \Retailplace\AttributesUpdater\Api\UpdaterInterface $updater */
            foreach ($updaters as $updater) {
                $start = microtime(true);
                $output->writeln(__('%1 start', $updater->getName()));
                if ($input->getOption(self::INPUT_KEY_SKUS)) {
                    $skus = preg_split('/[, ]+/', $input->getOption(self::INPUT_KEY_SKUS));
                }
                $updater->run($skus);
                $endTime = round(microtime(true) - $start, 4);
                $output->writeln(__('%1 completed in %2 sec', $updater->getName(), $endTime));
            }

            $output->writeln(__('<info>Finish Updaters</info>'));
        }
    }

    /**
     * Set Area Code for CLI
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function processArea()
    {
        try {
            $this->state->getAreaCode();
        } catch (Exception $e) {
            $this->state->setAreaCode(Area::AREA_FRONTEND);
        }
    }
}
