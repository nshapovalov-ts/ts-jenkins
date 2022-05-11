<?php
namespace Retailplace\SellerAffiliate\Block\Adminhtml\Column\Renderer;

use \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\Url;
use Magento\Backend\Block\Context;

class AffiliateUrl extends AbstractRenderer
{
    /** @var Url */
    protected $urlHelper;

    public function __construct(Context $context, Url $urlHelper, array $data = [])
    {
        parent::__construct($context, $data);
        $this->urlHelper = $urlHelper;
    }

    /**
     * Render link for product
     *
     * @param  \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        $url = $this->urlHelper->getUrl('marketplace/shop/view', ['id' => $value]) ."#u$value";
        $output = '<a href="' . $url . '">' . 'Link' . '</a>';
        return $output;
    }
}
