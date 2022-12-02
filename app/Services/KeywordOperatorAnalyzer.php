<?php

namespace App\Services;

//  ユーザーがシステム上指定するいいねやフォローのAND,OR,NOTの複数のキーワードを一定の規則で変換して一つ文字列に変換する。
// またはその文字列を、複数の文字列に複合する。
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
            if (mb_strstr($org, ' -', true) !== false) {
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

    // 「A B (C OR D OR E) -F -G」を「A,B」「C,D,E」「F,G」に分ける
    public static function operatorStrToCSV($str, &$andStr, &$orStr, &$notStr)
    {
        // not文字列作成
        $notStr = self::SeparateByNotOperator($str, false);
        $notStr = str_ireplace(' ', ',', $notStr);

        // notを除いた文字列
        $str = self::SeparateByNotOperator($str, true);

        // or文字列作成
        if (mb_strpos($str, '(') === false) {
            $orStr = '';
        } else {
            $orStr = mb_substr($str, ($iti=(mb_strpos($str, '(')+1)), (mb_strpos($str, ')'))-$iti);
            $orStr = str_ireplace(' OR ', ',', $orStr);
            $orStr = ($orStr === ' ') ? '':$orStr;
        }
        // and文字列作成
        $andStr = mb_substr($str, ($iti=(mb_strpos($str, '(')+1)), (mb_strpos($str, ')'))-$iti);
        if (mb_strpos($str, '(') === false) {
            $andStr = $str;
        } else {
            if (mb_strpos($str, '(') === 0) {
                $andStr = '';
            } else {
                $andStr = mb_substr($str, 0, mb_strpos($str, '(') -1);
            }
        }
        $andStr = str_ireplace(' ', ',', $andStr);
    }

    // 「A,B」「C,D,E」「F,G」を「A B OR C OR D OR E -F G」にする
    public static function csvToOperatorStr($and, $or, $not)
    {
        $andStr = str_replace(',', ' ', $and);
        $orStr = '('.str_replace(',', ' OR ', $or).')';
        $notStr = ' -'.str_replace(',', ' -', $not);

        $str = '';

        $str .= $andStr;

        if ($orStr !== '()') {
            if (!empty($str)) {
                $str .= ' ';
            }
            $str .= $orStr;
        }

        if ($notStr !== ' -') {
            $str .= $notStr;
        }
        return $str;
    }
}
