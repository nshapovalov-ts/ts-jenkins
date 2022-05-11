<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * Retailplace_Performance
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Performance\Console\Command;

use Magento\Framework\App\Area;
use Psr\Log\LoggerInterface;
use Retailplace\Performance\Helper\Data;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;

/**
 * Class Imageresize
 */
class Imageresize extends Command
{
    /** @var string */
    const NAME_ARGUMENT = "name";
    const NAME_OPTION = "option";
    public const LIMIT = 'limit';

    /**
     * @var State
     */
    private $appState;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Retailplace\Performance\Helper\Data
     */
    protected $helper;

    /**
     * @param State $state
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Retailplace\Performance\Helper\Data $helper
     * @param string|null $name
     */
    public function __construct(
        State $state,
        LoggerInterface $logger,
        Data $helper,
        $name = null
    ) {
        parent::__construct($name);
        $this->helper = $helper;
        $this->logger = $logger;
        $this->appState = $state;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        $limit = $input->getArgument(self::LIMIT);
        if ($limit) {
            $this->helper->resizeProductImages($output, $limit);
        } else {
            $this->helper->resizeProductImages($output);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("retailplace_performance:imageresize");
        $this->setDescription("Imported Image Resize By Cron");
        $this->setDefinition([
            new InputArgument(self::NAME_ARGUMENT, InputArgument::OPTIONAL, "Name"),
            new InputArgument(self::LIMIT, InputArgument::OPTIONAL, "Limit"),
            new InputOption(self::NAME_OPTION, "-a", InputOption::VALUE_NONE, "Option functionality")
        ]);
        parent::configure();
    }
}
