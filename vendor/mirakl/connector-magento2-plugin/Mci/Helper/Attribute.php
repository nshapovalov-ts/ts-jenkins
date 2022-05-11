<?php
namespace Mirakl\Mci\Helper;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Store\Api\Data\StoreInterface;
use Mirakl\Api\Helper\Attribute as Api;
use Mirakl\Connector\Common\ExportInterface;
use Mirakl\Connector\Common\ExportTrait;
use Mirakl\Mci\Helper\Config as MciConfig;
use Mirakl\Mci\Helper\Data as MciHelper;
use Mirakl\Mci\Model\Product\Attribute\AttributeFormatter;
use Mirakl\Mci\Model\Product\Attribute\AttributeUtil;
use Mirakl\Mci\Model\Product\Attribute\CategoryAttributesBuilder;
use Mirakl\Mci\Model\Product\Attribute\ProductAttributesFinder;
use Mirakl\Process\Model\Process;

class Attribute extends AbstractHelper implements ExportInterface
{
    use ExportTrait;

    const EXPORT_SOURCE = 'PM01';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var MciConfig
     */
    protected $mciConfig;

    /**
     * @var CategoryAttributesBuilder
     */
    protected $categoryAttributesBuilder;

    /**
     * @var ProductAttributesFinder
     */
    protected $productAttributesFinder;

    /**
     * @var AttributeFormatter
     */
    protected $attributeFormatter;

    /**
     * @param   Context                     $context
     * @param   Api                         $api
     * @param   MciConfig                   $mciConfig
     * @param   CategoryAttributesBuilder   $categoryAttributesBuilder
     * @param   ProductAttributesFinder     $productAttributesFinder
     * @param   AttributeFormatter          $attributeFormatter
     */
    public function __construct(
        Context $context,
        MciConfig $mciConfig,
        Api $api,
        CategoryAttributesBuilder $categoryAttributesBuilder,
        ProductAttributesFinder $productAttributesFinder,
        AttributeFormatter $attributeFormatter
    ) {
        parent::__construct($context);
        $this->api = $api;
        $this->mciConfig = $mciConfig;
        $this->categoryAttributesBuilder = $categoryAttributesBuilder;
        $this->productAttributesFinder = $productAttributesFinder;
        $this->attributeFormatter = $attributeFormatter;
    }

    /**
     * Deletes all operator attributes on Mirakl platform
     *
     * @return  int|false
     */
    public function deleteAll()
    {
        return $this->exportTree('delete');
    }

    /**
     * Deletes de-synchroniezd attribute on Mirakl platform
     *
     * @return  int|false
     */
    public function deleteAttribute()
    {
        return $this->exportTree('delete', false);
    }

    /**
     * {@inheritdoc}
     */
    public function export(array $data)
    {
        if (!$this->isExportable()) {
            return false;
        }

        return $this->api->export($data);
    }

    /**
     * Exports all operator attributes to Mirakl platform
     *
     * @param   Process $process
     * @return  int|false
     */
    public function exportAll(Process $process = null)
    {
        if (!$this->isExportable()) {
            if ($process) {
                $process->output(__('Export has been blocked by another module.'));
            }

            return false;
        }

        if ($process) {
            $process->output(__('Preparing attributes to export...'), true);
        }

        $synchroId = $this->exportTree();

        if ($process) {
            $process->setSynchroId($synchroId);
            $process->output(__('Done! (%1)', $synchroId), true);
        }

        return $synchroId;
    }

    /**
     * Export an attribute to Mirakl platform
     *
     * @param   EavAttribute    $attribute
     * @return  int|false
     */
    public function exportAttribute(EavAttribute $attribute)
    {
        return $this->exportTree('update', false, [$attribute->getId()]);
    }

    /**
     * Exports operator attributes to Mirakl platform
     *
     * @param   null|string $action
     * @param   bool        $full
     * @param   array       $attributeIds
     * @return  int|false
     */
    public function exportTree($action = 'update', $full = true, array $attributeIds = [])
    {
        return $this->export($this->prepareCsvData($action, $full, $attributeIds));
    }

    /**
     * Returns attribute label
     *
     * @param   DataObject|EavAttribute $attribute
     * @return  string
     */
    protected function getAttributeLabel(DataObject $attribute)
    {
        if ($attribute->hasData('label')) {
            return $attribute->getData('label');
        }

        return $attribute->getStoreLabel($this->getStore()->getId());
    }

