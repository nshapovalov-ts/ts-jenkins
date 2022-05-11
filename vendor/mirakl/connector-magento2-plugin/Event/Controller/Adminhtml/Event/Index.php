<?php
namespace Mirakl\Event\Controller\Adminhtml\Event;

use Magento\Framework\Controller\ResultFactory;

class Index extends AbstractEventAction
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Mirakl_Event::event');
        $page->getConfig()->getTitle()->prepend(__('Event List'));

        return $page;
    }
}
