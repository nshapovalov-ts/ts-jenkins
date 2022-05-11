<?php
namespace Mirakl\Core\Model\ResourceModel;

use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Mirakl\MMP\FrontOperator\Domain\Collection\Shop\ShopCollection;
use Mirakl\Process\Model\Process;

class Shop extends AbstractDb
{
    use ArraySerializableFieldsTrait;

    /**
     * @var Shop\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var AttributeRepository
     */
    protected $attributeRepository;

    /**
     * @var array
     */
    protected $_serializableFields = [
        'additional_info' => [null, []]
    ];

    /**
     * @param   Shop\CollectionFactory      $collectionFactory
     * @param   AttributeRepository         $attributeRepository
     * @param   Context                     $context
     * @param   mixed                       $connectionName
     */
    public function __construct(
        Shop\CollectionFactory $collectionFactory,
        AttributeRepository $attributeRepository,
        Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->collectionFactory = $collectionFactory;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        // Table Name and Primary Key column
        $this->_init('mirakl_shop', 'id');
    }

    /**
     * Returns EAV option ids of shops
     *
     * @return  array
     */
    public function getEavOptionIds()
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), ['id', 'eav_option_id']);

        return $this->getConnection()->fetchPairs($select);
    }

    /**
     * @param   ShopCollection  $shops
     * @param   Process         $process
     * @param   int             $chunkSize
     * @return  int
     * @throws  \Exception
     */
    public function synchronize(ShopCollection $shops, Process $process, $chunkSize = 100)
    {
        if (!$shops->count()) {
            throw new \Exception(__('Shops to synchronize cannot be empty.'));
        }

        // Load existing mirakl_shop_ids EAV attribute
        $attribute = $this->attributeRepository->get('mirakl_shop_ids');
        if (!$attribute) {
            throw new \Exception(__('mirakl_shop_ids attribute is not created.'));
        }

        $adapter = $this->getConnection();

        // Load existing EAV option ids associated to shop ids
        $customShops = $this->getEavOptionIds();

        $eavShopOptions = [];
        foreach ($attribute->getOptions() as $option) {
            /** @var \Magento\Eav\Api\Data\AttributeOptionInterface $option */
            if ($option->getValue()) {
                $eavShopOptions[$option->getValue()] = $option;
            }
        }

        $fields = array_keys($adapter->describeTable($this->getMainTable()));
        $insert = [];

        foreach ($shops->toArray() as $shop) {
            // Check if EAV option exists
            if (isset($customShops[$shop['id']]) &&
                isset($eavShopOptions[$customShops[$shop['id']]]))
            {
                $optionId = $customShops[$shop['id']];
                // Update EAV option if label has changed
                if ($eavShopOptions[$optionId]->getLabel() != $shop['name']) {
                    $this->getConnection()->update(
                        $this->getTable('eav_attribute_option_value'),
                        ['value' => $shop['name']],
                        ['option_id = ?' => $optionId, 'store_id = ?' => 0]
                    );
                }
            } else {
                // Create EAV option
                $optionTable = $this->getTable('eav_attribute_option');
                $optionValueTable = $this->getTable('eav_attribute_option_value');

                $data = ['attribute_id' => $attribute->getId()];
                $this->getConnection()->insert($optionTable, $data);
                $optionId = $this->getConnection()->lastInsertId($optionTable);

                $data = ['option_id' => $optionId, 'store_id' => 0, 'value' => $shop['name']];
                $this->getConnection()->insert($optionValueTable, $data);
            }

            $data = [];
            foreach ($fields as $field) {
                $data[$field] = isset($shop[$field]) ? $shop[$field] : null;
            }
            $data['free_shipping'] = $shop['shipping_info']['free_shipping'];
            $data['eav_option_id'] = $optionId;
            $data['additional_info'] = serialize($shop);
            $insert[] = $data;
            $process->output(__('Saving shop %1', $data['id']));
        }

        $affected = 0;
        foreach (array_chunk($insert, $chunkSize) as $shopsData) {
            $affected += $adapter->insertOnDuplicate($this->getMainTable(), $shopsData, $fields);
        }

        return $affected;
    }
}