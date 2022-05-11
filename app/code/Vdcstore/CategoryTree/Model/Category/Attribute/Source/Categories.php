<?php

namespace Vdcstore\CategoryTree\Model\Category\Attribute\Source;

use \Magento\Catalog\Api\CategoryManagementInterface;

class Categories extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * Catalog config
     *
     * @var \Magento\Catalog\Model\Config
     */
    protected $_catalogConfig;

    /**
     * Construct
     *
     * @param \Magento\Catalog\Model\Config $catalogConfig
     */
    public function __construct(\Magento\Catalog\Model\Config $catalogConfig, CategoryManagementInterface $categoryTree)
    {
        $this->_catalogConfig = $catalogConfig;
        $this->categoryTree = $categoryTree;
    }

    /**
     * Retrieve Catalog Config Singleton
     *
     * @return \Magento\Catalog\Model\Config
     */
    protected function _getCatalogConfig()
    {
        return $this->_catalogConfig;
    }

    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        $tree = $this->categoryTree->getTree(1, 1);
        if($this->_options == null) {
            foreach ($tree->getChildrenData() as $item) {
                $this->_options[] = [
                    'label' => __($item->getName()),
                    'value' => $item->getId()
                ];
            }
        }
        return $this->_options;
    }
}
