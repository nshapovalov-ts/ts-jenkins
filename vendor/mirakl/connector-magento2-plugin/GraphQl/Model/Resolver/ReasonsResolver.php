<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mirakl\Api\Helper\Reason as ReasonHelper;

class ReasonsResolver extends AbstractResolver implements ResolverInterface
{
    /**
     * @var ReasonHelper
     */
    protected $reasonHelper;

    /**
     * @param  ReasonHelper $reasonHelper
     */
    public function __construct(ReasonHelper $reasonHelper)
    {
        $this->reasonHelper = $reasonHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->checkLoggedCustomer($context);

        $reasonType = $this->getInput($args, 'reason_type', true);
        try {
            $response = $this->reasonHelper->getTypeReasons($reasonType);
        } catch (\Exception $e) {
            throw $this->mapSdkError($e);
        }

        return [
            'model' => $response,
            'reasons' => $response->toArray(),
        ];
    }
}
