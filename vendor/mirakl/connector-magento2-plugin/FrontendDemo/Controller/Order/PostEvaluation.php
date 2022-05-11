<?php
namespace Mirakl\FrontendDemo\Controller\Order;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Mirakl\MMP\Front\Domain\Collection\Order\Evaluation\CreateOrderEvaluationAssessmentCollection;
use Mirakl\MMP\Front\Domain\Order\Evaluation\CreateOrderEvaluation;
use Mirakl\MMP\Front\Domain\Order\Evaluation\CreateOrderEvaluationAssessment;

class PostEvaluation extends AbstractOrder
{
    /**
     * Submit new message action
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

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->registry->registry('current_order');
        /** @var \Mirakl\MMP\FrontOperator\Domain\Order $miraklOrder */
        $miraklOrder = $this->registry->registry('mirakl_order');

        $data = $this->session->getFormData(true);
        if (!$data) {
            $data = $this->getRequest()->getPostValue();
        }

        if (!empty($data) && !empty($data['evaluation'])) {
            $evalData = $data['evaluation'];
            try {
                $evaluation = new CreateOrderEvaluation();

                $assessments = new CreateOrderEvaluationAssessmentCollection();
                foreach ($evalData['assessments'] as $code => $response) {
                    $assessments->add(new CreateOrderEvaluationAssessment($code, $response));
                }

                $evaluation->setAssessments($assessments);
                $evaluation->setGrade($evalData['grade']);

                if (!empty($evalData['comment'])) {
                    $evaluation->setComment(trim($evalData['comment']));
                }

                $this->orderApi->evaluateOrder($miraklOrder, $evaluation);

                $this->messageManager->addSuccessMessage(
                    __('Your evaluation has been sent successfully.')
                );
            } catch (\Exception $e) {
                $this->session->setFormData($data);
                $this->logger->warning($e->getMessage());
                $this->messageManager->addErrorMessage(
                    __('An error occurred while evaluating your order.')
                );
            }
        }

        $resultRedirect->setUrl($this->url->getUrl('marketplace/order/evaluation', [
            'order_id' => $order->getId(),
            'remote_id' => $miraklOrder->getId(),
        ]));

        return $resultRedirect;
    }
}
