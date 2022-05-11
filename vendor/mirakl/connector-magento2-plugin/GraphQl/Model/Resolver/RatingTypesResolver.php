<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mirakl\Api\Helper\Evaluation as EvaluationHelper;

class RatingTypesResolver extends AbstractResolver implements ResolverInterface
{
    /**
     * @var EvaluationHelper
     */
    protected $evaluationHelper;

    /**
     * @param  EvaluationHelper $evaluationHelper
     */
    public function __construct(EvaluationHelper $evaluationHelper)
    {
        $this->evaluationHelper = $evaluationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->checkLoggedCustomer($context);

        try {
            $response = $this->evaluationHelper->getAssessments();
        } catch (\Exception $e) {
            throw $this->mapSdkError($e);
        }

        return [
            'model' => $response,
            'assessments' => $response->toArray(),
        ];
    }
}
