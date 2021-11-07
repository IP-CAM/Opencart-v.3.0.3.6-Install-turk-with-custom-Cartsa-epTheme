<?php
class ControllerExtensionFeedCustom extends Controller
{
    public function index()
    {
        $prefix='feed_custom_';
        if ($this->get($prefix.'status')) {
            if (ini_get('date.timezone')) {//set server time, seems to be used by opencart
            	date_default_timezone_set(ini_get('date.timezone'));
            }
            $this->tax->setShippingAddress($this->get('config_country_id'), $this->get('config_zone_id'));//set taxes to store address
            //$file_location = $this->get($prefix.'file_location');
            //load setttings
            $save_to_file = (int)$this->get($prefix.'save_to_file');
            $gzip_compression = (int)$this->get($prefix.'compression');
            $selected_options = $this->get($prefix.'options');
            $shipping_carriers = $this->get($prefix.'carriers');
            $country_default = (int)$this->get($prefix.'shop_address');
            $clear_html = (int)$this->get($prefix.'clear_html');
            $use_meta = (int)$this->get($prefix.'use_meta');
            $image_cache = $this->get($prefix.'image_cache');
            $in_stock_products = $this->get($prefix.'in_stock_products');
            $disabled_products = $this->get($prefix.'disabled_products');
            $sold_out_products = $this->get($prefix.'sold_out_products');
            $language = $this->get($prefix.'language');
            $currency = $this->get($prefix.'currency');
            $base_taxonomy = $this->get('feed_google_merchant_center_base_taxonomy');
            $template_location = trim($this->get($prefix.'template_location'), ' '.DIRECTORY_SEPARATOR);
            if (empty($template_location)) {
                $template_location = "feed_templates";
            }
            $file_location = '';//here set feed file folder, if needed
            if (!is_array($base_taxonomy)) {
                if (substr($base_taxonomy, 0, 1) === '[') {
                    $base_taxonomy = json_decode($base_taxonomy);
                } else {
                    $base_taxonomy = array('');
                }
            }
            //load model
            $this->load->model('catalog/category');
            $this->load->model('catalog/product');
            $this->load->model('extension/feed/google_merchant_center');
            $this->load->model('tool/image');
            $this->load->model('localisation/country');
            $this->load->model('localisation/zone');
            $this->load->model('catalog/review');
            $store_id = $this->get('config_store_id');
            if (isset($_GET['store'])) {
                $store_id = (int)$_GET['store'];
            }
            $file_name_append = "s".$store_id;
            $image_option_module = null;
            if ($this->model_extension_feed_google_merchant_center->checkTableExist('uber_options')) {
                $image_option_module = 'uber_options';
            } elseif ($this->model_extension_feed_google_merchant_center->checkTableExist('poip_option_image')) {
                $image_option_module = 'poip_option_image';
            }
            $isDefaultLang = true;
            $lang_id="";
            $currency_code="";
            //load url parameters
            $template_name = '';
            if (isset($_GET['template']) && $_GET['template'] != '') {
                $template_name = $_GET['template'];
            }
            $use_additional_images = 10;
            if (isset($_GET['additional_images'])) {
                $use_additional_images = (int)$_GET['additional_images'];
                $file_name_append.="_ei".$use_additional_images;
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
            if (isset($_GET['availability_in_stock'])) {
                $in_stock_products = $_GET['availability_in_stock'];
                $file_name_append.="_ai".preg_replace('/[^a-zA-Z0-9]/', '', $in_stock_products);
            }
            if (isset($_GET['availability_out_of_stock'])) {
                $sold_out_products = $_GET['availability_out_of_stock'];
                $file_name_append.="_ao".preg_replace('/[^a-zA-Z0-9]/', '', $sold_out_products);
            }
            if (isset($_GET['availability_disabled'])) {
                $disabled_products = $_GET['availability_disabled'];
                $file_name_append.="_ad".preg_replace('/[^a-zA-Z0-9]/', '', $disabled_products);
            }
            if (isset($_GET['shipping'])) {
                $shipping_carriers = $_GET['shipping'];
                $file_name_append.="_sc".$shipping_carriers;
                $shipping_carriers = explode(',', $shipping_carriers);
            }
            if (isset($_GET['options'])) {
                $options_parameter = $_GET['options'];
                $file_name_append.="_o".$options_parameter;
                $selected_options = explode(',', $options_parameter);
            }
            $string_encode = 0;
            if (isset($_GET['html_entities_text'])) {
                $string_encode = $_GET['html_entities_text'];
            }
            $url_encode = 0;
            if (isset($_GET['html_entities_url'])) {
                $url_encode = $_GET['html_entities_url'];
            }
            $remove_char = '';
            if (isset($_GET['remove_char'])) {
                $remove_char = $_GET['remove_char'];
                $remove_char = ($remove_char !=='' ? '/['.htmlspecialchars_decode($remove_char).']/' : '');
            }
            $countries = array();
            $main_country = array();
            if (isset($_GET['country_code'])) {//url parameter - selected countries/all zones
                $country_code = explode(',', $_GET['country_code']);
                $file_name_append.="_cc".implode('-',$country_code);
                $countries = $this->model_extension_feed_google_merchant_center->getCountriesByIso($country_code);
                $first_country = reset($countries);
                $main_country[$first_country['country']['country_id']] = $first_country;
            } else {
                $country_id = (int)$this->get('config_country_id');
                $zone_id = (int)$this->get('config_zone_id');
                $main_country[$country_id]['country']=$this->model_localisation_country->getCountry($country_id);
                $zone_info = $this->model_localisation_zone->getZone($zone_id);
                if (!array_key_exists('code', $zone_info)) {
                    $zone_info = array(
                        'zone_id' => $zone_id,
                        'country_id' => $country_id,
                        'code' => '',
                        'name' => '',
                        'status' => 1
                    );
                }
                $main_country[$country_id]['zones'][$zone_id]=$zone_info;
                $main_country[$country_id]['zones'][$zone_id]['iso_code_2'] = $main_country[$country_id]['country']['iso_code_2'];
                $main_country[$country_id]['zones'][$zone_id]['iso_code_3'] = $main_country[$country_id]['country']['iso_code_3'];
                if ($country_default) {//shop location - single country/single zone
                    $countries = $main_country;
                } else {
                    $countries = $this->model_extension_feed_google_merchant_center->getCountriesByIso();
                }
            }
            $country_list = array();
            $country_iso = $this->model_localisation_country->getCountry((int)$this->get('config_country_id'));
            $country_iso = $country_iso['iso_code_2'];
            //$country_iso = strtoupper($country_iso);
            if (isset($_GET['country'])) {
                $country = $_GET['country'];
                if (!is_array($country)) {
                    $country = explode(',', $country);
                }
                $file_name_append.="_cl".str_replace(':', '', str_replace(',', '', implode('-', $country)));
                foreach ($country as $value) {
                    $country_data = explode(":", $value);
                    $country_lang = array();
                    $country_curr = array();
                    if (count($country_data) == 3) {//full data set with language and currency
                        $country_iso = $country_data[0];
                        foreach (explode(',', $country_data[1]) as $lang_iso) {
                            $country_lang[$lang_iso]=$this->model_extension_feed_google_merchant_center->getLangID($lang_iso);
                        }
                        foreach (explode(',', $country_data[2]) as $curr_iso) {
                            $country_curr[$curr_iso]['currency_value']=$this->currency->getValue($curr_iso);
                            $country_curr[$curr_iso]['decimal_place']=(int)$this->currency->getDecimalPlace($curr_iso);
                        }
                    } else {//no language and currency
                        $country_iso = reset($country_data);
                    }
                    //$country_iso = strtoupper($country_iso);
                    $country_list[$country_iso]['languages']=$country_lang;
                    $country_list[$country_iso]['currencies']=$country_curr;
                }
            } else {
                $country_list[$country_iso]['languages']=array();
                $country_list[$country_iso]['currencies']=array();
            }

            if (isset($_GET['lang'])) {
                $languages = explode(',', $_GET['lang']);
                foreach ($languages as $lang_iso) {
                    foreach (array_keys($country_list) as $country_iso) {
                        $country_list[$country_iso]['languages'][$lang_iso]=$this->model_extension_feed_google_merchant_center->getLangID($lang_iso);
                    }
                }
                $file_name_append.="_l".implode('-', $languages);
            } else {
                foreach (array_keys($country_list) as $country_iso) {
                    if (empty($country_list[$country_iso]['languages'])) {
                        $lang_iso = ($language == '' ? $this->get('config_language') : $language);
                        $lang_id = $this->model_extension_feed_google_merchant_center->getLangID($lang_iso);
                        $country_list[$country_iso]['languages'][$lang_iso]=$lang_id;
                    }
                }
            }

            if (isset($_GET['curr'])) {
                $currencies = explode(',', $_GET['curr']);
                foreach ($currencies as $curr_iso) {
                    foreach (array_keys($country_list) as $country_iso) {
                        $country_list[$country_iso]['currencies'][$curr_iso]['currency_value']=$this->currency->getValue($curr_iso);
                        $country_list[$country_iso]['currencies'][$curr_iso]['decimal_place']=(int)$this->currency->getDecimalPlace($curr_iso);
                    }
                }
                $file_name_append.="_c".implode('-', $currencies);
            } else {
                foreach (array_keys($country_list) as $country_iso) {
                    if (empty($country_list[$country_iso]['currencies'])) {
                        $curr_iso = ($currency == '' ? $this->get('config_currency') : $currency);//USD
                        $country_list[$country_iso]['currencies'][$curr_iso]['currency_value']=$this->currency->getValue($curr_iso);
                        $country_list[$country_iso]['currencies'][$curr_iso]['decimal_place']=(int)$this->currency->getDecimalPlace($curr_iso);
                    }
                }
            }
            $black_product_id = array();
            $white_product_id = array();
            if (isset($_GET['include_product_id'])) {
                $white_product_id = explode(",", $_GET['include_product_id']);
                $file_name_append.="_ip".implode('-', $white_product_id);
            } elseif (isset($_GET['exclude_product_id'])) {
                $black_product_id = explode(",", $_GET['exclude_product_id']);
                $file_name_append.="_ep".implode('-', $black_product_id);
            }
            $black_category_id = array();
            $white_category_id = array();
            if (isset($_GET['include_category_id'])) {
                $white_category_id = explode(",", $_GET['include_category_id']);
                $file_name_append.="_ic".implode('-', $white_category_id);
            } elseif (isset($_GET['exclude_category_id'])) {
                $black_category_id = explode(",", $_GET['exclude_category_id']);
                $file_name_append.="_ec".implode('-', $black_category_id);
            }
            $black_manufacturer_id = array();
            $white_manufacturer_id = array();
            if (isset($_GET['include_manufacturer_id'])) {
                $white_manufacturer_id = explode(",", $_GET['include_manufacturer_id']);
                $file_name_append.="_im".implode('-', $white_manufacturer_id);
            } elseif (isset($_GET['exclude_manufacturer_id'])) {
                $black_manufacturer_id = explode(",", $_GET['exclude_manufacturer_id']);
                $file_name_append.="_em".implode('-', $black_manufacturer_id);
            }
            $black_keywords = array();
            $white_keywords = array();
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
            $i_start = 0;
            $i_limit = 1000;
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
                if ($i_limit < $step) {
                    $i_limit = $step;
                }
            }
            if (isset($_GET['start'])) {
                $i_start = (int)$_GET['start'];
            }
            if (isset($_GET['limit'])) {
                $i_limit = (int)$_GET['limit'];
                if ($i_limit < 1) {
                    $i_limit = 1000;
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
                if ($i_start < $range_start) {//adjust start, is first run
                    $i_start = $range_start;
                }
                if (($i_start + $i_limit) > $range_finish) {//last batch is limited to not get more products than requested
                    $i_limit = $range_finish - $i_start;
                }
            }
            if ($save_to_file) {
                $filetitle='/'.$file_name_append.'_'.$template_name;
                $dirname = str_replace('catalog/', '', DIR_APPLICATION);
                $filepath = $dirname.$file_location.$filetitle;
                $filepath = str_replace('//', '/', $filepath);
            }
            $file_path = str_replace('catalog/', '', DIR_APPLICATION).$template_location.'/'.$template_name;
            if (!file_exists($file_path)) {
                $this->response->setOutput('<head><meta name="robots" content="noindex"></head><body>Feed template not found.</body>');
                return;
            }
            $template = file_get_contents($file_path);

            $image_size = array();
            if ($image_cache !== "direct") {//no cache
                $image_size = explode('x', $image_cache);
            }
            if (count($image_size)!==2) {
                $image_size = array(600,600);
            }

            //taxes
            //$this->model_extension_feed_google_merchant_center->getTax(false);

            $shipping_extensions = array();
            $shipping_sort_order = array();
            $this->load->model('setting/extension');
            foreach ($this->model_setting_extension->getExtensions('shipping') as $value) {
                if ($shipping_carriers != null && in_array($value['code'], $shipping_carriers) && $this->get('shipping_' . $value['code'] . '_status')) {//only enabled
                    $value['sort_order'] = $this->get('shipping_'.$value['code'].'_sort_order');
                    $shipping_extensions[] = $value;
                    $shipping_sort_order[] = $value['sort_order'];
                }
            }
            array_multisort($shipping_sort_order, SORT_ASC, $shipping_extensions);
            $shipping_data = array('extensions' => $shipping_extensions, 'shipping_countries' => $countries, 'countries' => $country_list, 'country' => $main_country, 'clear_html' => $clear_html, 'shipping' => 1);//, 'lang' => $id_lang, 'id_shop' => $id_shop
            $output = '';
            if (!$save_to_file || ($save_to_file && ($i_start === 0 || $i_start === $range_start))) {//first run
                if ($save_to_file) {
                    file_put_contents($filepath.'.tmp', "");
                }
                //set shop, carriers, manufacturers, currencies, categories
                $template = $this->replaceShopInfo($template, $base_url);
                $template = $this->replaceShopCategory($template, $country_list, $store_id, $clear_html, $black_category_id, $white_category_id, $string_encode, $url_encode, $remove_char, $template_name, $base_taxonomy);
                $template = $this->replaceShopManufacturer($template, $clear_html, $country_list, $store_id, $string_encode, $url_encode, $remove_char);
                $template = $this->replaceShopCurrency($template, $store_id);
                $template = $this->replaceShopCarrier($template, $shipping_data);
                $template_explode = $this->explodeTemplateSequence($template, 'product');
            } elseif ($save_to_file) {
                $template_explode = $this->explodeTemplateSequence($template, 'product');
                $template_explode['before'] = '';
            }
            $list = array();
            if ($template_explode !== false) {
                $template_product = $template_explode['center'];
                $product_batch_count = 0;
                foreach ($country_list as $country_iso => $country) {
                    foreach ($country['languages'] as $lang_iso => $lang_id) {
                        $this->config->set('config_language_id', $lang_id);
                        $start = $i_start;
                        $limit = $i_limit;
                        $products = $this->model_extension_feed_google_merchant_center->getProducts($lang_id, $store_id, $start, $limit);
                        $language_iso = $this->getLocaleIso($lang_iso);
                        $language_locale = $this->getLocaleIso($language_iso, $country_iso);
                        while (count($products)>0) {
                            foreach ($products as $product) {
                                $reviews = array();
                                $rating = '';
                                if ((int)$product['reviews'] > 0) {
                                    $rating = 0;
                                    $reviews = $this->model_catalog_review->getReviewsByProductId($product['product_id'], 0, (int)$product['reviews']);
                                    foreach ($reviews as $review) {
                                        $rating+= (float)$review['rating'];
                                    }
                                    $rating = $rating/(float)$product['reviews'];
                                }
                                foreach ($country['currencies'] as $currency_code => $curr) {
                                    $currency_value = $curr['currency_value'];
                                    $decimal_place = $curr['decimal_place'];
                                    $product_url_parameter="";
                                    if ($use_language_parameter && $lang_iso !== $this->get('config_language')) {
                                        $product_url_parameter.="&amp;language=".$lang_iso;
                                    }
                                    if ($use_currency_parameter && $currency_code !== $this->get('config_currency')) {
                                        $product_url_parameter.="&amp;currency=".$currency_code;
                                    }
                                    $product_id = $product['product_id'];
                                    $model = $this->processText(trim($product['model']), $clear_html, 1, $string_encode, $remove_char);
                                    $base_quantity = $product['quantity'];
                                    if ($base_quantity < 0) {
                                        $base_quantity = 0;
                                    }
                                    $status = $product['status'];
                                    //skip excluded products
                                    if (in_array($product_id, $black_product_id)
                                    || (empty($white_product_id) === false && in_array($product_id, $white_product_id) === false)
                                    || ($sold_out_products==="exclude!" && ($base_quantity <= 0 || $base_quantity < $product['minimum']))//maybe add $product['subtract']
                                    || ($disabled_products==="exclude!" && $status==0)
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
                                    $product_category = array();
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
                                                    $product_category[$path_id]['name']=$this->processText($category_info['name'], 0, 1, $string_encode, $remove_char);
                                                    $product_category[$path_id]['parent_id']=$category_info['parent_id'];
                                                    $product_category[$path_id]['category_id']=$path_id;
                                                }
                                            }
                                        }
                                        if ($count>$counter) {
                                            $counter = $count;
                                            $category_id = $category['category_id'];
                                        }
                                    }
                                    $g_google_product_category = array();
                                    $category_id_google = $this->model_extension_feed_google_merchant_center->getTaxonomy($category_id);
                                    if (isset($category_id_google['taxonomy_id'])) {
                                        $g_google_product_category = $category_id_google;
                                    } else {
                                        $base_taxononmy_id = reset($base_taxonomy);
                                        $g_google_product_category['taxonomy_id'] = $base_taxononmy_id;
                                        $g_google_product_category['name'] = $this->model_extension_feed_google_merchant_center->getTaxonomyName($base_taxononmy_id);
                                    }
                                    $custom_product_category = $this->model_extension_feed_google_merchant_center->getTaxonomyCustom($category_id, $template_name);
                                    $link = str_replace(" ", "%20", $this->url->link('product/product', 'product_id=' . $product_id, $secure));

                                    if ($product_url_parameter !== "") {//add currency language parameters
                                        $link.=($this->strpos($link, "index.php?") !== false ? $product_url_parameter : "?".substr($product_url_parameter, 5));
                                    }
                                    if ($this->strpos($link, 'http') === false) {//add base url if link is only request
                                        $link = $base_url.$link;
                                    }
                                    //title & descriptions
                                    if ($use_meta && $product['meta_title'] != "") {
                                        $title = $product['meta_title'];
                                    } else {
                                        $title = $product['name'];
                                    }
                                    $title = $this->model_extension_feed_google_merchant_center->decodeChars($title);
                                    $title = $this->model_extension_feed_google_merchant_center->fixUpperCase($title);
                                    $title = $this->processText(trim($title), $clear_html, 1, $string_encode, $remove_char);
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
                                    $description = $this->processText($description, $clear_html, 1, $string_encode, $remove_char);
                                    $description = mb_substr($description, 0, 5000, 'UTF-8');
                                    $manufacturer = $this->processText(trim($product['manufacturer']), $clear_html, 1, $string_encode, $remove_char);
                                    //images
                                    $main_image_link = '';
                                    $additional_image_link = array();
                                    if ($product['image']) {
                                        $main_image_link = $this->model_extension_feed_google_merchant_center->getImageUrl($product['image'], $image_size[0], $image_size[1], ($image_cache !== "direct"), $image_url);
                                        if ($use_additional_images) {
                                            $additional_images = $this->model_extension_feed_google_merchant_center->getImages($product_id, $product['image']);
                                            $max_image_count = $use_additional_images;
                                            foreach ($additional_images as $value) {
                                                if ($max_image_count == 0) {
                                                    break;
                                                }
                                                $max_image_count--;
                                                $additional_images_url = $this->model_extension_feed_google_merchant_center->getImageUrl($value['image'], $image_size[0], $image_size[1], ($image_cache !== "direct"), $image_url);
                                                $additional_image_link[] = $additional_images_url;
                                            }
                                        }
                                    }
                                    //gtin & mpn
                                    $mpn = $product['mpn'];
                                    if ($mpn === '') {//if empty use model instead
                                        $mpn = $model;
                                    }
                                    $sku = $product['sku'];
                                    $upc = $product['upc'];
                                    $ean = $product['ean'];
                                    $jan = $product['jan'];
                                    $isbn = $product['isbn'];
                                    $gtin = $ean;//ean will be used if set in options
                                    if ($gtin==='') {
                                        $gtin=$upc;
                                    }
                                    if ($gtin==='') {
                                        $gtin=$jan;
                                    }
                                    if ($gtin==='') {
                                        $gtin=$isbn;
                                    }
                                    //prices
                                    $sales_start_date = '';
                                    $sales_end_date = '';
                                    $base_sale_price = '';
                                    if ((float)$product['special']) {
                                        $date_start = $product['date_start'];
                                        $date_end =$product['date_end'];
                                        if ($date_start=='0000-00-00') {
                                            $date_start=date("Y-m-d");
                                        }
                                        if ($date_end!=='0000-00-00' && $date_start < $date_end) {
                                            $sales_start_date = date('Y-m-d\T00:00:00O',strtotime($date_start));
                                            $sales_end_date = date('Y-m-d\T00:00:00O',strtotime($date_end));
                                        }
                                        $base_sale_price = $product['special'];
                                    }
                                    $base_price = ((float)$product['price'] > 0) ? $product['price'] : $this->model_extension_feed_google_merchant_center->getLowestPriceOption($product_id);
                                    if ($free_products === 0 && $base_price <= 0) {
                                        continue;
                                    }
                                    //product tab color, gender & age group
                                    $product_tab_data = $this->model_extension_feed_google_merchant_center->getProductTabData($product_id, $lang_id);
                                    $g_age_group = $product_tab_data['age_group'];
                                    $g_gender = $product_tab_data['gender'];
                                    $color = trim($product_tab_data['color']);
                                    //attributes
                                    $attributes = $this->model_extension_feed_google_merchant_center->getAllProductAttributes($product_id, $lang_id);
                                    //options
                                    $options = $this->model_extension_feed_google_merchant_center->getOptions($product_id, $selected_options, $lang_id);
                                    $product_weight = $product['weight'];
                                    $shipping_data['shipping'] = $product['shipping'];
                                    $products_options = array();
                                    foreach ($options as $key => $option_group) {
                                        $g_id = array();
                                        $g_id[] = $product_id;
                                        $price = $base_price;
                                        $sale_price = $base_sale_price;
                                        $option_price = 0;
                                        $option_shipping_weight = 0;
                                        $option_quantity = 0;//only not subtracting items
                                        $option_subtract = 0;//will use 1 if any subtract is 1
                                        $option_names = array();
                                        $option_values = array();
                                        $option_select = array();
                                        $option_checkbox = array();
                                        $option_image_link = null;
                                        ksort($option_group);//order to option_value_id
                                        foreach ($option_group as $option_value_id => $option) {
                                            $option_shipping_weight += ($option['weight_prefix'] === '+' ? $option['weight'] : -$option['weight']);
                                            if ($option_value_id == "") {//without options
                                                $option_quantity = $base_quantity;
                                                $option_subtract = $product['subtract'];
                                            } elseif ((int)$option['subtract'] === 0) {
                                                $option_quantity = $base_quantity;
                                            } elseif ($option_subtract == 0 || $option_quantity > $option['quantity']) {
                                                $option_quantity = $option['quantity'];//use only lowest quantity if subtract is 1, else option quantity is inaccurate
                                                $option_subtract = 1;
                                            }
                                            if ($option_quantity < 0) {
                                                $option_quantity = 0;
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
                                            if ($option['name'] != '' && $option_value_id != '') {
                                                $g_id[] = $option_value_id;
                                                if ($option['type'] == 'select') {//url selection parameters
                                                    $option_select[] = $option['product_option_value_id'];
                                                } else {
                                                    $option_checkbox[] = $option['product_option_value_id'];
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
                                        $availability = $in_stock_products;//default
                                        $available_for_order = 1;
                                        if ($status == 0) {//disabled
                                            $availability = $disabled_products;
                                        } elseif ($base_quantity == 0 || $base_quantity <= 0 || $base_quantity < $product['minimum']) {//if main quantity is 0, you can't order, so always sold out
                                            $availability = $sold_out_products;
                                            $available_for_order = 0;
                                        } elseif ($option_subtract == 1) {//option quantity might be inacurate, but use it
                                            if ($option_quantity == 0 || $option_quantity <= 0 || $option_quantity < $product['minimum']) {
                                                $availability = $sold_out_products;
                                                $available_for_order = 0;
                                            }
                                        }
                                        if ($option_price > 0) {//base price reset
                                            $price = $product['price'];
                                        }
                                        $price += $option_price;
                                        if ($free_products === 0 && $price <= 0) {
                                            continue;
                                        }
                                        $sale_price_with_tax = '';
                                        $sale_price_without_tax = '';
                                        if ($sale_price > 0) {
                                            $sale_price += $option_price;
                                            $sale_price_without_tax = round($sale_price*$currency_value, $decimal_place);//.' '.$currency_code;//
                                            $sale_price_with_tax = round($this->tax->calculate($sale_price, $product['tax_class_id'], 1)*$currency_value, $decimal_place);//.' '.$currency_code;
                                        }
                                        $price_without_tax = round($price*$currency_value, $decimal_place);
                                        $price_with_tax = round($this->tax->calculate($price, $product['tax_class_id'], 1)*$currency_value, $decimal_place);
                                        $weight = $this->weight->format(($option_shipping_weight+$product_weight), null, '.', '');
                                        $weight_unit = $this->weight->getUnit($product['weight_class_id']);
                                        if (!empty($price_range)) {
                                            $final_price = $sale_price_with_tax != "" ? $sale_price_with_tax : $price_with_tax;
                                            if ((count($price_range) == 2
                                            && !($price_range[0] <= $final_price
                                            && $price_range[1] >= $final_price))
                                            || (count($price_range) == 1
                                            && $price_range[0] > $final_price)
                                            ) {
                                                continue;
                                            }
                                        }
                                        // Shipping Methods
                                        $product['total'] = ($sale_price_without_tax == "" ? $price_without_tax : $sale_price_without_tax) * $product['minimum'];
                                        $product_link = $link;
                                        if ($use_select_parameter) {
                                            if (!empty($option_select)) {
                                                $product_link.=($this->strpos($product_link, "?") !== false ? '&amp;select='.implode(',',$option_select) : '?select='.implode(',',$option_select));
                                            }
                                            if (!empty($option_checkbox)) {
                                                $product_link.=($this->strpos($product_link, "?") !== false ? '&amp;checkbox='.implode(',',$option_checkbox) : '?checkbox='.implode(',',$option_checkbox));
                                            }
                                        }
                                        //fill data
                                        $id = implode('-', $g_id);
                                        $products_options[$id]['option_ids'] = $g_id;
                                        $products_options[$id]['product_link'] = $this->processText($product_link, 0, 1, $url_encode);
                                        $products_options[$id]['full_price_without_tax'] = $price_without_tax;
                                        $products_options[$id]['full_price_with_tax'] = $price_with_tax;
                                        $products_options[$id]['sale_price_without_tax'] = $sale_price_without_tax;
                                        $products_options[$id]['sale_price_with_tax'] = $sale_price_with_tax;
                                        $products_options[$id]['sales_start_date'] = $sales_start_date;
                                        $products_options[$id]['sales_end_date'] = $sales_end_date;
                                        $products_options[$id]['option_quantity'] = $option_quantity;
                                        $products_options[$id]['option'] = $option_group;
                                        $products_options[$id]['availability'] = $availability;
                                        $products_options[$id]['exclude'] = ($availability === 'exclude!');
                                        $products_options[$id]['available_for_order'] = $available_for_order;
                                        $products_options[$id]['main_image'] = ($option_image_link == null ? $main_image_link : $option_image_link);
                                        $products_options[$id]['cart'] = array(array_merge(array('cart_id' => 0),array('option' => array($option)),$product));
                                    }
                                    if ($availability === 'exclude!' || empty($products_options)) {//skip excluded
                                        continue;
                                    }
                                    $product_data = array();
                                    $product_data['quantity'] = $base_quantity;
                                    $product_data['product_id'] = $product_id;
                                    $product_data['model'] = $model;
                                    $product_data['taxonomy'] = $g_google_product_category;
                                    $product_data['custom_taxonomy'] = $custom_product_category;
                                    $product_data['category'] = $product_category;
                                    $product_data['weight'] = $weight;
                                    $product_data['weight_unit'] = $weight_unit;
                                    $product_data['gtin'] = $gtin;
                                    $product_data['sku'] = $sku;
                                    $product_data['upc'] = $upc;
                                    $product_data['ean'] = $ean;
                                    $product_data['jan'] = $jan;
                                    $product_data['isbn'] = $isbn;
                                    $product_data['mpn'] = $mpn;
                                    $product_data['description'] = $description;
                                    $product_data['manufacturer'] = $manufacturer;
                                    $product_data['manufacturer_id'] = $product['manufacturer_id'];
                                    $product_data['product_name'] = $title;
                                    $product_data['currency_iso'] = $currency_code;
                                    $product_data['attributes'] = $attributes;
                                    $product_data['additional_images'] = $additional_image_link;
                                    $product_data['options'] = $products_options;
                                    $product_data['color'] = $color;
                                    $product_data['age_group'] = $g_age_group;
                                    $product_data['gender'] = $g_gender;
                                    $product_data['country_iso'] = $country_iso;
                                    $product_data['language_iso'] = $language_iso;
                                    $product_data['language_locale'] = $language_locale;
                                    $product_data['rating'] = $rating;
                                    $product_data['reviews'] = $reviews;
                                    $list[]=$this->processProductTemplate($template_product,  $product_data, $shipping_data);
                                }
                            }
                            if (!$save_to_file) {
                                $start = $start + $limit;
                                if ($range_finish > 0 && ($start + $limit) > $range_finish) {//last batch is limited, re-adjust
                                    $limit = $range_finish - $start;
                                }
                                if ($range_finish == 0 || $start < $range_finish) {//range not used or finish not reached
                                    $products = $this->model_extension_feed_google_merchant_center->getProducts($lang_id, $store_id, $start, $limit);
                                } else {//finish reached
                                    $products = array();
                                }
                            } else {
                                $product_batch_count = count($products);
                                $products = array();
                            }
                        }
                    }
                }
                if ($save_to_file) {
                    $start = $start + $limit;
                    if ($range_finish > 0 && ($start + $limit) > $range_finish) {//last batch is limited, re-adjust
                        $limit = $range_finish - $start;
                    }
                    file_put_contents($filepath.'.tmp',$this->clearTemplate((!empty($template_explode['before']) ? $template_explode['before'] : $template_explode['delimiter']).implode($template_explode['delimiter'], $list)) , FILE_APPEND | LOCK_EX);
                    $output = "";
                }
            } else {//no product list
                $product_count = 0;
                $template_explode = array('before' => '', 'after' => $template, 'delimiter' => '');
            }
            if (!$save_to_file) {
                $output = $template_explode['before'].implode($template_explode['delimiter'], $list).$template_explode['after'];
                unset($list);
            } elseif ($product_count <= $start || $range_finish <= $start || $product_batch_count === 0) {
                $output = $template_explode['after'];
            }
            $output = $this->clearTemplate($output);
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
                    $link = $_SERVER['REQUEST_URI'];
                    $link = $this->model_extension_feed_google_merchant_center->setParameterURL($link, 'start', $start);
                    $link = $this->model_extension_feed_google_merchant_center->setParameterURL($link, 'limit', $limit);
                    $link = ($secure ? "https" : "http")."://".$_SERVER['HTTP_HOST'].$link;
                    header('Cache-Control: no-store');
                    header('Location: '.$link, true, 302);
                }
                die();
            } else {
                $file_type = pathinfo($template_name, PATHINFO_EXTENSION);
                if ($file_type !== "xml" && $file_type !== "csv" && $file_type !== "json") {//json seem to be ignored in browsers
                    $file_type = "plain";
                }
                if ($gzip_compression && !ini_get('zlib.output_compression') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && $this->strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
                    $output = gzencode($output, 5);
                    header("Content-Encoding: gzip");
                }
                header('Cache-Control: no-store');
                header('Content-Disposition: inline; filename="'.$file_name_append.'_'.$template_name.'"');
                header('Content-Type: text/'.$file_type.'; charset=UTF-8');
                print($output);
                exit(0);
            }
        } else {
            $this->response->setOutput('<head><meta name="robots" content="noindex"></head><body>Disabled feed.</body>');
        }
    }

