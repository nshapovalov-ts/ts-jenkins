<?php
namespace Mirakl\Mcm\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Mcm\Helper\Config;

class McmEnableObserver implements ObserverInterface
{
    /**
     * @var array
     */
    public static $sourcesToBlock = [
        \Mirakl\Catalog\Helper\Product::EXPORT_SOURCE,
        \Mirakl\Catalog\Helper\Category::EXPORT_SOURCE,
    ];

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param   Config  $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $isMcmEnabled = $this->config->isMcmEnabled();

        /** @var DataObject $input */
        $input  = $observer->getEvent()->getInput();
        $source = $observer->getEvent()->getSource();

        if ($isMcmEnabled && in_array($source, self::$sourcesToBlock) && !empty($input)) {
            $input->setData('enabled', false);
        }
    }
}
