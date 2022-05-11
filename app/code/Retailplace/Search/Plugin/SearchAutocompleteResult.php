<?php

namespace Retailplace\Search\Plugin;

use Mirasvit\SearchAutocomplete\Model\Result;

class SearchAutocompleteResult
{
    /**
     * @param Result $subject
     * @param array $result
     * @return array
     */
    public function afterToArray(
        Result $subject,
        $result
    ) {
        if ($result['noResults'] == true) {
            $result['noResults'] = false;

            $item = [
                'query_text'  => $result['query'],
                'num_results' => 0,
                'popularity'  => 0,
                'url'         => $result['urlAll'],
            ];

            foreach ($result['indices'] as $key => $index) {
                if ($index['identifier'] == 'magento_search_query') {
                    $result['indices'][$key]['items'][] = $item;
                    $result['indices'][$key]['totalItems'] = 1;
                }
            }
        }
        return $result;
    }
}
