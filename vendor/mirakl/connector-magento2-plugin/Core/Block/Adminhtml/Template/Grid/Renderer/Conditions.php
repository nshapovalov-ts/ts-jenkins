<?php
namespace Mirakl\Core\Block\Adminhtml\Template\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class Conditions extends AbstractRenderer
{
    /**
     * Render grid column
     *
     * @param   DataObject  $row
     * @return  string
     */
    public function render(DataObject $row)
    {
        /** @var \Mirakl\Core\Model\Shipping\Zone $row */
        if ($row->getRule()->getConditions()->getConditions()) {
            return nl2br($row->getRule()->getConditions()->asStringRecursive());
        }

        return __('No condition');
    }
}
