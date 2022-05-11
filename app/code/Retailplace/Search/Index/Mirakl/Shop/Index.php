<?php

namespace Retailplace\Search\Index\Mirakl\Shop;

use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Mirakl\Core\Model\Shop;
use Mirasvit\Search\Model\Index\AbstractIndex;
use Mirasvit\Search\Model\Index\Context;

/**
 * @method array getIgnoredPages()
 */
class Index extends AbstractIndex
{
    /**
     * @var ShopCollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param ShopCollectionFactory $collectionFactory
     * @param Context $context
     * @param array $dataMappers
     */
    public function __construct(
        ShopCollectionFactory $collectionFactory,
        Context $context,
        $dataMappers
    ) {
        $this->collectionFactory = $collectionFactory;

        parent::__construct($context, $dataMappers);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Mirakl / Shop';
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'mirakl_shop';
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return [
            'name' => __('Name'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryKey()
    {
        return 'id';
    }

    /**
     * {@inheritdoc}
     */
    public function buildSearchCollection()
    {
        $collection = $this->collectionFactory->create();

        $this->context->getSearcher()->joinMatches($collection, 'main_table.id');

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchableEntities($storeId, $entityIds = null, $lastEntityId = null, $limit = 100)
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('state', Shop::STATE_OPEN);

        if ($entityIds) {
            $collection->addFieldToFilter('id', $entityIds);
        }

        $collection
            ->addFieldToFilter('id', ['gt' => $lastEntityId])
            ->setPageSize($limit)
            ->setOrder('id', 'asc');

        return $collection;
    }
}
