<?php
namespace Mirakl\Mci\Helper;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Locale\ListsInterface as LocaleListsInterface;
use Mirakl\Api\Helper\ValueList as Api;
use Mirakl\Connector\Common\ExportInterface;
use Mirakl\Connector\Common\ExportTrait;
use Mirakl\Mci\Helper\Attribute as AttributeHelper;
use Mirakl\Mci\Helper\Config as MciConfig;
use Mirakl\Process\Model\Process;

class ValueList extends AbstractHelper implements ExportInterface
{
    use ExportTrait;

    const EXPORT_SOURCE = 'VL01';

    /**
     * @var ProductResource
     */
    protected $productResource;

    /**
     * @var LocaleListsInterface
     */
    protected $localeLists;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var MciConfig
     */
    protected $mciConfig;

    /**
     * @var AttributeHelper
     */
    protected $attributeHelper;

    /**
     * @param   Context                 $context
     * @param   ProductResource         $productResource
     * @param   LocaleListsInterface    $localeLists
     * @param   Api                     $api
     * @param   MciConfig               $mciConfig
     * @param   AttributeHelper         $attributeHelper
     */
    public function __construct(
        Context $context,
        ProductResource $productResource,
        LocaleListsInterface $localeLists,
        Api $api,
        MciConfig $mciConfig,
        AttributeHelper $attributeHelper
    ) {
        parent::__construct($context);
        $this->productResource = $productResource;
        $this->localeLists = $localeLists;
        $this->api = $api;
        $this->mciConfig = $mciConfig;
        $this->attributeHelper = $attributeHelper;
    }

    /**
     * Delete all values of all attributes
     *
     * @return  int|false
     */
    public function deleteAttributes()
    {
        $allAttributes = $this->getAvailableAttributes();

        $attributes = [];
        foreach ($allAttributes as $attribute) {
            /** @var EavAttribute $attribute */
            if ($this->isAttributeExportable($attribute)) {
                $attributes = array_merge($attributes, $this->prepare($attribute, 'delete'));
            }
        }

        return !empty($attributes) ? $this->export($attributes) : false;
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
     * Exports value lists of all attributes using options
     *
     * @param   Process $process
     * @return  int|false
     */
    public function exportAttributes(Process $process = null)
    {
        if (!$this->isExportable()) {
            if ($process) {
                $process->output(__('Export has been blocked by another module.'));
            }

            return false;
        }

        if ($process) {
            $process->output(__('Preparing value lists to export...'), true);
        }

        $allAttributes = $this->getAvailableAttributes();

        $attributes = [];
        foreach ($allAttributes as $attribute) {
            /** @var EavAttribute $attribute */
            if ($this->isAttributeExportable($attribute)) {
                $attributes = array_merge($attributes, $this->prepare($attribute));
            }
        }

        $attributes = new DataObject($attributes);

        $this->_eventManager->dispatch('mirakl_mci_export_values_lists_export_before', [
            'attributes' => $attributes,
        ]);

        if ($attributes->isEmpty()) {
            if ($process) {
                $process->output(__('No attribute available for export!'), true);
            }

            return false;
        }

        $synchroId = $this->export($attributes->getData());

        if ($process) {
            $process->setSynchroId($synchroId);
            $process->output(__('Done! (%1)', $synchroId), true);
        }

        return $synchroId;
    }

    /**
     * @return  array
     */
    public function getAvailableAttributes()
    {
        return $this->productResource
            ->loadAllAttributes()
            ->getAttributesByCode();
    }

    /**
     * @param   EavAttribute    $attribute
     * @return  bool
     */
    public function isAttributeExportable(EavAttribute $attribute)
    {
        return $attribute->usesSource()
            && $attribute->getIsVisible()
            && $this->attributeHelper->isAttributeExportable($attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(DataObject $attribute, $action = null)
    {
        /** @var EavAttribute $attribute */

        if (!$attribute->usesSource()) {
            return [];
        }

        $data = [];

        // Define store id on attribute to translate attribute label and options
        $store = $this->mciConfig->getCatalogIntegrationStore();
        $attribute->setStoreId($store->getId());

        $options = $attribute->getSource()->getAllOptions(false);
        if ($option = $attribute->getOption()) {
            $options = array_merge($options, array_map(function ($value) use ($option) {
                return [
                    'value' => $value,
                    'label' => $option['value'][$value][0],
                    'action' => 'delete',
                ];
            }, array_keys(isset($option['delete']) ? $option['delete'] : [], '1', true)));
        }

        // Looping on attribute options
        foreach ($options as $option) {
            if ('' === $option['value']) {
                continue;
            }

            // Convert option value to array in order to handle lists that include <optgroup> dimension
            if (!is_array($option['value'])) {
                $option = [
                    'label' => false,
                    'value' => [$option],
                ];
            }

            foreach ($option['value'] as $_option) {
                $data[] = $this->prepareOption(
                    $attribute,
                    $attribute->getStoreLabel($store->getId()) ?: $attribute->getFrontendLabel(),
                    $_option['value'],
                    ($option['label'] ? $option['label'] . ' > ' : '') . $_option['label'],
                    isset($_option['action']) ? $_option['action'] : $action,
                    $this->mciConfig->getStoresForLabelTranslation()
                );
            }
        }

        // Reinitialize attribute store id
        $attribute->setStoreId(\Magento\Store\Model\Store::DEFAULT_STORE_ID);

        return $data;
    }

    /**
     * @param   EavAttribute    $attribute
     * @param   string          $attributeLabel
     * @param   string          $optionValue
     * @param   string          $optionLabel
     * @param   null|string     $action
     * @param   null|array      $stores
     * @return  array
     */
    public function prepareOption(EavAttribute $attribute, $attributeLabel, $optionValue, $optionLabel, $action = null, $stores = null)
    {
        $optionData = [
            'list-code'     => $attribute->getAttributeCode(),
            'list-label'    => $action !== 'delete' ? $attributeLabel : '',
            'value-code'    => $optionValue,
            'value-label'   => $action !== 'delete' ? $optionLabel : '',
            'update-delete' => null === $action ? 'update' : $action,
        ];

        if (count($stores)) {
            $labels = $attribute->getStoreLabels();

            /** @var \Magento\Store\Model\Store $store */
            foreach ($stores as $store) {
                $storeLocale = $this->mciConfig->getLocale($store->getId());
                $optionData["list-label[$storeLocale]"] = isset($labels[$store->getId()]) ? $labels[$store->getId()] : '';
                $attribute->setStoreId($store->getId());

                $options = $attribute->getSource()->getAllOptions(false);
                foreach ($options as $option) {
                    if ($option['value'] == $optionValue) {
                        $optionData["value-label[$storeLocale]"] = $option['label'];
                    }
                }
            }
        }

        return $optionData;
    }

    /**
     * {@inheritdoc}
     */
    protected function wrap(DataObject $object, $action = null)
    {
        return $this->prepare($object, $action);
    }
}
