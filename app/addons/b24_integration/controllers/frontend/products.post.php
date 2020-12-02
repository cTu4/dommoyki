<?php
if ($mode == 'view'){
    $product = fn_get_product_data($_REQUEST['product_id'], $auth);
    $data = fn_get_category_data($product['main_category'],'ru');
    //fn_print_r($data);
    Tygh::$app['view']->assign('category', $data);
}