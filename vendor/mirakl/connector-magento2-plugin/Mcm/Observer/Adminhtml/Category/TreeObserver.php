<?php
namespace Mirakl\Mcm\Observer\Adminhtml\Category;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Mci\Helper\Hierarchy as HierarchyHelper;
use Mirakl\Mcm\Helper\Config;

class TreeObserver implements ObserverInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var HierarchyHelper
     */
    protected $hierarchyHelper;

    /**
     * @param   Config          $config
     * @param   HierarchyHelper $hierarchyHelper
     */
    public function __construct(Config $config, HierarchyHelper $hierarchyHelper)
    {
        $this->config = $config;
        $this->hierarchyHelper = $hierarchyHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($this->config->isMcmEnabled()) {
            /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
            $collection = $observer->getEvent()->getCollection();
            $collection->getSelect()
                ->reset()
                ->from(['e' => $collection->getTable('catalog_category_entity')]);

            $categoryIds = $this->hierarchyHelper->getTree()->getCollection()->getAllIds();
            $collection->addIdFilter($categoryIds);
        }
    }
}
