<?php
/**
 * Retailplace_Misspell
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Misspell\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Mirasvit\Misspell\Helper\Query as QueryHelper;
use Mirasvit\Misspell\Model\Config;
use Mirasvit\Misspell\Observer\OnCatalogSearchObserver as CatalogSearchObserver;

class OnCatalogSearchObserver extends CatalogSearchObserver
{
    /**
     * @var QueryHelper
     */
    private $queryHelper;

    /**
     * @var HttpResponse
     */
    private $response;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * OnCatalogSearchObserver constructor.
     * @param QueryHelper $queryHelper
     * @param Config $config
     * @param HttpResponse $response
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        QueryHelper          $queryHelper,
        Config               $config,
        HttpResponse         $response,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct(
            $queryHelper,
            $config,
            $response
        );

        $this->queryHelper = $queryHelper;
        $this->response = $response;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Run spell correction
     *
     * @return bool
     */
    public function doSpellCorrection(): bool
    {
        $queryText = $this->queryHelper->getQueryText();

        $suggestedText = $this->queryHelper->suggest($queryText);

        if ($suggestedText
            && $suggestedText != $queryText
            && $suggestedText != $this->queryHelper->getMisspellText()
        ) {
            $isUseSearchTable = (bool)$this->scopeConfig->getValue('misspell/general/is_use_search_table');

            // perform redirect
            if (!$isUseSearchTable || $this->queryHelper->getNumResults($suggestedText)) {
                $url = $this->queryHelper->getMisspellUrl($queryText, $suggestedText);
                $this->response->setRedirect($url);

                return true;
            }
        }

        return false;
    }
}