    public function explodeTemplateSequence($template, $sequence_name)
    {
        //simplified list
        if ($this->strpos($template, '_start#')) {//backwards compatibility
            $append_start = '_start#';
            $sequence_start = '#'.$sequence_name.'_start#';
            $sequence_end = '#'.$sequence_name.'_end#';
            $start_len = strlen($sequence_start);
            $end_len = strlen($sequence_end);
        } else {
            $append_start = '_list#';
            $sequence_start = '#'.$sequence_name.'_list#';
            $sequence_end = $sequence_start;
            $start_len = strlen($sequence_start);
            $end_len = $start_len;
        }
        $start = $this->strpos($template, $sequence_start);
        if ($start !== false) {//only first
            $end = $this->strpos($template, $sequence_end, $start+$start_len);
            $template_center = substr($template, $start+$start_len, $end-($start+$start_len));
            $template_start = substr($template, 0, $start);
            $template_end = substr($template, $end+$end_len);
            $delimiter = $this->strpos($template_center, '#delimiter#');
            $next_start = $this->strpos($template_center, $append_start);
            if ($delimiter !== false && ($next_start === false || $delimiter < $next_start)) {
                $delimiter = substr($template_center, 0, $delimiter);
                $template_center = substr($template_center, strlen($delimiter.'#delimiter#'));
            } else {
                $delimiter = "";
            }
            return array(
                'before' => $template_start,
                'center' => $template_center,
                'after' => $template_end,
                'delimiter' => $delimiter
            );
        } else {
            return false;
        }
    }

