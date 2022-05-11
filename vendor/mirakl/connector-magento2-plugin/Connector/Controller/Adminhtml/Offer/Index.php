<?php
namespace Mirakl\Connector\Controller\Adminhtml\Offer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Mirakl\Connector\Helper\Config;
use Mirakl\Core\Helper\Data as CoreHelper;

class Index extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mirakl_Connector::offers';

    /**
     * @var Config
     */
    protected $connectorConfig;

    /**
     * @var CoreHelper
     */
    protected $coreHelper;

    /**
     * @param   Context     $context
     * @param   Config      $connectorConfig
     * @param   CoreHelper  $coreHelper
     */
    public function __construct(
        Context $context,
        Config $connectorConfig,
        CoreHelper $coreHelper
    ) {
        parent::__construct($context);
        $this->connectorConfig = $connectorConfig;
        $this->coreHelper = $coreHelper;
    }

    /**
     * Init action
     *
     * @return  $this
     */
    protected function _initAction()
    {
        $this->showLastUpdateDate('offers');
        $this->_view->loadLayout();

        $this->_setActiveMenu('Mirakl_Connector::offer')
            ->_addBreadcrumb(__('Mirakl'), __('Mirakl'));

        return $this;
    }

    /**
     * @return  void
     */
    public function execute()
    {
        $this->_initAction()->_addBreadcrumb(__('Offer List'), __('Offer List'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Offer List'));
        $this->_view->renderLayout();
    }

    /**
     * Adds a notice that displays last synchronization date of specified entity
     *
     * @param   string  $entity
     */
    protected function showLastUpdateDate($entity)
    {
        $lastUpdateDate = $this->connectorConfig->getSyncDate($entity);
        if ($lastUpdateDate) {
            $this->messageManager->addNoticeMessage(
                __('Last synchronization: %1', $this->coreHelper->formatDateTime($lastUpdateDate))
            );
        }
    }
}
