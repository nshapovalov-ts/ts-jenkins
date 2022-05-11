<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver\Offer;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mirakl\GraphQl\Model\Resolver\AbstractResolver;

class FormattedPriceResolver extends AbstractResolver implements ResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"%1" value should be specified', 'model'));
        }

        if (!isset($value['product_model'])) {
            throw new LocalizedException(__('"%1" value should be specified', 'product_model'));
        }

        return [
            'model'         => $value['model'],
            'product_model' => $value['product_model'],
        ];
    }
}
