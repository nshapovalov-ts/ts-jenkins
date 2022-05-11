<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class AdditionalFieldValueResolver extends AbstractResolver implements ResolverInterface
{
    /**
     * {@inheritDoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        return is_array($value['value'])
            ? implode(',', $value['value'])
            : (string) $value['value'];
    }
}
