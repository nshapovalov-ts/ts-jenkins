<?php
namespace Mirakl\Mci\Observer\Adminhtml;

use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AttributeEditFormObserver implements ObserverInterface
{
    /**
     * @var Yesno
     */
    private $yesNo;

    /**
     * @param   Yesno   $yesNo
     */
    public function __construct(Yesno $yesNo)
    {
        $this->yesNo = $yesNo;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $observer->getEvent()->getForm();

        // Add Mirakl fieldset
        $fieldset = $form->addFieldset('mirakl_fieldset', [
            'legend' => __('Mirakl Settings')
        ]);

        $yesNoValues = $this->yesNo->toOptionArray();

        // Add Mirakl parameters
        $fieldset->addField('mirakl_is_exportable', 'select', [
            'name'   => 'mirakl_is_exportable',
            'label'  => __('Is Exportable'),
            'note'   => __('If set to yes, attribute will be exported to Mirakl through API PM01.'),
            'values' => $yesNoValues,
        ]);

        $fieldset->addField('mirakl_is_variant', 'select', [
            'name'     => 'mirakl_is_variant',
            'label'    => __('Is Variant'),
            'note'     => __('This flag is used in API PM01. If set to yes, attribute cannot be localizable.'),
            'values'   => $yesNoValues,
            'onchange' => "if (this.value && jQuery('#mirakl_is_localizable')) jQuery('#mirakl_is_localizable').val(0)",
        ]);

        $fieldset->addField('mirakl_is_localizable', 'select', [
            'name'     => 'mirakl_is_localizable',
            'label'    => __('Is Localizable'),
            'note'     => __('This flag is used in API PM01. If set to yes, attribute cannot be flagged as variant.'),
            'onchange' => "if (this.value && jQuery('#mirakl_is_variant')) jQuery('#mirakl_is_variant').val(0)",
            'values'   => $yesNoValues,
        ]);
    }
}