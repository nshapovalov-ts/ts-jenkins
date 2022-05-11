<?php
/**
 * Retailplace_MiraklSellerAdditionalField
 *
 * @copyright   Copyright (c) TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */
namespace Retailplace\MiraklSellerAdditionalField\Console\Command;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\State;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Store\Model\Store;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Retailplace\MiraklSellerAdditionalField\Observer\SetNewsStartDate;
use Zend_Db_Expr;

/**
 * Class UpdateNewsFields
 */
class UpdateNewsFields extends Command
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $productAttributeRepository;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var State
     */
    protected $appState;

    /**
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param CollectionFactory $productCollectionFactory
     * @param DateTime $dateTime
     * @param State $appState
     * @param string|null $name
     */
    public function __construct(
        ProductAttributeRepositoryInterface $productAttributeRepository,
        CollectionFactory $productCollectionFactory,
        DateTime $dateTime,
        State $appState,
        string $name = null
    ) {
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->dateTime = $dateTime;
        $this->appState = $appState;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('catalog:product:update-news-fields');
        $this->setDescription('Updates news* product attribute.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId(Store::DEFAULT_STORE_ID);
        $connection = $collection->getConnection();
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $interval = new \DateInterval('P' . \Retailplace\MiraklSellerAdditionalField\Observer\SetNewsStartDate::NEW_PRODUCT_DAYS . 'D');
        $dateTime->sub($interval);

        $fromDate = $this->dateTime->formatDate($dateTime->getTimestamp());
        $collection->addAttributeToFilter('created_at', ['date' => true, 'from' => $fromDate]);

        $newsFromAttribute = $this->productAttributeRepository->get('news_from_date');
        $newsToAttribute = $this->productAttributeRepository->get('news_to_date');

        $select = $collection->getSelect()
            ->reset('columns')
            ->columns([
                'attribute_id' => new Zend_Db_Expr($newsFromAttribute->getAttributeId()),
                'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
                'entity_id'    => 'entity_id',
                'value'        => 'created_at'
            ]);

        $sql = $connection->insertFromSelect(
            $select,
            $newsFromAttribute->getBackendTable(),
            ['attribute_id', 'store_id', 'entity_id', 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $connection->query($sql);

        $select = $collection->getSelect()
            ->reset('columns')
            ->columns([
                'attribute_id' => new Zend_Db_Expr($newsToAttribute->getAttributeId()),
                'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
                'entity_id'    => 'entity_id',
                'value'        => new Zend_Db_Expr("DATE_ADD(created_at, INTERVAL " . SetNewsStartDate::NEW_PRODUCT_DAYS . " DAY)")
            ]);

        $sql = $connection->insertFromSelect(
            $select,
            $newsToAttribute->getBackendTable(),
            ['attribute_id', 'store_id', 'entity_id', 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $connection->query($sql);
    }
}
