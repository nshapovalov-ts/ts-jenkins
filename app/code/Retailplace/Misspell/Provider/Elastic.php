<?php
/**
 * Retailplace_Misspell
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Misspell\Provider;

use Magento\Framework\Exception\NoSuchEntityException;
use Mirasvit\Misspell\Api\ProviderInterface;
use Zend_Db_Statement_Exception;

/**
 * Elastic Class
 */
class Elastic implements ProviderInterface
{
    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var Suggester
     */
    private $suggester;

    /**
     * Mysql constructor.
     * @param Indexer $indexer
     * @param Suggester $suggester
     */
    public function __construct(
        Indexer $indexer,
        Suggester $suggester
    ) {
        $this->indexer = $indexer;
        $this->suggester = $suggester;
    }

    /**
     * Reindex
     *
     * @return bool
     * @throws NoSuchEntityException
     * @throws Zend_Db_Statement_Exception
     */
    public function reindex()
    {
        $this->indexer->reindex();

        return true;
    }

    /**
     * Suggest
     *
     * @param $phrase
     * @return string
     */
    public function suggest($phrase)
    {
        return $this->suggester->getSuggest($phrase);
    }
}