    public function processProductTemplate($template_product, $product_data, $shipping_data, $first_option = false)
    {
        if ($this->strpos($template_product, '_start#')) {//backwards compatibility
            $append_start = '_start#';
            $append_end = '_end#';
        } else {
            $append_start = '_list#';
            $append_end = $append_start;
        }
        //variant is within option
        $option_start = $this->strpos($template_product, '#product_option'.$append_start);
        $variant_start = $this->strpos($template_product, '#product_variant'.$append_start);
        $option_end = $this->strpos($template_product, '#product_option'.$append_end, $option_start+1);
        while ($option_start !== false && $variant_start !== false && $option_end !== false && $option_start < $variant_start && $option_end > $variant_start) {
            $template_explode = $this->explodeTemplateSequence($template_product, 'product_option');
            if ($template_explode !== false) {
                $delimiter = $template_explode['delimiter'];
                $list = array();
                foreach (array_reverse($product_data['options']) as $value) {
                    if (!$value['exclude'] && $value['available_for_order'] && !array_key_exists('', $value['option'])) {
                        $data = $this->processProductTemplate($template_explode['center'], $product_data, $shipping_data, $value);
                        $list[] = $data;
                    }
                }
                $list = array_unique(array_filter($list));
                $template_product = $template_explode['before'].implode($delimiter, $list).$template_explode['after'];
            }
            $option_start = $this->strpos($template_product, '#product_option'.$append_start);
            $variant_start = $this->strpos($template_product, '#product_variant'.$append_start);
            $option_end = $this->strpos($template_product, '#product_option'.$append_end, $option_start+1);
        }
        //option is within variant - normal
        $template_explode = $this->explodeTemplateSequence($template_product, 'product_variant');
        while ($template_explode !== false) {
            $delimiter = $template_explode['delimiter'];
            //option replaces
            $option_template = $this->replaceProductOption(
                $template_explode['center'],
                $product_data,
                $delimiter,
                $shipping_data
            );
            $template_product = $template_explode['before'].$option_template.$template_explode['after'];
            $template_explode = $this->explodeTemplateSequence($template_product, 'product_variant');
        }
        //option indipendent replaces
        $template_product = $this->replaceProductAttributes($template_product, $product_data['attributes']);
        $template_product = $this->replaceProductReviews($template_product, $product_data);
        $template_product = $this->replaceProduct($template_product, $product_data);
        //find the best first option
        foreach (array_reverse($product_data['options']) as $value) {
            if ($first_option === false && !$value['exclude'] && $value['available_for_order']) {
                $first_option = $value;
            }
        }
        if ($first_option !== false) {
            $template_product = $this->replaceOption($template_product, $first_option, $shipping_data);//will use first option
        }
        $this->setCustomProduct();
        $template_product = $this->replaceProductCarrier($template_product, $shipping_data);
        $this->setCustomProduct(null);
        $template_product = $this->replaceProductImages($template_product, $product_data['additional_images']);
        $template_product = $this->replaceProductCategory($template_product, $product_data);
        $template_product = $this->processCalculate($template_product);
        $template_product = $this->processRound($template_product);
        return $template_product;
    }

