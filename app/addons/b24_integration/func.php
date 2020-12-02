<?php

function fn_get_product_id_cscart($XML_ID){
    return db_get_field("select product_id FROM ?:products WHERE XML_ID=?s", $XML_ID);
}
function fn_get_category_id_cscart($XML_ID){
    return db_get_field("select category_id FROM ?:categories WHERE XML_ID=?s", $XML_ID);
}
function fn_change_key($key,$new_key,&$arr){
    if(!array_key_exists($new_key,$arr)){
        $arr[$new_key]=$arr[$key];
        unset($arr[$key]);
        return true;
    }
    return false;
}
function fn_file_get_contents_curl_call($webhook,$method, $params) {
    $url = 'https://b24-nsn38a.bitrix24.ru/rest/7/'.$webhook."/".$method . '?'. http_build_query($params);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
//    curl_setopt($ch, CURLOPT_POST, 1);
//    curl_setopt($ch, CURLOPT_POSTFIELDS,
//        http_build_query($params));
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($data, true);
    return $data['result'];
}

function fn_get_product_data_bitrix($id){
    $params = [
        "id"=>$id
    ];
    $data = fn_file_get_contents_curl_call('iderm9n61stccn33','crm.product.get',$params);
    return $data;
}
function fn_add_product_bitrix($product_data,$id,$check){
    if(empty($product_data['bitrix'])){
        if($check){
            $data_add = [
                "fields"=>[
                    "NAME"=>$product_data['product'],
                    "CATALOG_ID"=>'25',   // $product_data['category_ids'][0],
                    'PRICE'=>$product_data['price'],
                    'ACTIVE'=>$product_data['status']==='A'?'Y':'N',
                    'DESCRIPTION'=>$product_data['full_description'],
                    'PROPERTY_113'=>[
                        'value'=>$product_data['image_link']
                    ],
                    'PROPERTY_117'=>[
                        'value'=>$product_data['category_ids'][0]
                    ]
                ]
            ];
            $url = "https://b24-wdjw41.bitrix24.ru/rest/13/qexfv43m4e830l09/crm.product.add?";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Устанавливаем параметр, чтобы curl возвращал данные, вместо того, чтобы выводить их в браузер.
            curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data_add));
            curl_setopt($ch, CURLOPT_URL, $url);
            $id_bitrix = curl_exec($ch);
            fn_print_r($id_bitrix);
            $id_bitrix=json_decode($id_bitrix,true)['result'];
            db_query('update ?:products set id_bitrix=?i where product_id=?i', $id_bitrix,$id);
            curl_close($ch);
        }
        else{
            $data_update=[
                "ID"=>$product_data['id_bitrix'],
                "fields"=>[
                    "NAME"=>$product_data['product'],
                    "CATALOG_ID"=>'25',   // $product_data['category_ids'][0],
                    'PRICE'=>$product_data['price'],
                    'ACTIVE'=>$product_data['status']==='A'?'Y':'N',
                    'DESCRIPTION'=>$product_data['full_description'],
                    'PROPERTY_113'=>[
                        'value'=>$product_data['image_link']
                    ],
                    'PROPERTY_117'=>[
                        'value'=>$product_data['category_ids'][0]
                    ]
                ]

            ];
            $url = "https://b24-wdjw41.bitrix24.ru/rest/13/kqix32mompstwdyz/crm.product.update?";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Устанавливаем параметр, чтобы curl возвращал данные, вместо того, чтобы выводить их в браузер.
            curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data_update));
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_exec($ch);
            curl_close($ch);
        }
    }


}

function fn_my_module_update_product_post(&$product_data, &$product_id, &$lang_code, &$create){
    fn_add_product_bitrix($product_data,$product_id,$create);
}

function fn_install_module(){
    db_query('CREATE TABLE `?:products_properties` (id int(3) AUTO_INCREMENT PRIMARY KEY,name varchar(40) not null,bitrix_key varchar(40) not null)');
    $url = "https://b24-wdjw41.bitrix24.ru/rest/13/k4lzp37w80m063w6/crm.product.property.list";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Устанавливаем параметр, чтобы curl возвращал данные, вместо того, чтобы выводить их в браузер.
    //curl_setopt($ch, CURLOPT_POSTFIELDS,"id=113");
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    $data=json_decode($data, true)['result'];
    // закинуть в массив необходимые значения и закинуть sql запросом ?e
    foreach ($data as $item){
        $name=$item['NAME'];
        $bitrix_key="PROPERTY_".$item['ID'];

        db_query('insert into ?:products_properties values(null,?s,?s)',$name,$bitrix_key);
        db_query('ALTER TABLE `cscart_products` ADD ?s VARCHAR(40)',$bitrix_key);
    }

}

function fn_delete_module(){
    $url = "https://b24-wdjw41.bitrix24.ru/rest/13/k4lzp37w80m063w6/crm.product.property.list";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Устанавливаем параметр, чтобы curl возвращал данные, вместо того, чтобы выводить их в браузер.
    //curl_setopt($ch, CURLOPT_POSTFIELDS,"id=113");
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    $data=json_decode($data, true)['result'];
    foreach ($data as $item){
        $name=$item['NAME'];
        $bitrix_key="PROPERTY_".$item['ID'];
        db_query('ALTER TABLE `?:products` drop ?s',$bitrix_key);
    }
    //db_query('ALTER TABLE `?:products` drop id_bitrix')
    db_query('DROP TABLE `?:products_properties`');
}