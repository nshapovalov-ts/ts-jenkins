<?php
namespace Mirakl\Event\Block\Adminhtml\Event;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;
use Mirakl\Event\Model\Event;

class View extends Container
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param   Context     $context
     * @param   Registry    $registry
     * @param   array       $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_mode = 'view';
        $this->_controller = 'adminhtml_event';
        $this->_blockGroup = 'Mirakl_Event';

        parent::_construct();

        $this->removeButton('save');
        $this->removeButton('reset');

        $event = $this->getEvent();
        $this->buttonList->update('delete', 'class', 'primary');

        if (!$event) {
             $this->removeButton('delete');
        }
    }

    /**
     * @return  string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', ['id' => $this->getEvent()->getId()]);
    }

    /**
     * @return  Event
     */
    public function getEvent()
    {
        return $this->coreRegistry->registry('event');
    }
}
