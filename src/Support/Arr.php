<?php

namespace Sunkeydev\Wx\Support;

use Illuminate\Support\Arr as BaseArr;
use Illuminate\Support\Str;

class Arr extends BaseArr
{
    /**
     * 将数据的键转化成camel形式
     * @param  array $array 数组
     * @return 转化结果
     */
    public static function camelKey($array)
    {
        $result = [];
        foreach ($array as $key => $val) {
            if (!is_numeric($key)) {
                $key = Str::camel($key);
            }
            if (is_array($val)) {
                $val = self::camelKey($val);
            }

            $result[$key] = $val;
        }

        return $result;
    }
}