<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\Performance\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;
class FixSmallImages extends Command
{

    const NAME_ARGUMENT = "name";
    const NAME_OPTION = "option";

    /**
     * @var State
     */
    private $appState;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    protected $logger;

    protected $helper;
    /**
     * @param   State           $state
     * @param   ProcessFactory  $processFactory
     * @param   string|null     $name
     */
    public function __construct(
        State $state,
        \Psr\Log\LoggerInterface $logger,
        \Retailplace\Performance\Helper\Data $helper,
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
        
        $this->helper->fixSmallSizeImages($output);
        $name = $input->getArgument(self::NAME_ARGUMENT);
        $option = $input->getOption(self::NAME_OPTION);
       
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("retailplace_performance:fix-small-size-images");
        $this->setDescription("Imported Image Resize By Cron");
        $this->setDefinition([
            new InputArgument(self::NAME_ARGUMENT, InputArgument::OPTIONAL, "Name"),
            new InputOption(self::NAME_OPTION, "-a", InputOption::VALUE_NONE, "Option functionality")
        ]);
        parent::configure();
    }
}

