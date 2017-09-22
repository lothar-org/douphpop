<?php
// 图片缩略图统一配置
// 顺序 ：首页、列表页、详情页(product_category除外)
// product 271*147 375*216
// research 283*160

if ($lang_type==2) {
    $thumb_conf = array(
        'apply' => array(array(284,284)),
        'apply_category' => array(array(910,512)),
        'article' => array(array(283,160),array(70,70)),
        'article_category' => array(array(910,512)),
        'job' => array(array(910,512)),
        'job_category' => array(array(910,512)),
        'page' => array(array(167,167),array(910,512)),
        'product' => array(array(241,119),array(298,298)),
        'product_category' => array(array(284,284),array(205,205)),
        'research' => array(array(291,165)),
        'research_category' => array(array(910,512)),
        'show' => array(array(910,513)),
        'user' => array(array(123,123)),
    );
} else {
    $thumb_conf = array(
        'apply' => array(array(370,370)),
        'apply_category' => array(array(910,513)),
        'article' => array(array(345,195),array(70,70)),
        'article_category' => array(array(910,513)),
        'job' => array(array(910,513)),
        'job_category' => array(array(910,513)),
        'page' => array(array(167,167)),
        'product' => array(array(285,153),array(305,305)),
        'product_category' => array(array(275,275),array(360,360)),
        'research' => array(array(384,217)),
        'research_category' => array(array(910,513)),
        'show' => array(array(910,513)),
        'user' => array(array(123,123)),
    );
}
if (CMOD) {
    // 主图设置
    $_CFG['thumb_width'] = $thumb_conf[CMOD][0][0];
    $_CFG['thumb_height'] = $thumb_conf[CMOD][0][1];

    // 第二张 缩略图未能实现
    $thumb['w2'] = $thumb_conf[CMOD][1][0];
    $thumb['h2'] = $thumb_conf[CMOD][1][1];

    // echo $thumb_conf[CMOD][0][0];
    // echo ",";
    // echo $thumb_conf[CMOD][0][1];
}


// 含比例
/*if ($lang_type==2) {
    $thumb_conf = array(
        'apply' => array(284*284(1)),
        'apply_category' => array(910*512(1.78)),
        'article' => array(283*160(1.77),70*70(1)),
        'article_category' => array(910*512(1.78)),
        'job' => array(910*512(1.78)),
        'job_category' => array(910*512(1.78)),
        'page' => array(167*167(1),910*512(1.78)),
        'product' => array(241*119(2.03),298*298(1)),
        'product_category' => array(910*513(1.77),284*284(1),205*205(1)),
        'research' => array(291*165(1.76)),
        'research_category' => array(910*512(1.78)),
        'show' => array(1847*848(2.18)),
        'user' => array(123*123(1)),
    );
} else {
    $thumb_conf = array(
        'apply' => array(370*370),
        'apply_category' => array(910*513(1.77)),
        'article' => array(345*195(1.77),70*70,),
        'article_category' => array(910*513(1.77)),
        'job' => array(910*513(1.77)),
        'job_category' => array(910*513(1.77)),
        'page' => array(167*167,),
        'product' => array(285*153(1.86)),
        'product_category' => array(910*513(1.77),275*275,360*360),
        'research' => array(384*217(1.77)),
        'research_category' => array(910*513(1.77)),
        'show' => array(1847*848(2.18)),
        'user' => array(123*123),
    );
}*/
?>