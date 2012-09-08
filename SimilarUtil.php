<?php

/**
 * 相似度计算工具类 SimilarityUtil
 * @author niuqi.neil  <niuqi227@gmail.com>
 * @version 1.0.0
 * 说明：基于分词结果的cos值和文本长度差值百分比，推算文本是否非常相似
 */
class SimilarityUtil {

    //相似度阈值 
    protected static $threshold = 0.93;
    //cos值所占相似度比重
    protected static $cosineWeight = 0.85;
    
    /**
     * segmentWords 分词函数，好的分词函数可使得计算结果更加精准
     * 这里仅把连续的数字、连续字母分为一词，汉字单字成词
     * @param string $content 需要被分词的输入字符串
     * @return array 分词结果数组,array（'词'=>出现次数）     
     */
    protected static function segmentWords($content) {
        $utf16 = mb_convert_encoding($content, "ucs-2", "utf-8");
        mb_internal_encoding('ucs-2');
        $utf16len = mb_strlen($utf16);
        $wordArray = array();
        $type = -1; // 0 数字 1字母 2汉字 3其他
        $buffer = '';
        for ($i = 0; $i < $utf16len; $i++) {
            $ch = mb_substr($utf16, $i, 1);
            $point = intval(bin2hex($ch), 16);
            if ($point > 47 && $point < 58) { // 数字
                $newType = 0;
            } else if (($point > 64 && $point < 91) || ($point > 96 && $point < 123)) { //字母
                $newType = 1;
            } else if ($point > 19968 && $point < 40870) {
                $newType = 2;
            } else {
                $newType = 3;
            }
            $originChar = mb_convert_encoding($ch, "utf-8", "ucs-2");

            if ($i == 0)  $type = $newType;
            
            if ($type == $newType) { // 类型没变
                if ($type < 2) { // 数字字母连词
                    $buffer.= $originChar;
                } else {
                    if ($newType == 2 && $buffer != '') { //汉字单个成词
                        if (isset($wordArray[$buffer]))
                            $wordArray[$buffer] +=1;
                        else
                            $wordArray[$buffer] = 1;
                    }
                    $buffer = $originChar;
                }
            }else { // 类型变化
                if ($newType < 3) { // 需要保存的类型
                    if (isset($wordArray[$buffer]))
                        $wordArray[$buffer] +=1;
                    else
                        $wordArray[$buffer] = 1;
                    $buffer = $originChar;
                }
                $type = $newType;
            }
        }
        if (isset($wordArray[$buffer]))
            $wordArray[$buffer] +=1;
        else
            $wordArray[$buffer] = 1;
        return $wordArray;
    }

    /**
     * calculateCosine 计算2个分词结果数组cos值
     * @param array $vector 分词结果数组1
     * @param array $compare 分词结果数组2
     * @return float cos值,范围[0,1],0不相关,1完全相关
     */    
    protected static function calculateCosine($vector, $compare) {
        $a = 0;
        $b = 0;
        $dot = 0;
        foreach ($vector as $key => $value) {
            $a += pow($value, 2);
            if (isset($compare[$key])) {
                $b += pow($compare[$key], 2);
                $dot += $value * $compare[$key];
                unset($compare[$key]);
            }
        }
        if ($dot == 0)
            return 0;
        foreach ($compare as $key => $value) {
            $b += pow($value, 2);
        }
//        echo "dot:$dot,a:$a,b:$b<br/>\n";
        $cosine = abs($dot / (sqrt($a) * sqrt($b)));
        return $cosine;
    }
    
    /**
     * calculateLengthSimilarity 计算2条字符串的长度相似度     
     * @param string $content 字符串1
     * @param string $another 字符串2
     * @return float 相似度值 (0,1] 1长度相同
     */    
     protected static function calculateLengthSimilarity($content, $another) {
        $contentLength = strlen($content);
        $historyLength = strlen($another);
        $divider = $contentLength > $historyLength ? $contentLength : $historyLength;                
        $lengthSimilarity = 1 - abs($contentLength - $historyLength) / $divider;
        return $lengthSimilarity;
    }
    

     /**
     * calculateSimilarity 计算2条字符串的相似度     
     * @param string $content 字符串1
     * @param string $another 字符串2
     * @return float 相似度值
     */    
     protected static function calculateSimilarity($content, $another) {
//        echo "content:$content<br/>\n";
//        echo "another:$another<br/>\n";
        $wordArray = self::segmentWords($content);
        $historyWordArray = self::segmentWords($another);
//        var_dump($wordArray);
//        var_dump($historyWordArray);
        $cosine = self::calculateCosine($wordArray, $historyWordArray);
        $lengthSimilarity =  self::calculateLengthSimilarity($content, $another);
        echo "cosine:$cosine , lengthSimilarity:$lengthSimilarity <br/>\n";
        $similarity = self::$cosineWeight * $cosine + (1 - self::$cosineWeight) * $lengthSimilarity;
        return $similarity;
    }
    
     /**
     * isSimilar 判断2条字符串是否相似     
     * @param string $content 字符串1
     * @param string $another 字符串2
     * @return boolean 相似true，不相似false
     */  
    public static function isSimilar($content, $another) {
        $similarity = self::calculateSimilarity($content, $another);
        echo "similarity:$similarity<br/>\n";
        return $similarity > self::$threshold;
    }

}