    /**
     * Returns exportable attribute codes according to current included attributes property
     *
     * @param   array   $attributeIds
     * @return  EavAttribute[]
     */
    public function getExportableAttributeCodes(array $attributeIds = [])
    {
        $attrCodes = [];
        $attributes = $this->productAttributesFinder->getExportableAttributes();

        if (empty($attributeIds)) {
            $attributeIds = array_keys($attributes);
        }

        foreach ($attributeIds as $attributeId) {
            if (isset($attributes[$attributeId])) {
                $attrCodes[] = $attributes[$attributeId]->getAttributeCode();
            }
        }

        return $attrCodes;
    }

    /**
     * @param   EavAttribute    $attribute
     * @return  bool
     */
    public function isAttributeExportable(EavAttribute $attribute)
    {
        $allowedAttributes = $this->productAttributesFinder->getExportableAttributes();

        return array_key_exists($attribute->getId(), $allowedAttributes);
    }

    /**
     * Prepare CSV for export
     *
     * @param   null|string $action
     * @param   bool        $full
     * @param   array       $attributeIds
     * @return  array
     */
    public function prepareCsvData($action = 'update', $full = true, array $attributeIds = [])
    {
        // Get current Mirakl attributes
        $miraklAttributes = $this->getMiraklAttributes();

        $categoryAttributes = (array) $this->categoryAttributesBuilder->build();

        // Add system attribute if not exist
        $attributesNeeded = AttributeUtil::getSystemAttributes();
        if (isset($miraklAttributes[''])) {
            $attributesNeeded = array_diff($attributesNeeded, $miraklAttributes['']);
        }
        $categoryAttributes[''] = array_merge($categoryAttributes[''], $attributesNeeded);

        // Find attribute to delete
        $data = [];
        if ($full && $action == 'delete') {
            $attributesToDelete = $miraklAttributes;
        } else {
            $attributesToDelete = AttributeUtil::diff($miraklAttributes, $categoryAttributes);
        }

        // Prepare CSV data for deleted attribute
        foreach ($attributesToDelete as $hCode => $attributes) {
            foreach ($attributes as $attrCode) {
                if ($hCode == '' && AttributeUtil::isSystem($attrCode)) {
                    continue; // don't remove system attribute
                }
                $data = array_merge($data, $this->prepareAttribute($attrCode, $hCode, 'delete'));
            }
        }

        // Find attribute to update
        if ($action == 'delete') {
            $attributesToUpdate = [];
        } elseif ($full) {
            $attributesToUpdate = $categoryAttributes;
        } else {
            $attributesToUpdate = AttributeUtil::diff($categoryAttributes, $miraklAttributes);
        }

        // Add included attributes (update attribute which is in Mirakl and Magento)
        if (!$full && $action == 'update' && !empty($attributeIds)) {
            $extraAttributeCodes = $this->getExportableAttributeCodes($attributeIds);

            foreach ($categoryAttributes as $hCode => $attributes) {
                $currentAttributes = array_intersect($extraAttributeCodes, $attributes);

                if (count($currentAttributes)) {
                    if (!isset($attributesToUpdate[$hCode])) {
                        $attributesToUpdate[$hCode] = array_unique($currentAttributes);
                    } else  {
                        $attributesToUpdate[$hCode] = array_unique(array_merge($attributesToUpdate[$hCode], $currentAttributes));
                    }
                }
            }
        }

        // Prepare CSV data for updated attribute
        foreach ($attributesToUpdate as $hCode => $attributes) {
            foreach ($attributes as $attrCode) {
                $data = array_merge($data, $this->prepareAttribute($attrCode, $hCode, 'update'));
            }
        }

        return $data; // Send CSV data to Mirakl
    }

    /**
     * Call PM11 to retrieve Mirakl attributes
     *
     * @return  array
     */
    protected function getMiraklAttributes()
    {
        $miraklAttributes = [];
        foreach ($this->api->getAttributesConfiguration() as $attribute) {
            /** @var \Mirakl\MCI\Common\Domain\Attribute $attribute */
            $code = $this->getOriginalAttributeCode($attribute->getCode());
            $miraklAttributes[$attribute->getHierarchyCode()][] = $code;
        }

        return array_map('array_unique', $miraklAttributes);
    }

