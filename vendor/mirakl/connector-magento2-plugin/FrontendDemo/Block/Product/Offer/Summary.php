<?php
namespace Mirakl\FrontendDemo\Block\Product\Offer;

use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;

/**
 * @method  Product getProduct
 * @method  $this   setProduct(Product $product)
 */
class Summary extends Template
{
    /**
     * @var string
     */
    protected $_template = 'product/offer/summary.phtml';

    /**
     * @var OfferHelper
     */
    protected $offerHelper;

    /**
     * @param   Template\Context    $context
     * @param   OfferHelper         $offerHelper
     * @param   array               $data
     */
    public function __construct(Template\Context $context, OfferHelper $offerHelper, array $data = [])
    {
        parent::__construct($context, $data);
        $this->offerHelper = $offerHelper;
    }

    /**
     * Returns offers summary for current product
     *
     * @return  array
     */
    public function getSummary()
    {
        $summary = [];

        if ($product = $this->getProduct()) {
            $summary = $this->offerHelper->getOffersSummary($product);
        }

        return $summary;
    }
}
