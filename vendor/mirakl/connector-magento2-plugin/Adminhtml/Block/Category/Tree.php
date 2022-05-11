<?php
namespace Mirakl\Adminhtml\Block\Category;

class Tree extends \Magento\Catalog\Block\Adminhtml\Category\Tree
{
    /**
     * @var array
     */
    protected $miraklSyncIds = [];

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();

        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->_categoryFactory->create()->getCollection();
        $collection->addAttributeToFilter('mirakl_sync', 1);

        $this->_eventManager->dispatch('mirakl_adminhtml_category_tree', [
            'collection' => $collection,
        ]);

        $this->miraklSyncIds = $collection->getAllIds();
    }

    /**
     * Need to specify Magento_Catalog module because we do not override the template file
     *
     * {@inheritdoc}
     */
    public function getTemplateFile($template = null)
    {
        $params = ['module' => 'Magento_Catalog'];
        if ($area = $this->getArea()) {
            $params['area'] = $area;
        }

        return $this->resolver->getTemplateFileName($this->_template, $params);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getNodeJson($node, $level = 0)
    {
        $item = parent::_getNodeJson($node, $level);
        if (in_array($item['id'], $this->miraklSyncIds)) {
            $item['cls'] .= ' mirakl';
        }

        return $item;
    }
}