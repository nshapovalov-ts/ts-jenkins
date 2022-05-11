<?php
namespace Mirakl\FrontendDemo\Block\Shop;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class View extends Template
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var string
     */
    protected $_template = 'shop/view.phtml';

    /**
     * @param   Context     $context
     * @param   Registry    $registry
     * @param   array       $data
     */
    public function __construct(Context $context, Registry $registry, array $data = [])
    {
        parent::__construct($context, $data);
        $this->coreRegistry = $registry;
    }

    /**
     * @return  $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->pageConfig->addBodyClass('account');
        $shop = $this->getShop();

        $this->_setTabTitle();

        if (!$shop || !$shop->getId()) {
            return $this;
        }

        if ($title = $shop->getName()) {
            $this->pageConfig->getTitle()->set($title);
        }

        if ($description = $shop->getDescription()) {
            $this->pageConfig->setDescription($description);
        }

        /** @var \Magento\Theme\Block\Html\Title $pageMainTitle */
        if ($pageMainTitle = $this->getLayout()->getBlock('page.main.title')) {
            $pageMainTitle->setPageTitle($shop->getName());
        }

        return $this;
    }

    /**
     * @return  \Mirakl\Core\Model\Shop
     */
    public function getShop()
    {
        return $this->coreRegistry->registry('mirakl_shop');
    }

    /**
     * @return  string
     */
    public function getShopDescription()
    {
        $description = $this->getShop()->getDescription();

        // Remove Javascript or style tags
        $description = preg_replace('#<(script|style)[^>]*?>.*?</\\1>#si', '', $description);

        return $description;
    }

    /**
     * Calculate and format the duration for the human interface
     *
     * @param   int $duration
     * @return  string
     */
    public function formatDuration($duration)
    {
        // Convert duration to hours
        $durationHours = $duration / 24 / 60;

        if ($durationHours <= 12) {
            return 12 . ' ' . __('Hours');
        }

        if ($durationHours > 12 && $durationHours <= 24) {
            return 1 . ' ' . __('Day');
        }

        return date('j', $duration) . ' ' . __('Days');
    }

    /**
     * {@inheritdoc}
     */
    protected function _setTabTitle()
    {
        $title = __('Description');
        $this->setTitle($title);
    }
}