    public function replaceProductOption($option_template, $product_data, $delimiter, $shipping_data)
    {
        $list = array();
        foreach ($product_data['options'] as $option) {
            $template = $option_template;
            $template = $this->replaceOption($template, $option, $shipping_data);
            $list[]=$template;
        }
        $list = array_unique(array_filter($list));
        return implode($delimiter, $list);
    }

    public function replaceProduct($template, $product_data)
    {
        $template = str_replace('#quantity#', $product_data['quantity'], $template);
        $template = str_replace('#product_id#', $product_data['product_id'], $template);
        $template = str_replace('#google_category_name#', $product_data['taxonomy']['name'], $template);
        $template = str_replace('#google_category_id#', $product_data['taxonomy']['taxonomy_id'], $template);
        $template = str_replace('#custom_category_name#', $product_data['custom_taxonomy']['taxonomy_name'], $template);
        $template = str_replace('#custom_category_id#', $product_data['custom_taxonomy']['taxonomy_id'], $template);
        $template = str_replace('#weight#', $product_data['weight'], $template);
        $template = str_replace('#weight_unit#', $product_data['weight_unit'], $template);
        $template = str_replace('#gtin#', $product_data['gtin'], $template);
        $template = str_replace('#sku#', $product_data['sku'], $template);
        $template = str_replace('#upc#', $product_data['upc'], $template);
        $template = str_replace('#ean#', $product_data['ean'], $template);
        $template = str_replace('#jan#', $product_data['jan'], $template);
        $template = str_replace('#isbn#', $product_data['isbn'], $template);
        $template = str_replace('#product_description#', $product_data['description'], $template);
        $template = str_replace('#manufacturer_name#', $product_data['manufacturer'], $template);
        $template = str_replace('#manufacturer_id#', $product_data['manufacturer_id'], $template);
        $template = str_replace('#product_name#', $product_data['product_name'], $template);
        $template = str_replace('#model#', $product_data['model'], $template);
        $template = str_replace('#currency_iso#', $product_data['currency_iso'], $template);
        $template = str_replace('#color#', $product_data['color'], $template);
        $template = str_replace('#gender#', $product_data['gender'], $template);
        $template = str_replace('#age_group#', $product_data['age_group'], $template);
        $template = str_replace('#mpn#', $product_data['mpn'], $template);
        $template = str_replace('#country_iso#', $product_data['country_iso'], $template);
        $template = str_replace('#language_iso#', $product_data['language_iso'], $template);
        $template = str_replace('#language_locale#', $product_data['language_locale'], $template);
        $template = str_replace('#rating#', $product_data['rating'], $template);
        $template = str_replace('#rating_count#', count($product_data['reviews']), $template);
        return $template;
    }

