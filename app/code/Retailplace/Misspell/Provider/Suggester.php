<?php
/**
 * Retailplace_Misspell
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Misspell\Provider;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Mirasvit\Misspell\Helper\Text as TextHelper;
use Mirasvit\Misspell\Helper\Damerau as DamerauHelper;
use Retailplace\Misspell\Model\Search\Engine;
use Mirasvit\Misspell\Provider\Suggester as ProviderSuggester;

/**
 * Suggester Class
 */
class Suggester extends ProviderSuggester
{
    /**
     * @var DamerauHelper
     */
    private $damerauHelper;

    /**
     * @var Engine
     */
    private $engine;

    /**
     * Suggester constructor.
     * @param ResourceConnection $resource
     * @param TextHelper $textHelper
     * @param DamerauHelper $damerauHelper
     * @param Engine $engine
     */
    public function __construct(
        ResourceConnection $resource,
        TextHelper         $textHelper,
        DamerauHelper      $damerauHelper,
        Engine             $engine
    ) {
        parent::__construct(
            $resource,
            $textHelper,
            $damerauHelper
        );

        $this->damerauHelper = $damerauHelper;
        $this->engine = $engine;
    }

    /**
     * Get Best Match
     *
     * @param $baseQuery
     * @param int $limit
     * @return array
     * @throws Exception
     */
    public function getBestMatch($baseQuery, int $limit = 10): array
    {
        $result = $this->engine->query(Indexer::INDEX_NAME, [
            '_source' => true,
            'query' => [
                'fuzzy' => [
                    'keyword' => $baseQuery
                ]
            ],
            'size' => $limit,
        ]);

        if (!$result) {
            return ['keyword' => $baseQuery, 'diff' => 100];
        }

        $hits = $result['hits']['hits'];

        $keywords = [];
        foreach ($hits as $hit) {
            $keywords[] = [
                'keyword' => (string)$hit['_source']['keyword'],
                'frequency' => round((float)$hit["_score"])
            ];
        }

        if (count($keywords) == 0) {
            return ['keyword' => $baseQuery, 'diff' => 100];
        }

        $maxFreq = 0.0001;
        foreach ($keywords as $keyword) {
            $maxFreq = max($keyword['frequency'], $maxFreq);
        }

        $preResults = [];
        foreach ($keywords as $keyword) {
            $preResults[$keyword['keyword']] = $this->damerauHelper->similarity($baseQuery, $keyword['keyword'])
                + $keyword['frequency'] * (10 / $maxFreq);
        }
        arsort($preResults);

        $keys = array_keys($preResults);

        if (count($keys) > 0) {
            $keyword = $keys[0];
            $keyword = $this->toSameRegister($keyword, $baseQuery);
            $diff = $preResults[$keys[0]];
            $resultArray = ['keyword' => $keyword, 'diff' => $diff];
        } else {
            $resultArray = ['keyword' => $baseQuery, 'diff' => 100];
        }

        return $resultArray;
    }
}
