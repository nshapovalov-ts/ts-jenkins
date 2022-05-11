<?php
/**
 * Retailplace_Search
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Search\Plugin;

use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Amasty\Shopby\Model\Request;

/**
 * Class FilterParamValidation
 */
class FilterParamValidation
{

    /**
     * @param Request $subject
     * @param mixed|string $param
     * @param AbstractFilter $filter
     * @return mixed|string
     */
    public function afterGetFilterParam(Request $subject, $param, AbstractFilter $filter)
    {
        if (!empty($param)) {
            if ($filter instanceof AbstractFilter) {
                $attributeModel = $filter->getData('attribute_model');
                $filterCode = $filter->getRequestVar();
                $type = null;
                if (!empty($attributeModel)) {
                    $type = $attributeModel->getBackendType();
                } else {
                    if (in_array($filterCode, ['cat'])) {
                        $type = 'int';
                    }
                }

                switch ($type) {
                    case "int":
                        $param = $this->filterParamForInt($param);
                        break;
                    case "decimal":
                        $param = $this->filterParamForDecimal($param);
                        break;
                }
            }
        }

        return $param;
    }

    /**
     * Filter param for int and decimal
     *
     * @param $param
     * @param string $delimiter
     * @return mixed|string
     */
    private function filterParamForInt($param, $delimiter = ',')
    {
        if (!preg_match('/\d+/', $param)) {
            return "";
        }

        $param = preg_replace('/[^0-9' . $delimiter . ']/', '', $param);

        if (preg_match('/^\d+$/', $param)) {
            return $param;
        }

        $params = explode($delimiter, $param);

        $params = array_filter($params, function ($v, $k) {
            return !empty($v);
        }, ARRAY_FILTER_USE_BOTH);

        $param = implode($params, $delimiter);

        return !empty($param) ? $param : "";
    }

    /**
     * Filter param for int and decimal
     *
     * @param $param
     * @param string $delimiter
     * @return mixed|string
     */
    private function filterParamForDecimal($param, $delimiter = '-')
    {
        if (!preg_match('/\d+/', $param)) {
            return "";
        }

        $isDotExist = false;

        if (preg_match('/\.\d+/', $param)) {
            $param = preg_replace('/\.\d+/', '', $param);
            $isDotExist = true;
        }

        $param = preg_replace('/[^0-9' . $delimiter . ']/', '', $param);

        if (preg_match('/^\\' . $delimiter . '\d+$/', $param) || preg_match('/^\d+\\' . $delimiter . '$/', $param)) {
            return $param;
        }

        $params = explode($delimiter, $param);

        $params = array_filter($params, function ($v, $k) {
            return !empty($v);
        }, ARRAY_FILTER_USE_BOTH);

        if ($isDotExist) {
            $params = array_map(function ($v) {
                return $v . ".00";
            }, $params);
        }

        $param = implode($params, $delimiter);

        if (count($params) == 1) {
            $param .= $delimiter;
        }

        return !empty($param) ? $param : "";
    }
}