    public function replaceProductAttributes($template, $data)
    {
        $template_explode = $this->explodeTemplateSequence($template, 'product_attributes');
        while ($template_explode !== false) {
            $delimiter = $template_explode['delimiter'];
            $list = array();
            foreach ($data as $value) {
                $center = $template_explode['center'];
                //replace
                $center = str_replace('#attribute_value#', $value['value'], $center);
                $center = str_replace('#attribute_name#', $value['name'], $center);
                $center = str_replace('#attribute_group#', $value['group_name'], $center);
                $center = str_replace('#attribute_id#', $value['attribute_id'], $center);
                $center = str_replace('#attribute_group_id#', $value['attribute_group_id'], $center);
                $list[]=$center;
            }
            $list = array_unique(array_filter($list));
            $template = $template_explode['before'].implode($delimiter, $list).$template_explode['after'];
            $template_explode = $this->explodeTemplateSequence($template, 'product_attributes');
        }
        //replace specific
        foreach ($data as $value) {
            $key = $value['attribute_id'];
            $template = str_replace('#attribute_value_'.$key.'#', $value['value'], $template);
            $template = str_replace('#attribute_name_'.$key.'#', $value['name'], $template);
            $template = str_replace('#attribute_group_'.$key.'#', $value['group_name'], $template);
        }
        //clean
        $template = preg_replace('/#attribute_value_[\\d]+#/', '', $template);
        $template = preg_replace('/#attribute_name_[\\d]+#/', '', $template);
        $template = preg_replace('/#attribute_group_[\\d]+#/', '', $template);
        return $template;
    }

    public function replaceOption($template, $option, $shipping_data)
    {
        if ($option['exclude']) {
            return '';
        }
        $template = str_replace('#id#', implode('-', $option['option_ids']), $template);
        $template = str_replace('#product_link#', $option['product_link'], $template);
        $template = str_replace('#full_price_without_tax#', $option['full_price_without_tax'], $template);
        $template = str_replace('#full_price_with_tax#', $option['full_price_with_tax'], $template);
        $template = str_replace(
            '#full_price_tax#',
            $option['full_price_with_tax']-$option['full_price_without_tax'],
            $template
        );
        $template = str_replace('#sale_price_without_tax#', $option['sale_price_without_tax'], $template);
        $template = str_replace('#sale_price_with_tax#', $option['sale_price_with_tax'], $template);
        $template = str_replace(
            '#sale_price_tax#',
            ($option['sale_price_without_tax'] !== "" && $option['sale_price_with_tax'] !== "" ? (float)$option['sale_price_with_tax']-(float)$option['sale_price_without_tax'] : ""),
            $template
        );
        $price_without_tax = ($option['sale_price_without_tax'] !== "" ? $option['sale_price_without_tax'] : $option['full_price_without_tax']);
        $price_with_tax = ($option['sale_price_with_tax'] !== "" ? $option['sale_price_with_tax'] : $option['full_price_with_tax']);
        $template = str_replace('#price_without_tax#', $price_without_tax, $template);
        $template = str_replace('#price_with_tax#', $price_with_tax, $template);
        $template = str_replace(
            '#price_tax#',
            (float)$price_with_tax-(float)$price_without_tax,
            $template
        );
        $template = str_replace('#sales_start_date#', $option['sales_start_date'], $template);
        $template = str_replace('#sales_end_date#', $option['sales_end_date'], $template);
        $template = str_replace('#sales_date#', ($option['sales_start_date'] != '' && $option['sales_end_date'] != ''? $option['sales_start_date'].'/'.$option['sales_end_date'] : ''), $template);
        $template = str_replace('#option_quantity#', $option['option_quantity'], $template);
        $template = str_replace('#availability#', $option['availability'], $template);
        $template = str_replace('#main_image#', $option['main_image'], $template);
        $template = $this->replaceProductMultiOption($template, $option['option']);
        $this->setCustomProduct($option['cart']);
        $template = $this->replaceProductCarrier($template, $shipping_data);
        $this->setCustomProduct(null);
        return $template;
    }

    public function replaceProductMultiOption($template, $data)
    {
        $template_explode = $this->explodeTemplateSequence($template, 'product_option');
        while ($template_explode !== false) {
            $delimiter = $template_explode['delimiter'];
            $list = array();
            foreach ($data as $value) {
                $center = $template_explode['center'];
                //replace
                if (!empty($value['option_name'])) {
                    $center = str_replace('#option_id#', $value['option_id'], $center);
                    $center = str_replace('#option_value_id#', $value['option_value_id'], $center);
                    $center = str_replace('#option_name#', $value['option_name'], $center);
                    $center = str_replace('#option_value#', $value['name'], $center);
                    $list[]=$center;
                }
            }
            $list = array_unique(array_filter($list));
            $template = $template_explode['before'].implode($delimiter, $list).$template_explode['after'];
            $template_explode = $this->explodeTemplateSequence($template, 'product_option');
        }
        //simple
        if (!empty($data)) {
            $first_option = reset($data);
            $template = str_replace('#option_id#', $first_option['option_id'], $template);
            $template = str_replace('#option_value_id#', $first_option['option_value_id'], $template);
            $template = str_replace('#option_name#', $first_option['option_name'], $template);
            $template = str_replace('#option_value#', $first_option['name'], $template);
        }
        //replace specific and simplified
        $options = array();
        foreach ($data as $value) {
            $key = $value['option_id'];
            $template = str_replace('#option_value_'.$key.'#', $value['name'], $template);
            $template = str_replace('#option_value_id_'.$key.'#', $value['option_value_id'], $template);
            $options[] = $value['name'];
        }
        $template = str_replace('#variations#', implode(',', $options), $template);
        //clean
        $template = preg_replace('/#option_value_[\\d]+#/', '', $template);
        $template = preg_replace('/#option_value_id_[\\d]+#/', '', $template);
        return $template;
    }

    public function replaceProductCategory($template, $data)
    {
        $template_explode = $this->explodeTemplateSequence($template, 'product_category');
        while ($template_explode !== false) {
            $delimiter = $template_explode['delimiter'];
            $list = array();
            foreach ($data['category'] as $category) {
                $center = $template_explode['center'];
                $center = str_replace('#category_id#', $category['category_id'], $center);
                $center = str_replace('#parent_id#', $category['parent_id'], $center);
                $center = str_replace('#category_name#', $category['name'], $center);
                $list []= $center;
            }
            $list = array_unique(array_filter($list));
            $template = $template_explode['before'].implode($delimiter, $list).$template_explode['after'];
            $template_explode = $this->explodeTemplateSequence($template, 'product_category');
        }
        if (count($data['category']) > 0) {
            $first_category = reset($data['category']);
            $template = str_replace('#category_id#', $first_category['category_id'], $template);
            $template = str_replace('#parent_id#', $first_category['parent_id'], $template);
            $template = str_replace('#category_name#', $first_category['name'], $template);
        }
        return $template;
    }

    public function replaceProductCarrier($template, $shipping_data)
    {
        $template_explode = $this->explodeTemplateSequence($template, 'product_carrier');
        if ($template_explode !== false) {
            $delimiter = $template_explode['delimiter'];
            $list = $this->replaceCarrier($template_explode['center'], $shipping_data);
            $template = $template_explode['before'].implode($delimiter, $list).$template_explode['after'];
            return $this->replaceProductCarrier($template, $shipping_data);
        }
        $template = implode($this->replaceCarrier($template, $shipping_data, 1));
        return $template;
    }

