<?php
/**
 * Retailplace_AmastyPageSpeedOptimizer
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\AmastyPageSpeedOptimizer\Rewrite\Model\Output;

use Amasty\PageSpeedOptimizer\Model\OptionSource\LazyLoadScript;
use Amasty\PageSpeedOptimizer\Model\OptionSource\PreloadStrategy;
use Magento\Framework\DataObject;
use Magento\Framework\View\Layout;
use Amasty\PageSpeedOptimizer\Model\ConfigProvider;
use Magento\Framework\View\Asset\Repository;
use Amasty\PageSpeedOptimizer\Model\Image\OutputImage;
use Magento\Framework\DataObjectFactory;
use Amasty\PageSpeedOptimizer\Model\Output\DeviceDetect;
use Magento\Framework\Stdlib\CookieManagerInterface;

/**
 * Class LazyLoadProcessor
 */
class LazyLoadProcessor extends \Amasty\PageSpeedOptimizer\Model\Output\LazyLoadProcessor
{
    /**
     * @var string
     */
    const LAZY_LOAD_PLACEHOLDER = 'src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQImWP4//8/MwAI/wMBt+jMDAAAAABJRU5ErkJggg=="';
    const IMAGE_REGEXP = '<img([^>]*?)src=(\"|\'|)(.*?)(\"|\'| )(.*?)>';
    const HOME = 'cms_index_index';
    const CATEGORY = 'catalog_category_view';
    const PRODUCT = 'catalog_product_view';
    const CMS = 'cms_page_view';
    const GENERAL = 'general';
    const IS_SIMPLE = 'is_simple';
    const IS_REPLACE_WITH_USER_AGENT = 'is_replace_with_user_agent';
    const IS_LAZY = 'is_lazy';
    const LAZY_IGNORE_LIST = 'lazy_ignore_list';
    const LAZY_SKIP_IMAGES = 'lazy_skip_images';
    const LAZY_PRELOAD_STRATEGY = 'lazy_preload_strategy';
    const LAZY_SCRIPT = 'lazy_script';
    const REPLACE_IMAGES_IF_NOT_LAZY = 'replace_images_if_not_lazy';
    const REPLACE_IMAGES_IGNORE_LIST = 'replace_images_ignore_list';

    /**
     * @var array
     */
    const PAGE_CONFIG = [
        self::HOME     => 'lazy_load_home',
        self::CATEGORY => 'lazy_load_categories',
        self::PRODUCT  => 'lazy_load_products',
        self::CMS      => 'lazy_load_cms',
        self::GENERAL  => 'lazy_load_general'
    ];

    /**
     * @var DataObject
     */
    private $lazyConfig;

    /**
     * Add Lazy Script
     *
     * @param $output
     * @param $lazyScript
     * @see \Amasty\PageSpeedOptimizer\Model\Output\LazyLoadProcessor::addLazyScript()
     */
    public function addLazyScript(&$output, $lazyScript)
    {
        $lazy = '<script>window.amlazy = function() {'
            . 'if (typeof window.amlazycallback !== "undefined") {'
            . 'setTimeout(window.amlazycallback, 500);setTimeout(window.amlazycallback, 1500);}'
            . '}</script>';
        switch ($lazyScript) {
            case LazyLoadScript::NATIVE_LAZY:
                $lazy .= '<script>
                        require(["jquery"], function (jquery) {
                            require(["Amasty_PageSpeedOptimizer/js/nativejs.lazy"], function(lazy) {})
                        });
                    </script>';
                break;
            case LazyLoadScript::JQUERY_LAZY:
            default:
                $lazy .= '<script>
                        window.amlazycallback = function () {
                            window.jQuery("img[data-amsrc]").lazy({"bind":"event", "attribute": "data-amsrc"});
                        };
                        require(["jquery"], function (jquery) {
                            require(["Amasty_PageSpeedOptimizer/js/jquery.lazy"], function(lazy) {
                                if (document.readyState === "complete") {
                                    window.jQuery("img[data-amsrc]").lazy({"bind":"event", "attribute": "data-amsrc"});
                                } else {
                                    window.jQuery("img[data-amsrc]").lazy({"attribute": "data-amsrc"});
                                }
                            })
                        });
                    </script>';
                break;
        }
        $output = str_ireplace('</body', $lazy . '</body', $output);
    }

    /**
     * Get Lazy Config
     *
     * @return DataObject
     */
    public function getLazyConfig(): DataObject
    {
        if ($this->lazyConfig === null) {
            $this->lazyConfig = parent::getLazyConfig();
        }

        return $this->lazyConfig;
    }

    /**
     * Process Lazy Images
     *
     * @param $output
     * @see \Amasty\PageSpeedOptimizer\Model\Output\LazyLoadProcessor::processLazyImages()
     */
    public function processLazyImages(&$output)
    {
        $tempOutput = preg_replace('/<script[^>]*>(?>.*?<\/script>)/is', '', $output);

        if (preg_match_all('/' . self::IMAGE_REGEXP . '/is', $tempOutput, $images)) {
            $skipCounter = 1;

            if ($this->lazyConfig === null) {
                $this->getLazyConfig();
            }

            $preloadStrategy = $this->lazyConfig->getData(self::LAZY_PRELOAD_STRATEGY);

            foreach ($images[0] as $key => $image) {
                if ($this->skipIfContain($image, $this->lazyConfig->getData(self::LAZY_IGNORE_LIST))) {
                    if ($this->lazyConfig->getData(self::IS_REPLACE_WITH_USER_AGENT)
                        && !$this->skipIfContain($image, $this->lazyConfig->getData(self::REPLACE_IMAGES_IGNORE_LIST))
                    ) {
                        $newImg = $this->replaceWithBest($image, $images[3][$key]);
                        $output = str_replace($image, $newImg, $output);
                    }

                    continue;
                }

                if ($skipCounter < $this->lazyConfig->getData(self::LAZY_SKIP_IMAGES)) {
                    if ($this->lazyConfig->getData(self::IS_REPLACE_WITH_USER_AGENT)) {
                        $newImg = $this->replaceWithBest($image, $images[3][$key]);
                        $output = str_replace($image, $newImg, $output);
                    } else {
                        if ($preloadStrategy == PreloadStrategy::SKIP_IMAGES) {
                            $skipCounter++;
                            continue;
                        }

                        $newImg = $this->replaceWithPictureTag($image, $images[3][$key]);
                        $output = str_replace($image, $newImg, $output);
                    }

                    $skipCounter++;
                    continue;
                }

                $replace = 'src=' . $images[2][$key] . $images[3][$key] . $images[4][$key];
                $newImg = str_replace($replace, self::LAZY_LOAD_PLACEHOLDER . ' data-am' . $replace, $image);

                if ($this->lazyConfig->getData(self::IS_REPLACE_WITH_USER_AGENT)) {
                    $newImg = $this->replaceWithBest($newImg, $images[3][$key]);
                }

                $newImg = preg_replace('/srcset=[\"\'\s]+(.*?)[\"\']+/is', '', $newImg);
                $output = str_replace($image, $newImg, $output);
            }
        }
    }

    /**
     * Skip If Contain
     *
     * @param string $searchString
     * @param array $list
     * @return bool
     */
    private function skipIfContain(string $searchString, array $list)
    {
        $skip = false;
        foreach ($list as $item) {
            if (strpos($searchString, $item) !== false) {
                $skip = true;
                break;
            }
        }

        return $skip;
    }
}
