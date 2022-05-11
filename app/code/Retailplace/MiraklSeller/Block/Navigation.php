<?php

namespace Retailplace\MiraklSeller\Block;

/**
 * Navigation block class
 */
class Navigation extends \Magento\LayeredNavigation\Block\Navigation
{
    /**
     * Navigation constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Retailplace\MiraklSeller\Model\Layer\Resolver $layerResolver
     * @param \Magento\Catalog\Model\Layer\FilterList $filterList
     * @param \Magento\Catalog\Model\Layer\AvailabilityFlagInterface $visibilityFlag
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Retailplace\MiraklSeller\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Model\Layer\FilterList $filterList,
        \Magento\Catalog\Model\Layer\AvailabilityFlagInterface $visibilityFlag,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $layerResolver,
            $filterList,
            $visibilityFlag
        );
    }
}