    public function replaceProductReviews($template, $product_data)
    {
        $template_explode = $this->explodeTemplateSequence($template, 'product_review');
        if ($template_explode !== false) {
            $delimiter = $template_explode['delimiter'];
            $list = array();
            foreach ($product_data['reviews'] as $value) {
                $center = $template_explode['center'];
                $center = str_replace('#review_rating#', $value['rating'], $center);
                $center = str_replace('#review_id#', $value['review_id'], $center);
                $center = str_replace('#review#', $value['text'], $center);
                $center = str_replace('#review_author#', $value['author'], $center);
                $center = str_replace('#review_date#', $value['date_added'], $center);
                $list []= $center;
            }
            $template = $template_explode['before'].implode($delimiter, $list).$template_explode['after'];
        }
        return $template;
    }

    public function replaceProductImages($template, $images)
    {
        $template_explode = $this->explodeTemplateSequence($template, 'product_images');
        while ($template_explode !== false) {
            $delimiter = $template_explode['delimiter'];
            $list = array();
            $index = 1;
            foreach ($images as $image) {
                $center = $template_explode['center'];
                $center = str_replace('#additional_image_link#', $image, $center);
                $center = str_replace('#additional_image_index#', $index, $center);
                $index++;
                $list[]=$center;
            }
            $list = array_unique(array_filter($list));
            $template = $template_explode['before'].implode($delimiter, $list).$template_explode['after'];
            $template_explode = $this->explodeTemplateSequence($template, 'product_images');
        }
        if (count($images) > 0) {
            $template = str_replace('#additional_image_link#', reset($images), $template);
        } else {
            $template = str_replace('#additional_image_link#', '', $template);
        }
        $template = str_replace('#additional_image_index#', '1', $template);
        return $template;
    }

    public function replaceShopInfo($template, $base_url)
    {
        $template = str_replace('#shop_title#', $this->get('config_meta_title'), $template);
        $template = str_replace('#shop_description#', $this->get('config_meta_description'), $template);
        $template = str_replace('#shop_domain#', parse_url($base_url, PHP_URL_HOST), $template);
        $template = str_replace('#shop_url#', $base_url, $template);
        $template = str_replace('#date#', date('Y-m-d'), $template);
        $template = str_replace('#datetime#', date('Y-m-d\TH:i:sO'), $template);//datetime_format
        return $template;
    }

    public function replaceShopCategory($template, $country_list, $shop_id, $clear_html, $black_category_id, $white_category_id, $string_encode, $url_encode, $remove_char, $template_name, $base_taxonomy)
    {
        $template_explode = $this->explodeTemplateSequence($template, 'shop_category');
        while ($template_explode !== false) {
            $list = array();
            foreach ($country_list as $iso_country => $country) {
                foreach ($country['languages'] as $lang_iso => $lang_id) {
                    $this->config->set('config_language_id', $lang_id);
                    $language_iso = $this->getLocaleIso($lang_iso);
                    $language_locale = $this->getLocaleIso($language_iso, $iso_country);
                    $categories = $this->model_extension_feed_google_merchant_center->getCategories($lang_id, $shop_id);
                    $delimiter = $template_explode['delimiter'];
                    foreach ($categories as $parent) {
                        foreach ($parent as $value) {
                            $category = $value;
                            $center = $template_explode['center'];
                            $custom_category = $this->model_extension_feed_google_merchant_center->getTaxonomyCustom($value['category_id'], $template_name);
                            $g_google_product_category = array();
                            $category_id_google = $this->model_extension_feed_google_merchant_center->getTaxonomy($value['category_id']);
                            if (isset($category_id_google['taxonomy_id'])) {
                                $g_google_product_category = $category_id_google;
                            } else {
                                $base_taxononmy_id = reset($base_taxonomy);
                                $g_google_product_category['taxonomy_id'] = $base_taxononmy_id;
                                $g_google_product_category['name'] = $this->model_extension_feed_google_merchant_center->getTaxonomyName($base_taxononmy_id);
                            }
                            $center = str_replace('#google_category_name#', $g_google_product_category['name'], $center);
                            $center = str_replace('#google_category_id#', $g_google_product_category['taxonomy_id'], $center);
                            $center = str_replace('#custom_category_name#', $custom_category['taxonomy_name'], $center);
                            $center = str_replace('#custom_category_id#', $custom_category['taxonomy_id'], $center);
                            $center = str_replace('#category_id#', $value['category_id'], $center);
                            $center = str_replace('#parent_id#', $value['parent_id'], $center);
                            $center = str_replace('#category_meta_keywords#', $this->processText($value['meta_keyword'], $clear_html, 1, $string_encode, $remove_char), $center);
                            $center = str_replace('#category_meta_description#', $this->processText($value['meta_description'], $clear_html, 1, $string_encode, $remove_char), $center);
                            $center = str_replace('#category_meta_name#', $this->processText($value['meta_title'], $clear_html, 1, $string_encode, $remove_char), $center);
                            $center = str_replace('#category_description#', $this->processText($value['description'], $clear_html, 1, $string_encode, $remove_char), $center);
                            $center = str_replace('#category_name#', $this->processText($value['name'], $clear_html, 1, $string_encode, $remove_char), $center);
                            $center = str_replace('#category_link#', $this->processText($this->url->link('product/category', 'path='.$value['category_id']), 0, 1, $url_encode), $center);
                            $center = str_replace('#country_iso#', $iso_country, $center);
                            $center = str_replace('#language_iso#', $language_iso, $center);
                            $center = str_replace('#language_locale#', $language_locale, $center);
                            $center = $this->processCalculate($center);
                            $center = $this->processRound($center);
                            $list[] = $center;
                        }
                    }
                }
            }
            $list = array_unique(array_filter($list));
            $template = $template_explode['before'].implode($delimiter, $list).$template_explode['after'];
            $template_explode = $this->explodeTemplateSequence($template, 'shop_category');
        }
        return $template;
    }

    public function replaceShopManufacturer($template, $clear_html, $country_list, $id_store, $string_encode, $url_encode, $remove_char)//, $id_shop)
    {
        $template_explode = $this->explodeTemplateSequence($template, 'shop_manufacturer');
        while ($template_explode !== false) {
            $list = array();
            foreach ($country_list as $iso_country => $country) {
                foreach ($country['languages'] as $lang_iso => $lang_id) {
                    $this->config->set('config_language_id', $lang_id);
                    $language_iso = $this->getLocaleIso($lang_iso);
                    $language_locale = $this->getLocaleIso($language_iso, $iso_country);
                    $this->load->model('catalog/manufacturer');
                    $manufacturers = $this->model_catalog_manufacturer->getManufacturers();
                    $delimiter = $template_explode['delimiter'];
                    foreach ($manufacturers as $value) {
                        $center = $template_explode['center'];
                        $center = str_replace('#manufacturer_id#', $value['manufacturer_id'], $center);
                        $center = str_replace('#manufacturer_name#', $this->processText($value['name'], $clear_html, 1, $string_encode, $remove_char), $center);
                        $center = str_replace('#manufacturer_link#', $this->processText($this->url->link('product/manufacturer/info', 'manufacturer_id=' .$value['manufacturer_id']), 0, 1, $url_encode), $center);
                        $center = str_replace('#country_iso#', $iso_country, $center);
                        $center = str_replace('#language_iso#', $language_iso, $center);
                        $center = str_replace('#language_locale#', $language_locale, $center);
                        $center = $this->processCalculate($center);
                        $center = $this->processRound($center);
                        $list []= $center;
                    }
                }
            }
            $list = array_unique(array_filter($list));
            $template = $template_explode['before'].implode($delimiter, $list).$template_explode['after'];
            $template_explode = $this->explodeTemplateSequence($template, 'shop_manufacturer');
        }
        return $template;
    }

    public function replaceShopCurrency($template, $id_store)//, $id_shop)
    {
        $template_explode = $this->explodeTemplateSequence($template, 'shop_currency');
        while ($template_explode !== false) {
            $delimiter = $template_explode['delimiter'];
            $this->load->model('localisation/currency');
            $currencies= $this->model_localisation_currency->getCurrencies();
            $list = array();
            foreach ($currencies as $value) {
                if ($value['status'] == 1) {
                    $center = $template_explode['center'];
                    $center = str_replace('#currency_iso#', $value['code'], $center);
                    $center = str_replace('#currency_rate#', $value['value'], $center);
                    $center = str_replace('#currency_name#', $value['title'], $center);
                    $center = str_replace('#currency_symbol_right#', $value['symbol_right'], $center);
                    $center = str_replace('#currency_symbol_left#', $value['symbol_left'], $center);
                    $center = $this->processCalculate($center);
                    $center = $this->processRound($center);
                    $list[]= $center;
                }
            }
            $list = array_unique(array_filter($list));
            $template = $template_explode['before'].implode($delimiter, $list).$template_explode['after'];
            $template_explode = $this->explodeTemplateSequence($template, 'shop_currency');
        }
        return $template;
    }

    public function replaceShopCarrier($template, $shipping_data)//$id_country, $id_shop
    {
        $this->setCustomProduct();
        $template_explode = $this->explodeTemplateSequence($template, 'shop_carrier');
        while ($template_explode !== false) {
            $list = $this->replaceCarrier($template_explode['center'], $shipping_data);
            $delimiter = $template_explode['delimiter'];
            $center = implode($delimiter, $list);
            $center = $this->processCalculate($center);
            $center = $this->processRound($center);
            $template = $template_explode['before'].$center.$template_explode['after'];
            $template_explode = $this->explodeTemplateSequence($template, 'shop_carrier');
        }
        $template_explode = $this->explodeTemplateSequence($template, 'product');
        if ($template_explode === false) {
            $template = implode($this->replaceCarrier($template, $shipping_data, 1));
        } else {
            if ($this->strpos($template, '_start#')) {//backwards compatibility
                $sequence_start = '#product_start#';
                $sequence_end = '#product_end#';
            } else {
                $sequence_start = '#product_list#';
                $sequence_end = $sequence_start;
            }
            $template_explode['before'] = implode($this->replaceCarrier($template_explode['before'], $shipping_data, 1));
            $template_explode['after'] = implode($this->replaceCarrier($template_explode['after'], $shipping_data, 1));
            $template = $template_explode['before'].$sequence_start.$template_explode['center'].$sequence_end.$template_explode['after'];
        }
        $this->setCustomProduct(null);
        return $template;
    }

