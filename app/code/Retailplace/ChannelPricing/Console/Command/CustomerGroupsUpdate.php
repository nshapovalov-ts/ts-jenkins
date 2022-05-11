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
use Retailplace\ChannelPricing\Model\CustomerGroupMapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CustomerGroupsUpdate
 */
class CustomerGroupsUpdate extends Command
{
    /** @var string */
    public const COMMAND_NAME = 'retailplace:customer:update-groups';
    public const ARGUMENT_GROUP_NAME = 'group-name';

    /** @var \Magento\Framework\App\State */
    private $state;

    /** @var \Retailplace\ChannelPricing\Model\CustomerGroupMapper */
    private $customerGroupMapper;

    /**
     * CustomerGroupsUpdate constructor.
     *
     * @param \Magento\Framework\App\State $state
     * @param \Retailplace\ChannelPricing\Model\CustomerGroupMapper $customerGroupMapper
     * @param string|null $name
     */
    public function __construct(
        State $state,
        CustomerGroupMapper $customerGroupMapper,
        string $name = null
    ) {
        $this->state = $state;
        $this->customerGroupMapper = $customerGroupMapper;
        parent::__construct($name);
    }

    /**
     * Configure CLI Command
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Update Customer Group Related to Attributes.');

        $this->addArgument(self::ARGUMENT_GROUP_NAME, InputArgument::OPTIONAL);
        $this->addUsage('Retailers');

        $this->setHelp(
            <<<HELP
Update Customers with Related Customer Group depends on Customer Attribute (eg. "business_type").

Group Code can be passed to the arguments to update only one Group.

By default only Retailers group will be applied.
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

        $output->writeln(__('<info>Updating Customer Groups...</info>'));
        $groupName = $input->getArgument(self::ARGUMENT_GROUP_NAME);
        if ($groupName) {
            $groupName = array_unique(explode(',', $groupName));
            $count = $this->customerGroupMapper->applyGroupToCustomers($groupName);
        } else {
            $count = $this->customerGroupMapper->applyGroupToCustomers();
        }

        $output->writeln(__('<info>%1 Customers were updated.</info>', $count));
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
