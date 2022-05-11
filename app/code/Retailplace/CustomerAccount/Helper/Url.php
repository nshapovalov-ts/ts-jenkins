<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Url extends AbstractHelper
{
    /**
     * @param $url
     * @return string
     */
    public function urlEncoder($url)
    {
        return $this->urlEncoder->encode($url);
    }
}
