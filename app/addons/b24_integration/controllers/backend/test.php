<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'delete_product') {
        $id_bitrix = $_REQUEST['data']['FIELDS']['ID'];
        $id_cscart = fn_get_product_id_cscart($id_bitrix);
        fn_delete_product($id_cscart);
    }
    if ($mode == 'add_product') {


        $id_bitrix = $_REQUEST['data']['FIELDS']['ID'];
        $data = fn_get_product_data_bitrix($id_bitrix);
        $id_cscart = fn_get_product_id_cscart($id_bitrix);


        $product_data_cscart = [
            "product"=>$data['NAME'],
            "category_ids"=>[$data['PROPERTY_117']['value']],
            "price"=>$data['PRICE'],
            "status"=>$data['ACTIVE']==='Y'?'A':'D',
            "full_description"=>$data['DESCRIPTION'],
            "timestamp"=>$data['TIMESTAMP_X'],
            "id_bitrix"=>$data['ID'],
            "image_link"=>$data['PROPERTY_113']['value'],
            "test_update"=>$data['PROPERTY_119']['value'],
            "color"=>$data['PROPERTY_121']['value'],
            "diagonal"=>$data['PROPERTY_123']['value'],
            "company_id"=>'3',
            'bitrix'=>true
        ];
        file_put_contents('parse_prices_log.txt',print_r($data,true));
        fn_update_product($product_data_cscart,0,'ru');
    }
    if ($mode == 'update') {

    }
    return array(CONTROLLER_STATUS_OK, "products.update");
}

$view = Registry::get('view');
if ($mode == 'update_properties_db') {
    $url = "https://b24-wdjw41.bitrix24.ru/rest/13/k4lzp37w80m063w6/crm.product.property.list";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Устанавливаем параметр, чтобы curl возвращал данные, вместо того, чтобы выводить их в браузер.
    //curl_setopt($ch, CURLOPT_POSTFIELDS,"id=113");
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    $data=json_decode($data, true)['result'];
    $arr=[];
    //$data = $data['result'];
    foreach ($data as $item){
        $name=$item['NAME'];
        $bitrix_key="PROPERTY_".$item['ID'];
        db_query('insert into ?:products_properties values(null,?s,?s)',$name,$bitrix_key);
    }

    $view->assign('test', $data);
    //$view->display('addons/my_module/views/test/test1.tpl');
}


