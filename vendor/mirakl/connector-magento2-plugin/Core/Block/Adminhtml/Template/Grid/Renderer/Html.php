<?php
namespace Mirakl\Core\Block\Adminhtml\Template\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class Html extends AbstractRenderer
{
    /**
     * Render grid column
     *
     * @param   DataObject  $row
     * @return  string
     */
    public function render(DataObject $row)
    {
        return html_entity_decode($this->_getValue($row));
    }
}
