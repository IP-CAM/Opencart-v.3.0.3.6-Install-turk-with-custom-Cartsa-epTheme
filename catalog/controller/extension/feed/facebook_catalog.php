<?php
class ControllerExtensionFeedFacebookCatalog extends Controller
{
    public function index()
    {
        $prefix='feed_facebook_catalog_';
        if ($this->get($prefix.'status')) {
            //$file_location = $this->get($prefix.'file_location');
            //$base_taxonomy = $this->get($prefix.'base_taxonomy');
            //$shipping_price = (float)$this->get($prefix.'shipping');
            if (ini_get('date.timezone')) {//set server time, seems to be used by opencart
            	date_default_timezone_set(ini_get('date.timezone'));
            }
            $this->tax->setShippingAddress($this->get('config_country_id'), $this->get('config_zone_id'));//set taxes to store address
            //load setttings
            $save_to_file = (int)$this->get($prefix.'save_to_file');
            $gzip_compression = (int)$this->get($prefix.'compression');
            $size_setting = $this->get($prefix.'size_options');
            $color_setting = $this->get($prefix.'color_options');
            $pattern_setting = $this->get($prefix.'pattern_options');
            $material_setting = $this->get($prefix.'material_options');
            $clear_html = (int)$this->get($prefix.'clear_html');
            $use_meta = (int)$this->get($prefix.'use_meta');
            $google_pid1 = $this->get($prefix.'pid1');
            $google_option_ids = $this->get($prefix.'option_ids');
            $use_tax = (int)$this->get($prefix.'use_tax');
            $image_cache = $this->get($prefix.'image_cache');
            $disabled_products = $this->get($prefix.'disabled_products');
            $sold_out_products = $this->get($prefix.'sold_out_products');
            $language = $this->get($prefix.'language');
            $currency = $this->get($prefix.'currency');
            $gtin_source = $this->get($prefix.'gtin_source');
            $file_location = '';//here set feed file folder, if needed
            //load model
            $this->load->model('catalog/category');
            $this->load->model('catalog/product');
            $this->load->model('extension/feed/google_merchant_center');
            $this->load->model('tool/image');

            $store_id = $this->get('config_store_id');
            if (isset($_GET['store'])) {
                $store_id = (int)$_GET['store'];
            }
            $file_name_append = "_s".$store_id;
            $image_option_module = null;
            if ($this->model_extension_feed_google_merchant_center->checkTableExist('uber_options')) {
                $image_option_module = 'uber_options';
            } elseif ($this->model_extension_feed_google_merchant_center->checkTableExist('poip_option_image')) {
                $image_option_module = 'poip_option_image';
            }
            //$isDefaultLang = true;
            $lang_id="";
            $currency_code="";
            $product_url_parameter="";
            //load url parameters
            $use_additional_images = true;
            if (isset($_GET['additional_images']) && $_GET['additional_images'] == 0) {
                $use_additional_images = false;
            }
            $use_select_parameter = true;
            if (isset($_GET['select_parameter']) && $_GET['select_parameter'] == 0) {
                $use_select_parameter = false;
            }
            $use_language_parameter = true;
            if (isset($_GET['language_parameter']) && $_GET['language_parameter'] == 0) {
                $use_language_parameter = false;
            }
            $use_currency_parameter = true;
            if (isset($_GET['currency_parameter']) && $_GET['currency_parameter'] == 0) {
                $use_currency_parameter = false;
            }
            if (isset($_GET['tax'])) {//should be disabled in the USA,Canada and India
                $use_tax = (int)$_GET['tax'];
                $file_name_append.="_t".$_GET['tax'];
            }
            $use_tax_rate = 0;
            if (isset($_GET['tax_rate'])) {
                $use_tax_rate = (int)$_GET['tax_rate'];
                $file_name_append.="_tr".$_GET['tax_rate'];
            }
            $use_age_group = 0;
            if (isset($_GET['use_age_group'])) {
                $use_age_group = (int)$_GET['use_age_group'];
            }
            $use_gender = 0;
            if (isset($_GET['use_gender'])) {
                $use_gender = (int)$_GET['use_gender'];
            }
            if (isset($_GET['lang'])) {
                $lang_id = $this->model_extension_feed_google_merchant_center->getLangID($_GET['lang']);
                $file_name_append.="_l".$_GET['lang'];
                if ($use_language_parameter && $_GET['lang'] !== $this->get('config_language')) {
                    //$isDefaultLang=false;
                    $product_url_parameter.="&amp;language=".$_GET['lang'];
                }
            } else {
                if ($use_language_parameter && $language !== $this->get('config_language')) {
                    $product_url_parameter.="&amp;language=".$language;
                }
                $lang_id = $this->model_extension_feed_google_merchant_center->getLangID($language == '' ? $this->get('config_language') : $language);
            }
            $this->config->set('config_language_id', $lang_id);
            if (isset($_GET['curr'])) {
                $currency_code=$_GET['curr'];
                $file_name_append.="_c".$_GET['curr'];
                if ($use_currency_parameter && $_GET['curr'] !== $this->get('config_currency')) {
                    $product_url_parameter.="&amp;currency=".$_GET['curr'];
                }
            } else {
                if ($use_currency_parameter && $currency !== $this->get('config_currency')) {
                    $product_url_parameter.="&amp;currency=".$currency;
                }
                $currency_code = ($currency == '' ? $this->get('config_currency') : $currency);//USD
            }
            $shipping_price = null;
            if (isset($_GET['shipping_price'])) {//only url parameter, setting removed
                $shipping_price = (float)$_GET['shipping_price'];
                $file_name_append.="_sp".$_GET['shipping_price'];
            }
            $currency_value = $this->currency->getValue($currency_code);
            $decimal_place = (int)$this->currency->getDecimalPlace($currency_code);
            $black_product_id=array();
            $white_product_id=array();
            if (isset($_GET['include_product_id'])) {
                $white_product_id = explode(",", $_GET['include_product_id']);
                $file_name_append.="_ip".implode('-', $white_product_id);
            } elseif (isset($_GET['exclude_product_id'])) {
                $black_product_id = explode(",", $_GET['exclude_product_id']);
                $file_name_append.="_ep".implode('-', $black_product_id);
            }
            $black_category_id=array();
            $white_category_id=array();
            if (isset($_GET['include_category_id'])) {
                $white_category_id = explode(",", $_GET['include_category_id']);
                $file_name_append.="_ic".implode('-', $white_category_id);
            } elseif (isset($_GET['exclude_category_id'])) {
                $black_category_id = explode(",", $_GET['exclude_category_id']);
                $file_name_append.="_ec".implode('-', $black_category_id);
            }
            $black_manufacturer_id=array();
            $white_manufacturer_id=array();
            if (isset($_GET['include_manufacturer_id'])) {
                $white_manufacturer_id = explode(",", $_GET['include_manufacturer_id']);
                $file_name_append.="_im".implode('-', $white_manufacturer_id);
            } elseif (isset($_GET['exclude_manufacturer_id'])) {
                $black_manufacturer_id = explode(",", $_GET['exclude_manufacturer_id']);
                $file_name_append.="_em".implode('-', $black_manufacturer_id);
            }
            $black_keywords=array();
            $white_keywords=array();
            if (isset($_GET['include_keyword'])) {
                $white_keywords = preg_split("/[\s,;]+/", mb_strtolower($_GET['include_keyword']));
                $file_name_append.="_ik".implode('-', $white_keywords);
            } elseif (isset($_GET['exclude_keyword'])) {
                $black_keywords = preg_split("/[\s,;]+/", mb_strtolower($_GET['exclude_keyword']));
                $file_name_append.="_ek".implode('-', $black_keywords);
            }
            $price_range = array();
            if (isset($_GET['price_range'])) {
                $price_range = explode("-", $_GET['price_range']);
                $file_name_append.="_pr".$_GET['price_range'];
            }
            $free_products = 0;
            if (isset($_GET['include_free'])) {
                $free_products = (int)$_GET['include_free'];
                $file_name_append.="_if";
            }
            $base_url="";
            if (isset($this->ssl) || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
                $base_url = $this->get('config_ssl');
                $secure = true;
            } else {
                $base_url = $this->get('config_url');
                $secure = false;
            }
            if ($base_url === "") {
                $domainName = $_SERVER['HTTP_HOST'].'/';
                if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
                    $base_url = "https://".$domainName;
                    $secure = true;
                } else {
                    $base_url = "http://".$domainName;
                    $secure = false;
                }
            }
            $image_url = $base_url;
            if (isset($_GET['image_domain'])) {
                $image_url = $_GET['image_domain'];
                $file_name_append .= "_id".str_replace("/", "-", str_replace("http://", "", str_replace("https://", "", $image_url)));
            } else {
                $found = 0;
                $image_path = str_replace('catalog/controller/extension/feed', '', __DIR__, $found);
                if ($found > 0) {
                    $image_path = str_replace($image_path, '', DIR_IMAGE, $found);
                }
                if ($found == 0) {//not found, use default
                    $image_path = 'image/';
                } else {//try to fix possible errors
                    $image_path = str_replace('//', '/', $image_path);//remove double //
                    $image_path = substr($image_path, -1) != '/' ? $image_path.'/' : $image_path;//add / if missing
                }
                $image_url = $image_url.$image_path;
            }
            $start = 0;
            $limit = 1000;
            $redirect = 10;//set 1 to disable
            $product_count = 0;//onle used if $save_to_file = 1
            if ($save_to_file) {
                if (isset($_GET['redirect'])) {
                    $redirect = (int)$_GET['redirect'];
                    if ($redirect < 1) {
                        $redirect = 10;
                    }
                }
                $product_count = $this->model_extension_feed_google_merchant_center->getProductCount($store_id);
                $step = ceil(($product_count/$redirect)/10)*10;
                if ($limit < $step) {
                    $limit = $step;
                }
            }
            if (isset($_GET['start'])) {
                $start = (int)$_GET['start'];
            }
            if (isset($_GET['limit'])) {
                $limit = (int)$_GET['limit'];
                if ($limit < 1) {
                    $limit = 1000;
                }
            }
            $range_start = 0;
            $range_finish = $product_count;
            if (isset($_GET['range'])) {//process only products from-to
                $range = explode('-', $_GET['range']);
                $file_name_append .= "_r".$_GET['range'];
                $range_start = (int)reset($range)-1;
                if ($range_start < 0) {//fix for range from 0
                    $range_start = 0;
                }
                $range_finish = (int)end($range);
                if ($range_finish <= 0) {//fix for range from 0
                    $range_finish = $range_start+1;
                }
                if ($start < $range_start) {//adjust start, is first run
                    $start = $range_start;
                }
                if (($start + $limit) > $range_finish) {//last batch is limited to not get more products than requested
                    $limit = $range_finish - $start;
                }
            }
            if ($save_to_file) {
                $filetitle='/facebook_catalog'.$file_name_append.'.xml';
                $dirname = str_replace('catalog/', '', DIR_APPLICATION);
                $filepath = $dirname.$file_location.$filetitle;
                $filepath = str_replace('//', '/', $filepath);
            }
            $image_size = array();
            if ($image_cache !== "direct") {//no cache
                $image_size = explode('x', $image_cache);
            }
            if (count($image_size)!==2) {
                $image_size = array(600,600);
            }

            $shipping="";
            if ($shipping_price !== null) {//shipping flat rate, use merchant account instead
                $shipping_price = round($shipping_price*$currency_value, $decimal_place);
                $shipping.="<shipping><price>".$shipping_price. ' '.$currency_code."</price></shipping>";
            }

            $size_options = array();
            $size_attributes = array();
            if (is_array($size_setting)) {
                foreach ($size_setting as $value) {
                    if (substr($value, 0, 1)=='o') {
                        $size_options[]=substr($value, 1);
                    } else {
                        $size_attributes[]=substr($value, 1);
                    }
                }
            }

            $color_options = array();
            $color_attributes = array();
            if (is_array($color_setting)) {
                foreach ($color_setting as $value) {
                    if (substr($value, 0, 1)=='o') {
                        $color_options[]=substr($value, 1);
                    } else {
                        $color_attributes[]=substr($value, 1);
                    }
                }
            }

            $material_options = array();
            $material_attributes = array();
            if (is_array($material_setting)) {
                foreach ($material_setting as $value) {
                    if (substr($value, 0, 1)=='o') {
                        $material_options[]=substr($value, 1);
                    } else {
                        $material_attributes[]=substr($value, 1);
                    }
                }
            }

            $pattern_options = array();
            $pattern_attributes = array();
            if (is_array($pattern_setting)) {
                foreach ($pattern_setting as $value) {
                    if (substr($value, 0, 1)=='o') {
                        $pattern_options[]=substr($value, 1);
                    } else {
                        $pattern_attributes[]=substr($value, 1);
                    }
                }
            }

            $output = '';
            if (!$save_to_file || ($save_to_file && ($start === 0 || $start === $range_start))) {
                if ($save_to_file) {
                    file_put_contents($filepath.'.tmp', "");
                }
                $output  = '<?xml version="1.0" encoding="UTF-8" ?>';
                $output .= '<rss version="2.0">';
                $output .= '<channel>';
                $output .= '<link>'.$base_url.'</link>';
                $output .= '<title>'.$this->get('config_name').'</title>';
            }
            $products = $this->model_extension_feed_google_merchant_center->getProducts($lang_id, $store_id, $start, $limit);
            $product_batch_count = 0;
            while (count($products)>0) {
                foreach ($products as $product) {
                    $product_id = $product['product_id'];
                    $model = trim($this->model_extension_feed_google_merchant_center->decodeChars($product['model']));
                    $base_quantity = $product['quantity'];
                    $status = $product['status'];
                    //skip excluded products
                    if (in_array($product_id, $black_product_id)
                    || (empty($white_product_id) === false && in_array($product_id, $white_product_id) === false)
                    || ($sold_out_products==="skip products" && ($base_quantity <= 0 || $base_quantity < $product['minimum']))
                    || ($disabled_products==="skip products" && $status==0)
                    ) {
                        continue;
                    }
                    //skip exclude manufactures
                    if ((empty($white_manufacturer_id) === false && in_array($product['manufacturer_id'], $white_manufacturer_id) === false)
                    || in_array($product['manufacturer_id'], $black_manufacturer_id)) {
                        continue;
                    }
                    //skip meta keywords
                    $meta_keywords = preg_split("/[\s,;]+/", mb_strtolower($product['meta_keyword']));
                    $white_keyword_intersect = array_intersect($white_keywords, $meta_keywords);
                    $black_keyword_intersect = array_intersect($black_keywords, $meta_keywords);
                    if ((empty($white_keywords) === false && empty($white_keyword_intersect) === true)
                    || (empty($black_keywords) === false && empty($black_keyword_intersect) === false)) {
                        continue;
                    }
                    //skip excluded categories
                    $categories = $this->model_catalog_product->getCategories($product_id);
                    if (empty($white_category_id) === false || empty($black_category_id) === false) {
                        $category_continue = false;
                        $is_white_category = 2;
                        foreach ($categories as $category) {
                            if (in_array($category['category_id'], $black_category_id)) {
                                $category_continue = true;
                            }
                            if (empty($white_category_id) === false && $is_white_category != 1) {
                                if (in_array($category['category_id'], $white_category_id) === false) {
                                    $is_white_category = 0;
                                } else {
                                    $is_white_category = 1;
                                }
                            }
                        }
                        if ($category_continue || $is_white_category == 0) {
                            continue;
                        }
                    }
                    $product_type = array();
                    $category_id='';
                    $counter=0;
                    foreach ($categories as $category) {
                        $path = $this->model_extension_feed_google_merchant_center->getPath($category['category_id'], $lang_id, $store_id);
                        $count=1;
                        if ($path) {
                            $string = '';
                            foreach (explode('_', $path) as $path_id) {
                                $category_info = $this->model_extension_feed_google_merchant_center->getCategory($path_id, $lang_id, $store_id);
                                $count++;
                                if ($category_info) {
                                    if (!$string) {
                                        $string = trim(htmlspecialchars_decode($category_info['name'], ENT_COMPAT));
                                    } else {
                                        $string .= ' > ' . trim(htmlspecialchars_decode($category_info['name'], ENT_COMPAT));
                                    }
                                }
                            }
                            $string = str_replace(", ", " ", $string);
                            $string = str_replace(",", " ", $string);
                            array_unshift($product_type, $string);
                        }
                        if ($count>$counter) {
                            $counter = $count;
                            $category_id = $category['category_id'];
                        }
                    }
                    $google_product_category = '';
                    $category_id_google = $this->model_extension_feed_google_merchant_center->getTaxonomy($category_id);
                    $is_apparel = false;
                    if (isset($category_id_google['taxonomy_id']) && isset($category_id_google['name'])) {
                        $google_product_category_id = $category_id_google['taxonomy_id'];
                        $google_product_category = $category_id_google['name'];
                        $is_apparel = $this->model_extension_feed_google_merchant_center->isApparel($google_product_category_id);
                    } else {
                        $base_taxonomy = $this->get($prefix.'base_taxonomy');
                        if (!is_array($base_taxonomy)) {
                            if (substr($base_taxonomy, 0, 1) === '[') {
                                $base_taxonomy = json_decode($base_taxonomy);
                            } else {
                                $base_taxonomy = array('');
                            }
                        }
                        $google_product_category_id = reset($base_taxonomy);
                        if ((int)$google_product_category_id) {
                            $is_apparel = $this->model_extension_feed_google_merchant_center->isApparel($google_product_category_id);
                        }
                    }

                    $url = str_replace(" ", "%20", $this->url->link('product/product', 'product_id=' . $product_id, $secure));
                    if ($product_url_parameter !== "") {//add currency language parameters
                        $url.=(strpos($url, "index.php?") !== false ? $product_url_parameter : "?".substr($product_url_parameter, 5));
                    }
                    if (strpos($url, 'http') === false) {//add base url if link is only request
                        $url = $base_url.$url;
                    }
                    //title & descriptions
                    if ($use_meta && $product['meta_title'] != "") {
                        $title = $product['meta_title'];
                    } else {
                        $title = $product['name'];
                    }
                    $title = $this->model_extension_feed_google_merchant_center->decodeChars($title);
                    $title = $this->model_extension_feed_google_merchant_center->fixUpperCase($title);
                    $title = trim($title);
                    if ($use_meta && $product['meta_description'] != "") {
                        $description = $product['meta_description'];
                        if (strlen($description) <= 5) {
                            $description = $product['description'];
                        }
                    } else {
                        $description=$product['description'];
                        $desc_len = strlen($description);
                        if ($desc_len <= 5 && $product['meta_description']!="" && $desc_len < strlen($product['meta_description'])) {
                            $description = $product['meta_description'];
                        }
                    }
                    $description = $this->model_extension_feed_google_merchant_center->decodeChars($description);
                    if ($clear_html) {
                        $description= str_replace("
                        ", " ", str_replace("\n", " ", str_replace("\t", " ", str_replace("\r", " ", str_replace("\r\n", " ", $this->model_extension_feed_google_merchant_center->strip_html_tags($description))))));
                        while (strpos($description, "  ") !== false) {
                            $description=str_replace("  ", " ", $description);
                        }
                        $description=trim($description);
                        while ($this->model_extension_feed_google_merchant_center->startsWith($description, " ") || $this->model_extension_feed_google_merchant_center->endsWith($description, " ")) {
                            $description = $this->model_extension_feed_google_merchant_center->clearDescription($description, " ");
                        }
                    }
                    $description = mb_substr($description, 0, 5000, 'UTF-8');
                    $brand = trim($this->model_extension_feed_google_merchant_center->decodeChars($product['manufacturer']));
                    $condition = 'new';//possible add a feature
                    //images
                    $image_link = '';
                    $additional_image_link = array();
                    if ($product['image']) {
                        $image_link = $this->model_extension_feed_google_merchant_center->getImageUrl($product['image'], $image_size[0], $image_size[1], ($image_cache !== "direct"), $image_url);
                        if ($use_additional_images) {
                            $additional_images = $this->model_extension_feed_google_merchant_center->getImages($product_id, $product['image']);
                            $additional_images_counter = 0;
                            $max_image_count = 10;
                            foreach ($additional_images as $value) {
                                if ($max_image_count == 0) {
                                    break;
                                }
                                $max_image_count--;
                                $additional_images_url = $this->model_extension_feed_google_merchant_center->getImageUrl($value['image'], $image_size[0], $image_size[1], ($image_cache !== "direct"), $image_url);
                                $additional_images_counter += strlen($additional_images_url)+1;
                                if ($additional_images_counter <= 2000) {
                                    $additional_image_link[] = $additional_images_url;
                                }
                            }
                        }
                    }
                    //gtin & mpn
                    $mpn = $product['mpn'];
                    if ($mpn === '') {//if empty use model instead
                        $mpn = $model;
                    }
                    $gtin='';
                    if (array_key_exists($gtin_source, $product)) {
                        $gtin=$product[$gtin_source];
                    }
                    if ($gtin==='') {
                        $gtin=$product['ean'];
                    }
                    if ($gtin==='') {
                        $gtin=$product['upc'];
                    }
                    if ($gtin==='') {
                        $gtin=$product['jan'];
                    }
                    if ($gtin==='') {
                        $gtin=$product['isbn'];
                    }
                    //prices
                    $sale_price_effective_date = '';
                    $base_sale_price = '';
                    if ((float)$product['special']) {
                        $date_start = $product['date_start'];
                        $date_end =$product['date_end'];
                        if ($date_start=='0000-00-00') {
                            $date_start=date("Y-m-d");
                        }
                        if ($date_end!=='0000-00-00' && $date_start < $date_end) {
                            $sale_price_effective_date = date('Y-m-d\T00:00O',strtotime($date_start)).'/'.date('Y-m-d\T00:00O',strtotime($date_end));
                        }
                        $base_sale_price = $product['special'];
                    }
                    $base_price = ((float)$product['price'] > 0) ? $product['price'] : $this->model_extension_feed_google_merchant_center->getLowestPriceOption($product_id);
                    if ($free_products === 0 && $base_price <= 0) {
                        continue;
                    }
                    //product tab color, NO gender & NO age group
                    $product_tab_data = $this->model_extension_feed_google_merchant_center->getProductTabData($product_id, $lang_id);
                    $color_data = trim($product_tab_data['color']);
                    //attributes
                    $size_attribute = $this->model_extension_feed_google_merchant_center->getProductAttributes($product_id, $size_attributes, $lang_id);
                    $color_attribute = $this->model_extension_feed_google_merchant_center->getProductAttributes($product_id, $color_attributes, $lang_id);
                    $material_attribute = $this->model_extension_feed_google_merchant_center->getProductAttributes($product_id, $material_attributes, $lang_id);
                    $pattern_attribute = $this->model_extension_feed_google_merchant_center->getProductAttributes($product_id, $pattern_attributes, $lang_id);
                    //options
                    $options = $this->model_extension_feed_google_merchant_center->getOptions($product_id, array_merge($size_options, $color_options, $material_options, $pattern_options), $lang_id);
                    $item_group_id = $product_id;
                    if ($google_pid1 === 'model') {
                        $item_group_id = $model;
                    }
                    $weight = $product['weight'];
                    foreach ($options as $key => $option_group) {
                        $id = array();
                        if ($google_pid1 === 'product_id') {
                            $id[] = $product_id;
                        } else {
                            $id[] = $model;
                        }
                        $shipping_weight = $weight;
                        $price = $base_price;
                        $sale_price = $base_sale_price;
                        $option_price = 0;
                        $option_shipping_weight = 0;
                        $option_quantity = 0;//only not subtracting items
                        $option_subtract = 0;//will use 1 if any subtract is 1
                        $option_names = array();
                        $option_select = array();
                        $option_checkbox = array();
                        $option_image_link = null;
                        ksort($option_group);//order to option_value_id
                        foreach ($option_group as $option_value_id => $option) {
                            $option_shipping_weight += ($option['weight_prefix'] === '+' ? $option['weight'] : -$option['weight']);
                            if ($option_value_id == "") {//without options
                                $option_quantity = $base_quantity;
                                $option_subtract = 1;
                            } elseif ((int)$option['subtract'] === 0) {
                                $option_quantity = $base_quantity;
                            } elseif ($option_subtract == 0 || $option_quantity > $option['quantity']) {
                                $option_quantity = $option['quantity'];//use only lowest quantity if substract is 1, else option quantity is inaccurate
                                $option_subtract = 1;
                            }
                            $gtin = ((isset($option['upc']) && $option['upc'] != '') ? $option['upc'] : $gtin);//will propably not work, no setting in oc admin
                            if ($option['price_prefix'] === '+') {
                                $option_price = $option_price+$option['price'];
                            } elseif ($option['price_prefix'] === '-') {
                                $option_price = $option_price-$option['price'];
                            } elseif ($option['price'] > 0) {//for extensions
                                if ($sale_price > 0) {
                                    $sale_price = $option['price'];
                                } else {
                                    $price = $option['price'];
                                }
                            }
                            //$option_price = $option_price+($option['price_prefix'] === '-' ? -$option['price'] : $option['price']);
                            if ($option['name']!='' && $option_value_id!='') {
                                $option_names[$option['option_id']]=$option['name'];
                                if ($google_option_ids === 'option_id') {//id option append
                                    $id[]=$option_value_id;
                                } else {
                                    $id[]=$this->model_extension_feed_google_merchant_center->decodeChars($option['name']);
                                }
                                if ($option['type']=='select') {//url selection parameters
                                    $option_select[]=$option['product_option_value_id'];
                                } else {
                                    $option_checkbox[]=$option['product_option_value_id'];
                                }
                            }
                            //option images
                            if ($option_image_link == null) {
                                $option_image =
                                    isset($option['image']) && $option['image'] != ''
                                        ? $option['image']
                                        : ($image_option_module != null
                                            ? $this->model_extension_feed_google_merchant_center->getOptionImage(
                                                $image_option_module,
                                                $option['product_option_value_id']
                                            )
                                            : null
                                        );
                                if ($option_image != null) {
                                    $option_image_link = $this->model_extension_feed_google_merchant_center->getImageUrl(
                                        $option_image,
                                        $image_size[0],
                                        $image_size[1],
                                        ($image_cache !== "direct"),
                                        $image_url
                                    );
                                }
                            }
                        }
                        if ($option_price > 0) {//base price reset
                            $price = $product['price'];
                        }
                        $price += $option_price;
                        if ($free_products === 0 && $price <= 0) {
                            continue;
                        }
                        if (!empty($price_range)) {
                            $final_price = round($this->tax->calculate(($sale_price > 0 ? ($sale_price + $option_price) : $price), $product['tax_class_id'], $use_tax)*$currency_value, $decimal_place);
                            if ((count($price_range) == 2
                            && !($price_range[0] <= $final_price
                            && $price_range[1] >= $final_price))
                            || (count($price_range) == 1
                            && $price_range[0] > $final_price)
                            ) {
                                continue;
                            }
                        }
                        if ($sale_price > 0) {
                            $sale_price += $option_price;
                            $sale_price = round($this->tax->calculate($sale_price, $product['tax_class_id'], $use_tax)*$currency_value, $decimal_place).' '.$currency_code;
                        }
                        $price = round($this->tax->calculate($price, $product['tax_class_id'], $use_tax)*$currency_value, $decimal_place).' '.$currency_code;
                        $shipping_weight = $this->weight->format(($option_shipping_weight+$shipping_weight), $product['weight_class_id'], '.', '');
                        if (strpos($shipping_weight, 'g') === false && strpos($shipping_weight, 'lb') === false && strpos($shipping_weight, 'oz') === false) {
                            $shipping_weight = '0.00kg';
                        }

                        if ($sold_out_products==="skip products" && ($option_quantity <= 0 || $option_quantity < $product['minimum']) && $option_subtract == 1) {//skip product with 0 quantity and enabled subtract
                            continue;
                        }

                        $availability = 'in stock';//default
                        $quantity = $base_quantity;//not really used, but set it to get the vaule in the feed if required
                        if ($status == 0) {//disabled
                            $availability = $disabled_products;
                        } elseif ($quantity == 0) {//if main quantity is 0, you can't order, so always sold out
                            $availability = $sold_out_products;
                        } elseif ($option_subtract == 1) {//option quantity might be inacurate, but use it
                            if ($option_quantity == 0) {
                                $availability = $sold_out_products;
                            }
                            $quantity = $option_quantity;
                        }
                        if ($quantity < 0) {
                            $quantity = 0;
                        }
                        $product_tab_data = $this->model_extension_feed_google_merchant_center->getProductTabData($product_id, $lang_id);
                        $age_group = ($is_apparel || $use_age_group ? $product_tab_data['age_group'] : '');
                        $gender = ($is_apparel || $use_gender ? $product_tab_data['gender'] : '');
                        $color = array();
                        foreach ($color_options as $value) {
                            if (array_key_exists($value, $option_names)) {
                                $color[] = $option_names[$value];
                            }
                        }
                        $color = array_merge($color, $color_attribute);
                        if ($color_data !== '') {//empty($color) &&
                            $color[]=$color_data;
                        }
                        $size = array();
                        foreach ($size_options as $value) {
                            if (array_key_exists($value, $option_names)) {
                                $size[] = $option_names[$value];
                            }
                        }
                        $size = array_merge($size, $size_attribute);
                        $material = array();
                        foreach ($material_options as $value) {
                            if (array_key_exists($value, $option_names)) {
                                $material[] = $option_names[$value];
                            }
                        }
                        $material = array_merge($material, $material_attribute);
                        $pattern = array();
                        foreach ($pattern_options as $value) {
                            if (array_key_exists($value, $option_names)) {
                                $pattern[] = $option_names[$value];
                            }
                        }
                        $pattern = array_merge($pattern, $pattern_attribute);

                        $link = $url;
                        if ($use_select_parameter) {
                            if (!empty($option_select)) {
                                $link.=(strpos($link, "?") !== false ? '&amp;select='.implode(',',$option_select) : '?select='.implode(',',$option_select));
                            }
                            if (!empty($option_checkbox)) {
                                $link.=(strpos($link, "?") !== false ? '&amp;checkbox='.implode(',',$option_checkbox) : '?checkbox='.implode(',',$option_checkbox));
                            }
                        }
                        //fill data
                        $output .= '
<item>';
                        $output .= '<id><![CDATA['.implode('-',$id).']]></id>';
                        $output .= '<item_group_id><![CDATA['.$item_group_id.']]></item_group_id>';
                        $output .= '<link>'.$link.'</link>';
                        $output .= '<title><![CDATA['.$title.']]></title>';
                        $output .= '<description><![CDATA['.$description.']]></description>';
                        $output .= '<brand><![CDATA['.$brand.']]></brand>';
                        $output .= '<gtin>'.$gtin.'</gtin>';
                        $output .= '<mpn><![CDATA['.$mpn.']]></mpn>';
                        $output .= '<image_link>'.($option_image_link == null ? $image_link : $option_image_link).'</image_link>';
                        $output .= '<additional_image_link>'.implode(',',$additional_image_link).'</additional_image_link>';
                        $output .= '<price>'.$price.'</price>';
                        $output .= '<sale_price>'.$sale_price.'</sale_price>';
                        $output .= '<sale_price_effective_date>'.$sale_price_effective_date.'</sale_price_effective_date>';
                        $output .= '<product_type><![CDATA['.implode(',',$product_type).']]></product_type>';
                        $output .= '<google_product_category><![CDATA['.$google_product_category.']]></google_product_category>';
                        $output .= '<availability>'.$availability.'</availability>';
                        $output .= '<inventory>'.$quantity.'</inventory>';
                        $output .= $shipping;
                        $output .= '<shipping_weight>'.$shipping_weight.'</shipping_weight>';
                        $output .= '<condition>'.$condition.'</condition>';
                        $output .= ($age_group === '') ? '' : '<age_group>'.$age_group.'</age_group>';
                        $output .= ($gender === '') ? '' : '<gender>'.$gender.'</gender>';
                        $output .= '<size><![CDATA['.implode(',', $size).']]></size>';
                        $output .= '<color><![CDATA['.implode(',', $color).']]></color>';
                        $output .= '<pattern><![CDATA['.implode(',', $pattern).']]></pattern>';
                        $output .= '<material><![CDATA['.implode(',', $material).']]></material>';
                        $output .= '</item>';
                    }
                }

                $start = $start + $limit;
                if ($range_finish > 0 && ($start + $limit) > $range_finish) {//last batch is limited, re-adjust
                    $limit = $range_finish - $start;
                }
                if (!$save_to_file) {
                    if ($range_finish == 0 || $start < $range_finish) {//range not used or finish not reached
                        $products = $this->model_extension_feed_google_merchant_center->getProducts($lang_id, $store_id, $start, $limit);
                    } else {//finish reached
                        $products = array();
                    }
                } else {
                    $product_batch_count = count($products);
                    $products = array();
                    file_put_contents($filepath.'.tmp', $output, FILE_APPEND | LOCK_EX);
                    $output = "";
                }

            }
            if (!$save_to_file || ($save_to_file && ($product_count <= $start || $range_finish <= $start || $product_batch_count === 0))) {
                $output .= '</channel>';
                $output .= '</rss>';
            }
            if ($save_to_file) {
                file_put_contents($filepath.'.tmp', $output, FILE_APPEND | LOCK_EX);
                if ($product_count <= $start || $range_finish <= $start || $product_batch_count === 0) {//finish processing
                    rename($filepath.'.tmp', $filepath);
                    $gzip_extension = '';
                    if ($gzip_compression) {
                        $data = implode('', file($filepath));
                        $gzip_data = gzencode($data, 5);
                        if ($gzip_data !== false && $gzip_data != $data) {
                            $gzip_open = fopen($filepath.'.gz', 'w');
                            fwrite($gzip_open, $gzip_data);
                            fclose($gzip_open);
                            unlink($filepath);
                            $gzip_extension = '.gz';
                        }
                    }
                    $file_url = $file_location.$filetitle.$gzip_extension;
                    if (substr($file_url, 0, 1) === "/" && substr($base_url, -1) === "/") {
                        $file_url = substr($file_url, 1);
                    }
                    $file_url = $base_url.$file_url;
                    header('Cache-Control: no-store');
                    header('Location: ' . $file_url, true, 302);

                } else {//redirect back to processing next batch
                    $url = $_SERVER['REQUEST_URI'];
                    $url = $this->model_extension_feed_google_merchant_center->setParameterURL($url, 'start', $start);
                    $url = $this->model_extension_feed_google_merchant_center->setParameterURL($url, 'limit', $limit);
                    $url = ($secure ? "https" : "http")."://".$_SERVER['HTTP_HOST'].$url;
                    header('Cache-Control: no-store');
                    header('Location: '.$url, true, 302);
                }
                die();
            } else {
                if ($gzip_compression && !ini_get('zlib.output_compression') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
                    $output = gzencode($output, 5);
                    header("Content-Encoding: gzip");
                }
                header('Cache-Control: no-store');
                header('Content-Type: text/xml; charset=UTF-8');
                header('Content-Disposition: inline; filename="facebook_catalog'.$file_name_append.'.xml"');
                print($output);
                exit(0);
            }
        } else {
            $this->response->setOutput('<head><meta name="robots" content="noindex"></head><body>Disabled feed.</body>');
        }
    }

    private function get($config, $default = null)
    {
        $value = $this->config->get($config);
        return ($value === null ? $default : $value);
    }
}
