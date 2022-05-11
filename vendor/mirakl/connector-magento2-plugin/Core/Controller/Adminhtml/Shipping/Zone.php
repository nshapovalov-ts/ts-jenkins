<?php
namespace Mirakl\Core\Controller\Adminhtml\Shipping;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;

abstract class Zone extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mirakl_Core::shipping_zones';

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @param   Context     $context
     * @param   Registry    $coreRegistry
     */
    public function __construct(Context $context, Registry $coreRegistry)
    {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     * Init action
     *
     * @return  $this
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('Mirakl_Core::shipping_zones')
            ->_addBreadcrumb(__('Mirakl'), __('Mirakl'));

        return $this;
    }
}