    /**
     * Remove extra locale code from attribute code
     *
     * @param   string  $attrCode
     * @return  string  Attribute codes
     */
    public function getOriginalAttributeCode($attrCode)
    {
        $attrInfo = AttributeUtil::parse($attrCode);
        if ($attrInfo->isLocalized()) {
            $allowedLocales = $this->mciConfig->getAllowedLocales();
            if (in_array($attrInfo->getLocale(), $allowedLocales)) {
                $attribute = $this->productAttributesFinder->findByCode($attrInfo->getCode());
                if ($attribute && $attribute->getMiraklIsLocalizable()) {
                    return $attrInfo->getCode();
                }
            }
        }

        return $attrCode;
    }

    /**
     * @return  StoreInterface
     */
    protected function getStore()
    {
        return $this->mciConfig->getCatalogIntegrationStore();
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(DataObject $attribute, $action = null)
    {
        /** @var EavAttribute $attribute */

        if (null === $action) {
            $action = 'update';
        }

        $data = [
            'code'           => $attribute->getAttributeCode(),
            'label'          => $this->getAttributeLabel($attribute),
            'hierarchy-code' => strval($attribute->getData('hierarchy_code')),
            'required'       => $attribute->getData('is_required') ? 'true' : 'false',
            'type'           => $this->attributeFormatter->getAttributeType($attribute),
            'type-parameter' => $this->attributeFormatter->getAttributeTypeParameter($attribute),
            'variant'        => $attribute->getData('mirakl_is_variant') ? 'true' : 'false',
            'default-value'  => $attribute->getData('default_value'),
            'update-delete'  => $action,
        ];

        $stores = $this->mciConfig->getStoresForLabelTranslation();
        if (count($stores)) {
            $labels = $attribute->getStoreLabels();
            /** @var \Magento\Store\Model\Store $store */
            foreach ($stores as $store) {
                /** @var \Magento\Store\Model\Store $store */
                $storeLocale = $this->mciConfig->getLocale($store->getId());
                $data["label[$storeLocale]"] = isset($labels[$store->getId()]) ? $labels[$store->getId()] : '';
            }
        }

        $attribute = new DataObject($data);

        $this->_eventManager->dispatch('mirakl_mci_attribute_prepare', ['attribute' => $attribute]);

        return $attribute->getData();
    }

    /**
     * Prepare attributes to export
     *
     *  @param   string      $attrCode
     *  @param   string      $hierarchyCode
     *  @param   null|string $action
     *  @return  array
     */
    public function prepareAttribute($attrCode, $hierarchyCode, $action = null)
    {
        $attribute = $this->productAttributesFinder->findByCode($attrCode);

        if (!$attribute) {
            $attribute = new DataObject([
                'attribute_code' => $attrCode,
                'is_required'    => true,
            ]);

            switch ($attrCode) {
                case MciHelper::ATTRIBUTE_SKU:
                    $attribute->addData([
                        'label'          => (string) __('Shop SKU'),
                        'frontend_input' => 'text',
                    ]);
                    break;
                case MciHelper::ATTRIBUTE_CATEGORY:
                    $attribute->addData([
                        'label'          => (string) __('Category'),
                        'frontend_input' => 'text',
                    ]);
                    break;
                case MciHelper::ATTRIBUTE_VARIANT_GROUP_CODE:
                    $attribute->addData([
                        'label'          => (string) __('Variant Group Code'),
                        'is_required'    => false,
                        'frontend_input' => 'text',
                    ]);
                    break;
                default:
                    $attribute->addData([
                        'label'          => '',
                        'is_required'    => false,
                    ]);
            }
        }

        $attribute->setData('hierarchy_code', $hierarchyCode);

        $data = $this->prepare($attribute, $action);

        if ($attribute->getMiraklIsLocalizable()) {
            $result = [];
            foreach ($this->mciConfig->getAllowedLocales() as $locale) {
                $localeData = $data;
                $localeData['code'] = $data['code'] . '-' . $locale;
                if ($data['label']) {
                    $localeData['label'] = sprintf('%s (%s)', $data['label'], $locale);
                }
                if ($locale != $this->mciConfig->getLocale()) {
                    $localeData['required'] = 'false';
                }
                foreach ($localeData as $key => $value) {
                    // Add locale info on translated label
                    if ($value && preg_match('/^label\[[a-z]{2}_[A-Z]{2}\]$/', $key)) {
                        $localeData[$key] = $value . " ($locale)";
                    }
                }
                $result[] = $localeData;
            }
        } else {
            $result = [$data];
        }

        return $result;
    }
}
