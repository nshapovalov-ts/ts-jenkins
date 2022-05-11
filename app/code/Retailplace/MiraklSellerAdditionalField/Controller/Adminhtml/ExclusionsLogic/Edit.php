<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Controller\Adminhtml\ExclusionsLogic;

class Edit extends \Retailplace\MiraklSellerAdditionalField\Controller\Adminhtml\ExclusionsLogic
{

    protected $resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * Edit action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('exclusionslogic_id');
        $model = $this->_objectManager->create(\Retailplace\MiraklSellerAdditionalField\Model\ExclusionsLogic::class);
        
        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This Exclusionslogic no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->_coreRegistry->register('retailplace_miraklselleradditionalfield_exclusionslogic', $model);
        
        // 3. Build edit form
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Exclusionslogic') : __('New Exclusionslogic'),
            $id ? __('Edit Exclusionslogic') : __('New Exclusionslogic')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Exclusionslogics'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? __('Edit Exclusionslogic %1', $model->getId()) : __('New Exclusionslogic'));
        return $resultPage;
    }
}

