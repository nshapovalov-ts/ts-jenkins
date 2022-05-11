<?php
namespace Mirakl\Core\Model\ResourceModel\Offer;

use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Mirakl\MMP\Front\Domain\Collection\Offer\State\OfferStateCollection;
use Psr\Log\LoggerInterface;

class State extends AbstractDb
{
    /**
     * @var State\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var AttributeRepository
     */
    protected $attributeRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param   State\CollectionFactory $collectionFactory
     * @param   AttributeRepository     $attributeRepository
     * @param   Context                 $context
     * @param   LoggerInterface         $logger
     * @param   string                  $connectionName
     */
    public function __construct(
        State\CollectionFactory $collectionFactory,
        AttributeRepository $attributeRepository,
        Context $context,
        LoggerInterface $logger,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->collectionFactory = $collectionFactory;
        $this->attributeRepository = $attributeRepository;
        $this->logger = $logger;
    }

    /**
     * Initialize main table and table id field
     *
     * @return  void
     */
    protected function _construct()
    {
        $this->_init('mirakl_offer_state', 'id');
    }

    /**
     * Returns EAV option ids of offer states
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
     * @param   OfferStateCollection    $states
     * @return  int
     * @throws  \Exception
     */
    public function synchronize(OfferStateCollection $states)
    {
        if (!$states->count()) {
            throw new \Exception(__('States to synchronize cannot be empty.'));
        }

        // Load existing offer state EAV attribute
        $attribute = $this->attributeRepository->get('mirakl_offer_state_ids');
        if (!$attribute) {
            throw new \Exception(__('mirakl_offer_state_ids attribute is not created.'));
        }

        $adapter = $this->getConnection();

        // Load existing EAV option ids associated to offer state ids
        $customOfferStates = $this->getEavOptionIds();

        $eavOfferStateOptions = [];
        foreach ($attribute->getOptions() as $option) {
            /** @var \Magento\Eav\Api\Data\AttributeOptionInterface $option */
            if ($option->getValue()) {
                $eavOfferStateOptions[$option->getValue()] = $option;
            }
        }

        $fields = array_keys($adapter->describeTable($this->getMainTable()));
        $insert = [];

        /** @var \Mirakl\MMP\Common\Domain\Offer\State\OfferState $state */
        foreach ($states as $sortOrder => $state) {
            // Check if EAV option exists
            if (isset($customOfferStates[$state->getCode()]) &&
                isset($eavOfferStateOptions[$customOfferStates[$state->getCode()]]))
            {
                $optionId = $customOfferStates[$state->getCode()];
                // Update EAV option if label has changed
                if ($eavOfferStateOptions[$optionId]->getLabel() != $state->getLabel()) {
                    $this->getConnection()->update(
                        $this->getTable('eav_attribute_option_value'),
                        ['value' => $state->getLabel()],
                        ['option_id = ?' => $optionId, 'store_id = ?' => 0]
                    );
                }
            } else {
                // Create EAV option
                $optionTable = $this->getTable('eav_attribute_option');
                $data = ['attribute_id' => $attribute->getId(), 'sort_order' => $sortOrder];
                $this->getConnection()->insert($optionTable, $data);
                $optionId = $this->getConnection()->lastInsertId($optionTable);

                $data = ['option_id' => $optionId, 'store_id' => 0, 'value' => $state->getLabel()];
                $this->getConnection()->insert($this->getTable('eav_attribute_option_value'), $data);
            }

            $insert[] = [
                'id' => $state->getCode(),
                'name' => $state->getLabel(),
                'eav_option_id' => $optionId,
                'sort_order' => $sortOrder,
            ];
        }

        return $adapter->insertOnDuplicate($this->getMainTable(), $insert, $fields);
    }
}
