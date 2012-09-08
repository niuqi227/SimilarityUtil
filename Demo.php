<?php

include('SimilarUtil.php');

//测试例子
function demo(){
    //当前发布信息内容
    $content = '大众 桑塔纳';
    //今天已发布信息内容数组
    $historyArray = array(
        '大众朗逸2008款 1.6 自动品悠版',
        '出售大众 途锐 2006款进口(进口)3.2LV6',
        '大众 桑塔纳志俊',
        '大众 桑塔纳3000',
        '大众桑塔纳',
    );
    foreach ($historyArray as $history) {
        $isSimilar = SimilarityUtil::isSimilar($content, $history);
        if ($isSimilar)
            break;
    }
    if ($isSimilar)
        echo '今天已发布过类似条目';
    else
        echo '今天没有发布相似条目';
}

demo();
?>
