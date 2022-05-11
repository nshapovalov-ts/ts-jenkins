<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Controller\Adminhtml\IndustryExclusions;

use Magento\Framework\Exception\LocalizedException;

class Save extends \Magento\Backend\App\Action
{

    protected $dataPersistor;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
    ) {
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $id = $this->getRequest()->getParam('industryexclusions_id');

            $model = $this->_objectManager->create(\Retailplace\MiraklSellerAdditionalField\Model\IndustryExclusions::class)->load($id);
            if (!$model->getId() && $id) {
                $this->messageManager->addErrorMessage(__('This Industryexclusions no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
            if (!empty($data['visible_for'])){
                $data['visible_for']  = implode(",", $data['visible_for']);
            }
            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccessMessage(__('You saved the Industryexclusions.'));
                $this->dataPersistor->clear('retailplace_miraklselleradditionalfield_industryexclusions');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['industryexclusions_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the Industryexclusions.'));
            }

            $this->dataPersistor->set('retailplace_miraklselleradditionalfield_industryexclusions', $data);
            return $resultRedirect->setPath('*/*/edit', ['industryexclusions_id' => $this->getRequest()->getParam('industryexclusions_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}

