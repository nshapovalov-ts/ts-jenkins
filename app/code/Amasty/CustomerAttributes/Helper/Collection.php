<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Helper;

use Amasty\Base\Model\Serializer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Collection for configure collection data
 */
class Collection
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var ObjectManagerInterface $objectManager
     */
    private $objectManager;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var array
     */
    private $memberAlias = [];

    public function __construct(
        ResourceConnection $resource,
        ObjectManagerInterface $objectManager,
        Serializer $serializer
    ) {
        $this->objectManager = $objectManager;
        $this->resource = $resource;
        $this->serializer = $serializer;
    }

    /**
     * @param \Magento\Customer\Model\ResourceModel\Attribute\Collection $collection
     * @param string $tableName
     * @param array $filters
     * @param null $sorting
     * @return mixed
     * @throws LocalizedException
     *
     */
    //@codingStandardsIgnoreStart Sophisticated Filter Handler
    public function addFilters($collection, $tableName, $filters = [], $sorting = null)
    {
        $aliasDefault = $this->getProperAlias($collection->getSelect()->getPart('from'), $tableName);
        $select = $collection->getSelect();
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $key = '';
                $where = '';
                if (is_array($filter) && isset($filter['key'])) {
                    $key = $filter['key'];
                    unset($filter['key']);
                    $len = count($filter);
                    $i = 1;
                    foreach ($filter as $val) {
                        $rKey = '';
                        if ($len > $i++) {
                            $rKey = $key;
                        }
                        if (is_array($val)) {
                            $this->checkInterface($val);

                            if (isset($val['table'])) {
                                $alias = $this->getProperAlias(
                                    $collection->getSelect()->getPart('from'),
                                    $val['table']
                                );
                            } else {
                                $alias = $aliasDefault;
                            }
                            $where .= ' ' . $alias . $val['cond']
                                . $val['value']
                                . ' ' . $rKey;
                        } else {
                            $alias = $aliasDefault;
                            $where .= ' ' . $alias . $val . ' ' . $rKey;
                        }
                    }
                } elseif (is_array($filter)) {
                    $this->checkInterface($filter);
                    if (isset($filter['table'])) {
                        $alias = $this->getProperAlias(
                            $collection->getSelect()->getPart('from'),
                            $filter['table']
                        );
                    } else {
                        $alias = $aliasDefault;
                    }
                    $where = ' ' . $alias . $filter['cond'] . $filter['value'];

                } else {
                    $where = $aliasDefault . $filter;
                }
                if (!empty($where)) {
                    $select->where($where);
                }
            }
        }

        if ($sorting) {
            $select->order($aliasDefault . $sorting);
        }

        return $collection;
    }
    // @codingStandardsIgnoreEnd

    /**
     * @param array $from
     * @param string $needTableName
     * @return mixed|string
     */
    public function getProperAlias($from, $needTableName)
    {
        $needTableName = $this->resource->getTableName($needTableName);
        $key = $this->serializer->serialize($from) . $needTableName;

        if (isset($this->memberAlias[$key])) {
            return $this->memberAlias[$key];
        }

        foreach ($from as $key => $table) {
            $fullTableName = explode('.', $table['tableName']);

            if (isset($fullTableName[1])) {
                $tableName = $fullTableName[1];
            } else {
                $tableName = $fullTableName[0];
            }

            if ($needTableName == $tableName) {
                return $key . '.';
            }
        }

        return '';
    }

    /**
     * @param array $value
     * @throws LocalizedException
     */
    public function checkInterface($value)
    {
        if (!isset($value['cond']) || !isset($value['value'])) {
            throw new LocalizedException(__('Amasty error. Bad filter for select'));
        }
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getAttributesHash()
    {
        $collection = $this->objectManager->get(Magento\Customer\Model\Attribute::class)->getCollection();

        $filters = [
            "is_user_defined = 1",
            "frontend_input != 'file' ",
            "frontend_input != 'multiselect' "
        ];

        $collection = $this->addFilters($collection, 'eav_attribute', $filters);

        $filters = [
            [
                "key" => "OR",
               // "type_internal = 'statictext' ",
                [
                    'cond'  => 'backend_type =',
                    'value' => "'varchar'",
                    'table' => 'eav_attribute'
                ]
            ]
        ];

        $collection = $this->addFilters($collection, 'customer_eav_attribute', $filters);

        $attributes = $collection->load();
        $hash = [];

        foreach ($attributes as $attribute) {
            $hash[$attribute->getAttributeCode()]
                = $attribute->getFrontendLabel();
        }

        return $hash;
    }
}
