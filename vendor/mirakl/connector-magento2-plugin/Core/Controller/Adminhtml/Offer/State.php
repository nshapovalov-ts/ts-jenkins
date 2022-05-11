<?php
namespace Mirakl\Core\Controller\Adminhtml\Offer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;

abstract class State extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mirakl_Core::offer_states';

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
        $this->_setActiveMenu('Mirakl_Core::offer_states')
            ->_addBreadcrumb(__('Mirakl'), __('Mirakl'));

        return $this;
    }
}
