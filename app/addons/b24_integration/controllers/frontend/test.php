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

        $data_keys = B24_integration['product'];
        $id_bitrix = $_REQUEST['data']['FIELDS']['ID'];
        $data = fn_get_product_data_bitrix($id_bitrix);
        $params=[
            "select" => ["ID"],
            "filter" => [
                "NAME"=>"Витрины"
            ]
        ];


        $sale_destination = fn_file_get_contents_curl_call('iderm9n61stccn33','crm.product.property.list',$params)[0];
        $b24_product_destinations = $data['PROPERTY_'.$sale_destination['ID']];
        $check_destination = false;
        $key = 0;
        while (!$check_destination){
            $id_variant = $b24_product_destinations[$key]['value'];
            if($sale_destination['VALUES'][$id_variant]['XML_ID']==='DOMMOYKI' || $sale_destination['VALUES'][$id_variant]['XML_ID']==='ALL'){
                $check_destination = true;
            }
        }
        if($check_destination){
            $bitrix_category_id = $data['SECTION_ID'];
            $bitrix_product_id =  $data['XML_ID'];
            $XML_ID_CATEGORY = fn_file_get_contents_curl_call('iderm9n61stccn33','crm.productsection.get',["id"=>$bitrix_category_id])['XML_ID'];
            $category_id_cscart = fn_get_category_id_cscart($XML_ID_CATEGORY);
            $XML_ID_PRODUCT = fn_file_get_contents_curl_call('iderm9n61stccn33','crm.product.get',["id"=>$bitrix_product_id])['XML_ID'];

            $product_id_cscart = fn_get_product_id_cscart($XML_ID_PRODUCT);

            $product_data_cscart = [
                "product"=>$data[$data_keys['product']],
                "category_ids"=>[$category_id_cscart],
                "price"=>$data[$data_keys['price']],
                "status"=>$data[$data_keys['status']]==='Y'?'A':'D',
                "full_description"=>$data[$data_keys['full_description']],
                "timestamp"=>$data[$data_keys['timestamp']],
                "XML_ID"=>$data[$data_keys['XML_ID']],
                "company_id"=>'3'
            ];
            file_put_contents('request.txt',print_r($data,true));

            fn_update_product($product_data_cscart,empty($product_id_cscart) ? 0 :  $product_id_cscart,'ru');
        }

    }

    if ($mode == 'update') {
        $data = fn_get_product_data_bitrix(31245);
        die;

    }
    return array(CONTROLLER_STATUS_OK, "products.update");
}

if ($mode == 'test') {
    //fn_update_product_features_value(13,[12=>99],[12=>["variant" => 99]],DESCR_SL);
    $params=[
        "select" => ["ID"],
        "filter" => [
            "NAME"=>"Витрины"
        ]
    ];
    $sale_destination = fn_file_get_contents_curl_call('iderm9n61stccn33','crm.product.property.list',$params);
    echo '<pre>';
    print_r($sale_destination);
    echo '</pre>';
}

if ($mode == 'update_features') {
    $data_keys = B24_integration['feature'];
    $features = fn_file_get_contents_curl_call('iderm9n61stccn33','crm.product.property.list',[]);

    foreach ($features as $keyFeature => $feature){
        echo '<pre>';
        print_r($feature);
        echo '</pre>';
        $feature_cscart = [
            "description" =>            $feature[$data_keys['description']],
            "company_id" =>                1,
            "purpose" =>                "find_products",
            "feature_style"=>           "text",
            "filter_style" =>           "checkbox",
            "feature_type" =>           "S",
            "display_on_product" =>     "Y",
            "display_on_catalog" =>     "N",
            "display_on_header" =>      "N",
            "XML_ID"=>                  $feature['XML_ID']
        ];
        $feature_id = fn_update_product_feature($feature_cscart, 0, 'ru');
        if(!empty($feature['VALUES'])){
            $position = 1;
            foreach ($feature['VALUES'] as $variant){
                $variant_cscart = [
                    "variant" => $variant['VALUE'],
                    "position" => $position,
                    "XML_ID" => $variant['XML_ID']
                ];
                fn_add_feature_variant($feature_id,$variant_cscart);
                $position++;
            }
        }

    }




}


if ($mode == 'update_categories') {
    $categories = fn_file_get_contents_curl_call('iderm9n61stccn33','crm.productsection.list',[]);
    foreach ($categories as $key => $category){
        fn_change_key($key,$category['ID'],$categories);
    }
    foreach ($categories as $key => $category){
        $category_cscart = db_get_field("SELECT category_id FROM ?:categories WHERE XML_ID = ?s", $category['XML_ID']);
        if(empty($category_cscart)){
            $id_parent = 0;
            if(!empty($category['SECTION_ID'])){
//            $params = [
//                "select" => ["XML_ID"],
//                "filter"=>[
//                    "SECTION_ID" => $category['SECTION_ID']
//                ]
//            ];
//            $parent_XML_ID = fn_file_get_contents_curl_call('iderm9n61stccn33','crm.productsection.list',$params)[0]["XML_ID"];
                $parent_XML_ID = $categories[$category['SECTION_ID']]['XML_ID'];
                $id_parent = db_get_field("SELECT category_id FROM ?:categories WHERE XML_ID = ?s", $parent_XML_ID);
            }
            $category_cscart = [
                "category" => $category['NAME'],
                "XML_ID" => $category['XML_ID'],
                "parent_id" => $id_parent,
                "product_details_view" => 'default',
                "timestamp" => date('d/m/y')
            ];

            $id_category = fn_update_category($category_cscart,0, DESCR_SL);
            echo '<pre>';
            print_r(db_get_row("select * FROM ?:categories WHERE category_id = ?i",$id_category));
            echo '</pre>';
        }

    }

die;
}

if ($mode == 'update_prop') {
    $properties = fn_file_get_contents_curl_call('iderm9n61stccn33','crm.product.property.list',[]);
    foreach ($properties as $prop){

    }
    echo '<pre>';
    print_r($properties);
    echo '</pre>';
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


