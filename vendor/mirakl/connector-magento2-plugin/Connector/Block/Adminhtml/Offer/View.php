<?php
namespace Mirakl\Connector\Block\Adminhtml\Offer;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;
use Mirakl\Connector\Model\Offer;

class View extends Container
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param   Context     $context
     * @param   Registry    $coreRegistry
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_mode = false;

        parent::_construct();

        $this->removeButton('save');
        $this->removeButton('reset');
        $this->removeButton('delete');
    }

    /**
     * @return  Offer
     */
    public function getOffer()
    {
        return $this->coreRegistry->registry('mirakl_offer');
    }
}