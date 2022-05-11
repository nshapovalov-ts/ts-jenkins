<?php
namespace Mirakl\FrontendDemo\Controller\Order;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Download extends AbstractOrder
{
    /**
     * Download a document
     *
     * @return  ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        $result = $this->initOrders();
        if ($result !== true) {
            return $result;
        }

        /** @var \Mirakl\MMP\FrontOperator\Domain\Order $miraklOrder */
        $miraklOrder = $this->registry->registry('mirakl_order');

        try {
            $docId = $this->_request->getParam('doc_id');
            $docs = $this->orderApi->getOrderDocuments($miraklOrder);
            foreach ($docs as $doc) {
                /** @var \Mirakl\MMP\Common\Domain\Order\Document\OrderDocument $doc */
                if ($doc->getId() == $docId) {
                    $file = $this->orderApi->downloadDocument($docId);
                    $file->setFileName($doc->getFileName());
                    $file->download();
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred while downloading order document.')
            );
        }

        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}
