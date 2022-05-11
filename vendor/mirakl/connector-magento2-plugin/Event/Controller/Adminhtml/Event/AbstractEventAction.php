<?php
namespace Mirakl\Event\Controller\Adminhtml\Event;

use Mirakl\Event\Helper\Data as EventHelper;
use Mirakl\Event\Model\Event;
use Mirakl\Event\Model\ResourceModel\Event as EventResource;

abstract class AbstractEventAction extends \Magento\Backend\App\Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mirakl_Event::event';

    /**
     * @return  Event
     */
    protected function getEvent()
    {
        $id = (int) $this->getRequest()->getParam('id');

        /** @var Event $event */
        $event = $this->getEventModel();
        $this->getEventResource()->load($event, $id);

        return $event;
    }

    /**
     * @return  EventHelper
     */
    protected function getEventHelper()
    {
        return $this->_objectManager->get(EventHelper::class);
    }

    /**
     * @return  Event
     */
    protected function getEventModel()
    {
        return $this->_objectManager->create(Event::class);
    }

    /**
     * @return  EventResource
     */
    protected function getEventResource()
    {
        return $this->_objectManager->create(EventResource::class);
    }

    /**
     * @param   string  $errorMessage
     * @return  \Magento\Framework\Controller\Result\Redirect
     */
    protected function redirectError($errorMessage)
    {
        $this->messageManager->addErrorMessage($errorMessage);
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('*/*/');
    }
}
