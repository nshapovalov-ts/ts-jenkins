<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver\Order;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mirakl\MMP\Front\Domain\Order\Evaluation\CreateOrderEvaluation;

class OrderEvaluationResolver extends AbstractOrderResolver implements ResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $currentUserId = $context->getUserId();

        $this->checkLoggedCustomer($context);

        $orderId = $this->getInput($args, 'input.mp_order_id', true);
        $assessments = $this->getInput($args, 'input.assessments', true);
        $comment = $this->getInput($args, 'input.comment');
        $grade = $this->getInput($args, 'input.grade', true);
        $visible = $this->getInput($args, 'input.visible');

        $order = $this->getOrder($orderId, $currentUserId);

        $evaluation = new CreateOrderEvaluation();
        $evaluation->setAssessments($assessments);
        $evaluation->setComment($comment);
        $evaluation->setGrade($grade);
        $evaluation->setVisible($visible);

        try {
            $this->orderHelper->evaluateOrder($order, $evaluation);
        } catch (\Exception $e) {
            throw $this->mapSdkError($e);
        }

        return true;
    }
}
