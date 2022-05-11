<?php
namespace Mirakl\Connector\Block\Adminhtml\Offer\Grid\Column\Product;

use Magento\Backend\Block\Widget\Grid\Column;
use Mirakl\Connector\Model\Offer;

class Sku extends Column
{
    /**
     * Decorates column value
     *
     * @param   string  $value
     * @param   Offer   $row
     * @return  string
     */
    public function decorate($value, $row)
    {
        if ($productId = $row->getData('product_id')) {
            $url = $this->getUrl('catalog/product/edit', ['id' => $productId]);

            return sprintf(
                '<a href="%s" title="%s">%s</a>',
                $this->escapeUrl($url),
                $this->escapeHtml(__('Edit Product')),
                $this->escapeHtml($value)
            );
        }

        return $this->escapeHtml($value);
    }

    /**
     * @return  array
     */
    public function getFrameCallback()
    {
        return [$this, 'decorate'];
    }
}