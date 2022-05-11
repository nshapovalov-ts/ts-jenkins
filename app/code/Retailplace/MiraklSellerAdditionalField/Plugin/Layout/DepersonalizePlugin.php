<?php

namespace Retailplace\MiraklSellerAdditionalField\Plugin\Layout;

use Magento\Framework\View\LayoutInterface;
use Magento\PageCache\Model\DepersonalizeChecker;
use Retailplace\MiraklSellerAdditionalField\Helper\Data;

class DepersonalizePlugin
{
    /**
     * @var DepersonalizeChecker
     */
    private $depersonalizeChecker;

    /**
     * @param DepersonalizeChecker $depersonalizeChecker
     * @param Data $helper
     */
    public function __construct(
        DepersonalizeChecker $depersonalizeChecker,
        Data $helper
    ) {
        $this->depersonalizeChecker = $depersonalizeChecker;
        $this->helper = $helper;
    }

    /**
     * Retrieve sensitive customer data.
     *
     * @param LayoutInterface $subject
     * @return void
     */
    public function beforeGenerateXml(LayoutInterface $subject)
    {
        if ($this->depersonalizeChecker->checkIfDepersonalize($subject)) {
            /** Loading shop ids in advance to make it work after \Magento\Customer\Model\Layout\DepersonalizePlugin::afterGenerateElements */
            $this->helper->getShopIdsForExclusion();
        }
    }
}
