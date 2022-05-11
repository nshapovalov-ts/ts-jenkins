<?php
namespace Retailplace\MiraklSeller\Block\Seller;
class ProductCollection extends \Magento\Framework\View\Element\Template
{
    private $productRepository; 
    protected $_imageHelper;
    protected $_cartHelper;
    protected $product;
    protected $_offerModel;
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\Product $product,
        \Mirakl\Connector\Model\Offer $offerModel,
        array $data = []
    ) {
        $this->productRepository = $productRepository;    
        $this->product = $product;
        $this->_offerModel = $offerModel;
        $this->_imageHelper = $context->getImageHelper();
        $this->_cartHelper = $context->getCartHelper();
        parent::__construct($context, $data);
    }
    public function imageHelperObj(){
        return $this->_imageHelper;
    }
    public function getProduct($sku){
        return  $this->productRepository->get($sku);
    }
    /**
    To get mirakl product collection
    */
    public function getMiraklsellerProduct($sellerId){
        return $this->_offerModel->getCollection()->addFilter('shop_id',$sellerId)->addFilter('active','true');
    }
    public function getAddToCartUrl($product, $additional = []) {
        return $this->_cartHelper->getAddUrl($product, $additional);
    }
    public function getProductPriceHtml(
        \Magento\Catalog\Model\Product $product,
        $priceType = null,
        $renderZone = \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST,
        array $arguments = []
    ) {
        if (!isset($arguments['zone'])) {
            $arguments['zone'] = $renderZone;
        }
        $arguments['zone'] = isset($arguments['zone']) ? $arguments['zone'] : $renderZone;
        $arguments['price_id'] = isset($arguments['price_id'])  ? $arguments['price_id']  : 'old-price-' . $product->getId() . '-' . $priceType;
        $arguments['include_container'] = isset($arguments['include_container']) ? $arguments['include_container']  : true;
        $arguments['display_minimal_price'] = isset($arguments['display_minimal_price']) ? $arguments['display_minimal_price'] : true;
        $priceRender = $this->getLayout()->getBlock('product.price.render.default');
        $price = '';
        if ($priceRender) {
            $price = $priceRender->render(
                \Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE,
                $product,
                $arguments
            );
        }
        return $price;
    }
}
?>