<?php

namespace App\Services;

// ユーザーが指定する演算子を解析する
class KeywordOperatorAnalyzer
{
    // 「 OR 」を「|」（正規表現で「または」を表す）に置換する
    public static function ReplaceStrORToPipe($org)
    {
        if ($org === ' OR ') {
            return '';
        }
        return str_ireplace(' OR ', '|', $org);
    }

    // 「 -」演算子の前後で文字列を分割する
    // $org：変換元の文字列
    // $before_needle：true=「 -」の前部分、false=「 -」の後ろ部分
    // もし「 -」がなければ、trueは$orgをそのまま返し、falseは空文字を返す。
    public static function SeparateByNotOperator($org, $before_needle)
    {
        $operator = " -";

        if ($before_needle) {
            // 「 -」より前を取得
            if (mb_strstr($org, ' -', true)) {
                return mb_strstr($org, ' -', true);
            } else {
                return $org;
            }
        } else {
            // 「 -」より後ろを取得
            $index = mb_strpos($org, $operator);
            if ($index === false) {
                return '';
            }
            $index += mb_strlen($operator);
            $str = mb_substr($org, $index);
            // もし文字列に複数「 -」があれば、一個目の「 -」だけを演算子とみなし、
            // 二個目以降は、AND演算子とみなす。
            return str_ireplace(' -', ' ', $str);
        }
    }

    // 「 」（半角スペース）をデリミタにして、配列を返す
    public static function StrToArrayBySpace($org)
    {
        if ($org === "") {
            return [];
        } else {
            return explode(' ', $org);
        }
    }
}
