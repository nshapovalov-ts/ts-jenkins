<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\ViewModel;

use Magento\Directory\Block\Data as DirectoryBlock;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

class Country implements ArgumentInterface
{
    const XML_PATH_REGION_DISPLAY_ALL = 'general/region/display_all';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var DirectoryBlock
     */
    private $directoryBlock;

    /**
     * Configurable constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryBlock $directoryBlock
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DirectoryBlock $directoryBlock
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->directoryBlock = $directoryBlock;
    }

    /**
     * @return string
     */
    public function getRegionDisplayAll()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_REGION_DISPLAY_ALL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getCountryHtmlSelect()
    {
        return $this->directoryBlock->getCountryHtmlSelect();
    }
}