    public function replaceCarrier($template, $shipping_data, $only_first = 0)//$id_country, $id_shop
    {
        $string_encode = 0;
        if (isset($_GET['html_entities_text'])) {
            $string_encode = $_GET['html_entities_text'];
        }
        $remove_char = '';
        if (isset($_GET['remove_char'])) {
            $remove_char = $_GET['remove_char'];
            $remove_char = ($remove_char !=='' ? '/['.htmlspecialchars_decode($remove_char).']/' : '');
        }
        if (!(int)$shipping_data['shipping']) {//shipping not required
            $template = preg_replace('/#carrier_price_with_tax_[\w]+#/', '', $template);
            $template = preg_replace('/#carrier_price_without_tax_[\w]+#/', '', $template);
            $template = preg_replace('/#carrier_price_tax_[\w]+#/', '', $template);
            return array($template);
        }
        $list = array();
        $clear_html = $shipping_data['clear_html'];
        $default_country_id = (int)$this->get('config_country_id');
        $default_zone_id = (int)$this->get('config_zone_id');
        $shipping_options = array();
        //specific
        $matches = array();
        $country_specific = array();
        $countries = ($only_first || $this->strpos($template, '#carrier_country_') === false ? $shipping_data['country'] : $shipping_data['shipping_countries']);
        if (preg_match('/_tax_[\w]+#/', $template, $matches)) {
            foreach ($matches as $value) {
                $explode = explode('_', $value);
                $country_specific[] = str_replace('#', '', end($explode));
            }
            if (!empty($country_specific)) {//merge both country lists if needed
                $countries = $this->model_extension_feed_google_merchant_center->getCountriesByIso($country_specific) + $countries;
            }
        }
        $single_zone = ($this->strpos($template, '#carrier_zone_') === false || $only_first);//use single zone if not used in template or first carrier only
        if ($this->strpos($template, '#carrier_') !== false) {
            $shipping_options = $this->model_extension_feed_google_merchant_center->getShippingInfo($countries, $shipping_data['extensions'], $single_zone);
            foreach ($shipping_data['countries'] as $country_iso => $country) {
                foreach ($country['currencies'] as $currency_code => $currency) {
                    $currency_value = $currency['currency_value'];
                    $decimal_place = $currency['decimal_place'];
                    foreach ($countries as $country) {
                        $shipping_country_iso = $country['country']['iso_code_3'];
                        foreach ($country['zones'] as $zone) {
                            if (array_key_exists($shipping_country_iso, $shipping_options) && array_key_exists($zone['code'], $shipping_options[$shipping_country_iso])) {
                                foreach ($shipping_options[$shipping_country_iso][$zone['code']] as $carriers) {
                                    foreach ($carriers as $code => $carrier) {
                                        $center = $template;
                                        $price_with_tax = round($this->tax->calculate($carrier['cost'], $carrier['tax_class_id'], 1)*$currency_value, $decimal_place);
                                        $price_without_tax = round($carrier['cost']*$currency_value, $decimal_place);
                                        $center = str_replace('#carrier_country_iso#', $shipping_country_iso, $center);
                                        $center = str_replace('#carrier_country_name#', $this->processText($country['country']['name'], $clear_html, 1, $string_encode, $remove_char), $center);
                                        $center = str_replace('#carrier_zone_iso#', $zone['code'], $center);
                                        $center = str_replace('#carrier_zone_name#', $this->processText($zone['name'], $clear_html, 1, $string_encode, $remove_char), $center);
                                        $center = str_replace('#carrier_code#', $code, $center);
                                        $center = str_replace('#carrier_name#', $carrier['title'], $center);
                                        $center = str_replace('#carrier_price_with_tax#', $price_with_tax, $center);
                                        $center = str_replace('#carrier_price_without_tax#', $price_without_tax, $center);
                                        $center = str_replace('#carrier_price_tax#', $price_with_tax-$price_without_tax, $center);
                                        //specific
                                        $center = str_replace('#carrier_price_with_tax_'.$code.'_'.$shipping_country_iso.'#', $price_with_tax, $center);
                                        $center = str_replace('#carrier_price_without_tax_'.$code.'_'.$shipping_country_iso.'#', $price_without_tax, $center);
                                        $center = str_replace('#carrier_price_tax_'.$code.'_'.$shipping_country_iso.'#', $price_with_tax-$price_without_tax, $center);
                                        //$center = str_replace('#country_iso#', $country_iso, $center);
                                        //$center = str_replace('#language_iso#', $lang_iso, $center);
                                        //$center = str_replace('#language_locale#', $language_locale, $center);
                                        if ($only_first == 0) {
                                            //clean
                                            $center = preg_replace('/#carrier_price_with_tax_[\w]+#/', '', $center);
                                            $center = preg_replace('/#carrier_price_without_tax_[\w]+#/', '', $center);
                                            $center = preg_replace('/#carrier_price_tax_[\w]+#/', '', $center);
                                            $list[] = $center;
                                        } else {
                                            $template = $center;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            return array($template);
        }
        if ($only_first == 1) {
            //clean
            $template = preg_replace('/#carrier_price_with_tax_[\w]+#/', '', $template);
            $template = preg_replace('/#carrier_price_without_tax_[\w]+#/', '', $template);
            $template = preg_replace('/#carrier_price_tax_[\w]+#/', '', $template);
            $list[] = $template;
        }
        $list = array_unique(array_filter($list));
        return $list;
    }

    public function processText($description, $clear_html = 0, $decode = 1, $re_encode = 0, $remove_char = "")
    {
        if ($decode) {
            $description = trim($this->model_extension_feed_google_merchant_center->decodeChars($description));
        }
        if ($clear_html) {
            $description= str_replace("
            ", " ", str_replace("\n", " ", str_replace("\t", " ", str_replace("\r", " ", str_replace("\r\n", " ", $this->model_extension_feed_google_merchant_center->strip_html_tags($description))))));
            while ($this->strpos($description, "  ") !== false) {
                $description=str_replace("  ", " ", $description);
            }
            $description=trim($description);
            while ($this->model_extension_feed_google_merchant_center->startsWith($description, " ") || $this->model_extension_feed_google_merchant_center->endsWith($description, " ")) {
                $description = $this->model_extension_feed_google_merchant_center->clearDescription($description, " ");
            }
        }
        if ($remove_char !== "") {
            $description = preg_replace($remove_char, '' , $description);
        }
        if ($re_encode) {
            $description = htmlspecialchars($description);
        }
        return $description;
    }

    public function setCustomProduct($product = array())
    {
        if ($product !== null) {
            if (empty($product)) {
                $product = array(
                    array(
                        'cart_id' => '0',
                        'product_id' => '0',
                        'name' => '',
                        'model' => '',
                        'shipping' => '1',
                        'image' => '',
                        'quantity' => "1",
                        'minimum' => "1",
                        'subtract' => "0",
                        'stock' =>  true,
                        'price' =>  1,
                        'total' =>  1,
                        'reward' => 0,
                        'points' =>  0 ,
                        'tax_class_id' => 0,
                        'weight' => 0,
                        'weight_class_id' => "0",
                        'length' => "0.00000000",
                        'width' => "0.00000000",
                        'height' => "0.00000000",
                        'length_class_id' => "0",
                        'recurring' => false,
                        'download' => array(),
                        'option' => array(
                            array(
                                'product_option_id' => "0",
                                'product_option_value_id' => "0",
                                'option_id' => "0",
                                'option_value_id' => "0",
                                'name' => "",
                                'value' => "",
                                'type' => "select",
                                'quantity' => "1",
                                'subtract' => "0",
                                'price' => "0.0000",
                                'price_prefix' => "+",
                                'points' => "0",
                                'points_prefix' => "+",
                                'weight' => "0.00000000",
                                'weight_prefix' => "+"
                            )
                        )
                    )
                );
            } else {
                $product[0]['quantity'] = $product[0]['minimum'];
                $product[0]['option'][0]['quantity'] = $product[0]['minimum'];
            }
        }
        $this->cart->setCustomProduct($product);
    }

/*
#shop_title#
#shop_description#
#shop_domain#
#shop_url#
#date#
#datetime#
*/
    public function clearCarrierTemplate($template)
    {
        $template = str_replace('#carrier_country_iso#', '', $template);
        $template = str_replace('#carrier_country_name#', '', $template);
        $template = str_replace('#carrier_zone_iso#', '', $template);
        $template = str_replace('#carrier_zone_name#', '', $template);
        $template = str_replace('#carrier_code#', '', $template);
        $template = str_replace('#carrier_name#', '', $template);
        $template = str_replace('#carrier_price_with_tax#', '', $template);
        $template = str_replace('#carrier_price_without_tax#', '', $template);
        $template = str_replace('#carrier_price_tax#', '', $template);
        return $template;
    }

    public function clearCurrencyTemplate($template)
    {
        $template = str_replace('#currency_iso#', '', $template);
        $template = str_replace('#currency_rate#', '', $template);
        $template = str_replace('#currency_name#', '', $template);
        $template = str_replace('#currency_symbol_right#', '', $template);
        $template = str_replace('#currency_symbol_left#', '', $template);
        //$template = str_replace('#currency_decimal_place#', '', $template);
        return $template;
    }

    public function clearReviewTemplate($template)
    {
        $template = str_replace('#product_review_list#', '', $template);
        $template = str_replace('#rating#', '', $template);
        $template = str_replace('#rating_count#', '', $template);
        $template = str_replace('#review_rating#', '', $template);
        $template = str_replace('#review_id#', '', $template);
        $template = str_replace('#review#', '', $template);
        $template = str_replace('#review_author#', '', $template);
        $template = str_replace('#review_date#', '', $template);
        return $template;
    }

    public function clearManufacturerTemplate($template)
    {
        $template = str_replace('#manufacturer_id#', '', $template);
        $template = str_replace('#manufacturer_name#', '', $template);
        $template = str_replace('#manufacturer_link#', '', $template);
        return $template;
    }

    public function clearCategoryTemplate($template)
    {
        $template = str_replace('#product_category_start#', '', $template);
        $template = str_replace('#product_category_end#', '', $template);
        $template = str_replace('#product_category_list#', '', $template);
        $template = str_replace('#category_id#', '', $template);
        $template = str_replace('#parent_id#', '', $template);
        $template = str_replace('#category_meta_keywords#', '', $template);
        $template = str_replace('#category_meta_description#', '', $template);
        $template = str_replace('#category_meta_name#', '', $template);
        $template = str_replace('#category_description#', '', $template);
        $template = str_replace('#category_name#', '', $template);
        $template = str_replace('#category_link#', '', $template);
        return $template;
    }

    public function clearAttributeTemplate($template)
    {
        //clean
        $template = str_replace('#product_attributes_start#', '', $template);
        $template = str_replace('#product_attributes_end#', '', $template);
        $template = str_replace('#product_attributes_list#', '', $template);
        $template = str_replace('#attribute_value#', '', $template);
        $template = str_replace('#attribute_name#', '', $template);
        $template = str_replace('#attribute_group#', '', $template);
        $template = str_replace('#attribute_id#', '', $template);
        $template = str_replace('#attribute_group_id#', '', $template);
        return $template;
    }

    public function clearOptionTemplate($template)
    {
        $template = str_replace('#option_id#', '', $template);
        $template = str_replace('#option_value_id#', '', $template);
        $template = str_replace('#option_name#', '', $template);
        $template = str_replace('#option_value#', '', $template);
        $template = str_replace('#variations#', '', $template);

        $template = str_replace('#id#', '', $template);
        $template = str_replace('#product_link#', '', $template);
        $template = str_replace('#full_price_without_tax#', '', $template);
        $template = str_replace('#full_price_with_tax#', '', $template);
        $template = str_replace('#full_price_tax#', '', $template);
        $template = str_replace('#sale_price_without_tax#', '', $template);
        $template = str_replace('#sale_price_with_tax#', '', $template);
        $template = str_replace('#sale_price_tax#', '', $template);
        $template = str_replace('#price_without_tax#', '', $template);
        $template = str_replace('#price_with_tax#', '', $template);
        $template = str_replace('#price_tax#', '', $template);
        $template = str_replace('#sales_date#', '', $template);
        $template = str_replace('#sales_start_date#', '', $template);
        $template = str_replace('#sales_end_date#', '', $template);
        $template = str_replace('#option_quantity#', '', $template);
        $template = str_replace('#availability#', '', $template);
        return $template;
    }

    public function clearProductTemplate($template)
    {
        $template = str_replace('#quantity#', '', $template);
        $template = str_replace('#product_id#', '', $template);
        $template = str_replace('#google_category_name#', '', $template);
        $template = str_replace('#google_category_id#', '', $template);
        $template = str_replace('#custom_category_name#', '', $template);
        $template = str_replace('#custom_category_id#', '', $template);
        $template = str_replace('#weight#', '', $template);
        $template = str_replace('#weight_unit#', '', $template);
        $template = str_replace('#gtin#', '', $template);
        $template = str_replace('#sku#', '', $template);
        $template = str_replace('#upc#', '', $template);
        $template = str_replace('#ean#', '', $template);
        $template = str_replace('#jan#', '', $template);
        $template = str_replace('#isbn#', '', $template);
        $template = str_replace('#product_description#', '', $template);
        $template = str_replace('#manufacturer_name#', '', $template);
        $template = str_replace('#manufacturer_id#', '', $template);
        $template = str_replace('#product_name#', '', $template);
        $template = str_replace('#model#', '', $template);
        $template = str_replace('#currency_iso#', '', $template);
        $template = str_replace('#color#', '', $template);
        $template = str_replace('#gender#', '', $template);
        $template = str_replace('#age_group#', '', $template);
        $template = str_replace('#main_image#', '', $template);
        $template = str_replace('#additional_image_link#', '', $template);
        $template = str_replace('#additional_image_index#', '', $template);
        $template = str_replace('#product_images_start#', '', $template);
        $template = str_replace('#product_images_end#', '', $template);
        $template = str_replace('#product_images_list#', '', $template);
        $template = str_replace('#mpn#', '', $template);

        $template = str_replace('#product_option_start#', '', $template);
        $template = str_replace('#product_option_end#', '', $template);
        $template = str_replace('#product_option_list#', '', $template);
        $template = str_replace('#option_start#', '', $template);
        $template = str_replace('#option_end#', '', $template);
        $template = str_replace('#option_list#', '', $template);
        $template = str_replace('#product_start#', '', $template);
        $template = str_replace('#product_end#', '', $template);
        $template = str_replace('#product_list#', '', $template);

        $template = str_replace('#country_iso#', '', $template);
        $template = str_replace('#language_iso#', '', $template);
        $template = str_replace('#language_locale#', '', $template);
        return $template;
    }

    public function clearTemplate($template)
    {
        $template = $this->clearCarrierTemplate($template);
        $template = $this->clearCurrencyTemplate($template);
        $template = $this->clearReviewTemplate($template);
        $template = $this->clearManufacturerTemplate($template);
        $template = $this->clearCategoryTemplate($template);
        $template = $this->clearAttributeTemplate($template);
        $template = $this->clearOptionTemplate($template);
        $template = $this->clearProductTemplate($template);
        //meomory test var_dump(round(memory_get_usage()/1048576,2));
        return $template;
    }

    private function get($config, $default = null)
    {
        $value = $this->config->get($config);
        return ($value === null ? $default : $value);
    }

    private function processCalculate($template)
    {
        $start = $this->strpos($template, '#calculate#');
        while ($start !== false) {
            $len = strlen('#calculate#');
            $end = $this->strpos($template, '#calculate#', $start+$len);
            $equation = substr($template, $start+$len, $end-($start+$len));
            $equation = $this->calculate($equation);
            $template = substr($template, 0, $start).$equation.substr($template, $end+$len);
            $start = $this->strpos($template, '#calculate#');
        }
        return $template;
    }

    private function calculate($equation)
    {
        //fix small issues
        $equation = str_replace(',', '.', $equation);
        $equation = str_replace(' ', '', $equation);
        $result = (float)$equation;
        //calculate / *
        while(preg_match('/(\-?[\d\.\s]+)([\*\/])(\-?[\d\.\s]+)/', $equation, $matches) !== false && count($matches) === 4){
            $operator = $matches[2];
            switch($operator) {
                case '*':
                    $result = (float)$matches[1] * (float)$matches[3];
                    break;
                case '/':
                    $result = (float)$matches[1] / (float)$matches[3];
                    break;
            }
            //replace the partial result
            $pos = $this->strpos($equation, $matches[0]);
            if ($pos !== false && $equation !== $matches[0]) {
                $equation = substr_replace($equation, $result, $pos, strlen($matches[0]));
            } else {//it's finished or something went wrong, anyway return result
                return $result;
            }
        }
        //calculate +-
        while(preg_match('/(\-?[\d\.\s]+)([\+\-])(\-?[\d\.\s]+)/', $equation, $matches) !== false && count($matches) === 4){
            $operator = $matches[2];
            switch($operator){
                case '+':
                    $result = (float)$matches[1] + (float)$matches[3];
                    break;
                case '-':
                    $result = (float)$matches[1] - (float)$matches[3];
                    break;
            }
            //replace the partial result
            $pos = $this->strpos($equation, $matches[0]);
            if ($pos !== false && $equation !== $matches[0]) {
                $equation = substr_replace($equation, $result, $pos, strlen($matches[0]));
            } else {//it's finished or something went wrong, anyway return result
                return $result;
            }
        }
        return $result;
    }

    private function processRound($template)
    {
        $start = $this->strpos($template, '#round#');
        while ($start !== false) {
            $len = strlen('#round#');
            $end = $this->strpos($template, '#round#', $start+$len);
            $explode = explode('#precision#', substr($template, $start+$len, $end-($start+$len)));
            if (count($explode) === 2) {
                //fix small issues
                $num = str_replace(',', '.', $explode[1]);
                $num = str_replace(' ', '', $num);
                //round
                $result = round((float)$num, (int)$explode[0]);
            } else {
                $result = round(reset($explode));
            }
            $template = substr($template, 0, $start).$result.substr($template, $end+$len);
            $start = $this->strpos($template, '#round#');
        }
        return $template;
    }

    public function getLocaleIso($lang_iso, $iso_country = null)
    {
        $language_iso = preg_split('/[-_]/', $lang_iso);
        $language_iso = reset($language_iso);
        if ($iso_country === null) {
            return $language_iso;
        }
        return strtolower($lang_iso).'_'.strtoupper($iso_country);
    }

    public function strpos($str, $find, $offset = 0, $encoding = 'UTF-8')
    {
        /*if (function_exists('mb_strpos')) {
            return mb_strpos($str, $find, $offset, $encoding);
        }*/
        return strpos($str, $find, $offset);
    }
}
