<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mirakl\Connector\Helper\Shop as ShopHelper;
use Mirakl\GraphQl\Model\Mapper\Shop as ShopMapper;

class ShopResolver extends AbstractResolver implements ResolverInterface
{
    /**
     * @var ShopHelper
     */
    private $shopHelper;

    /**
     * @var ShopMapper
     */
    protected $shopMapper;

    /**
     * @param ShopHelper $shopHelper
     * @param ShopMapper $shopMapper
     */
    public function __construct(ShopHelper $shopHelper, ShopMapper $shopMapper)
    {
        $this->shopHelper = $shopHelper;
        $this->shopMapper = $shopMapper;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $shopId = $this->getInput($args, 'shop_id', true);
        $shop = $this->shopHelper->getShopById($shopId);

        return $this->shopMapper->toGraphQlArray($shop);
    }
}
