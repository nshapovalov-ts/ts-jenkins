<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Console\Command;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Retailplace\ChannelPricing\Model\CartRulesUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CartPriceRulesUpdate
 */
class CartPriceRulesUpdate extends Command
{
    /** @var string */
    public const COMMAND_NAME = 'retailplace:cart-rules:update-groups';
    public const ARGUMENT_GROUP_NAME = 'group-name';

    /** @var \Magento\Framework\App\State */
    private $state;

    /** @var \Retailplace\ChannelPricing\Model\CartRulesUpdater */
    private $cartRulesUpdater;

    /**
     * CartPriceRulesUpdate constructor.
     *
     * @param \Magento\Framework\App\State $state
     * @param \Retailplace\ChannelPricing\Model\CartRulesUpdater $cartRulesUpdater
     * @param string|null $name
     */
    public function __construct(
        State $state,
        CartRulesUpdater $cartRulesUpdater,
        string $name = null
    ) {
        $this->state = $state;
        $this->cartRulesUpdater = $cartRulesUpdater;
        parent::__construct($name);
    }

    /**
     * Configure CLI Command
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Add Customer Group to all Cart Price Rules.');

        $this->addArgument(self::ARGUMENT_GROUP_NAME, InputArgument::OPTIONAL);
        $this->addUsage('Retailers');

        $this->setHelp(
            <<<HELP
Update all Cart Price Rules with Customer Group.

Group Code can be passed to the arguments to add only one Group.

By default all groups will be added.
HELP
        );

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
        $this->processArea();

        $output->writeln(__('<info>Updating Cart Price Rules...</info>'));
        $groupName = $input->getArgument(self::ARGUMENT_GROUP_NAME);
        if ($groupName) {
            $groupName = array_unique(explode(',', $groupName));
            $count = $this->cartRulesUpdater->updateCartRulesWithGroup($groupName);
        } else {
            $count = $this->cartRulesUpdater->updateCartRulesWithGroup();
        }

        $output->writeln(__('<info>%1 Cart Price Rules were updated.</info>', $count));
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
