<?php
namespace Mirakl\Core\Block\Adminhtml\Template\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class Logo extends AbstractRenderer
{
    /**
     * Render grid column
     *
     * @param   DataObject $row
     * @return  string
     */
    public function render(DataObject $row)
    {
        $imageUrl = $this->_getValue($row);

        return $this->decorateLogo($imageUrl);
    }

    /**
     * Show logo as image instead of URL
     *
     * @param   string  $value
     * @return  string
     */
    public function decorateLogo($value)
    {
        if ($value) {
            $value = sprintf('<img src="%s" style="max-width: 80px; max-height: 80px;">', $this->escapeHtml($value));
        }

        return $value;
    }
}
