<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_InvisibleCaptcha
 */


declare(strict_types=1);

namespace Amasty\InvisibleCaptcha\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Amasty\InvisibleCaptcha\Model\ConfigProvider;
use Amasty\InvisibleCaptcha\Model\Captcha as CaptchaModel;

class Captcha implements ArgumentInterface
{
    /**
     * @var CaptchaModel
     */
    private $captchaModel;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        CaptchaModel $captchaModel,
        ConfigProvider $configProvider
    ) {
        $this->captchaModel = $captchaModel;
        $this->configProvider = $configProvider;
    }

    /**
     * @return bool
     */
    public function isNeedToShowCaptcha(): bool
    {
        return $this->captchaModel->isNeedToShowCaptcha();
    }

    /**
     * @return array
     */
    public function getAllFormSelectors(): array
    {
        return $this->configProvider->getAllFormSelectors();
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->configProvider->getLanguage();
    }

    /**
     * @return string
     */
    public function getBadgeTheme()
    {
        return $this->configProvider->getBadgeTheme();
    }

    /**
     * @return string
     */
    public function getBadgePosition()
    {
        return $this->configProvider->getBadgePosition();
    }

    /**
     * @return string
     */
    public function getSiteKey()
    {
        return $this->configProvider->getSiteKey();
    }
}
