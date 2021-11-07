<?php
class ModelExtensionFeedGoogleMerchantCenter extends Model {
	public function getTaxonomy($category_id)
    {
        if ($this->hasTable(DB_PREFIX."feed_manager_taxonomy") == 0) {
            return array();
        }
        $query = $this->db->query("SELECT `".DB_PREFIX."feed_manager_taxonomy`.`taxonomy_id`,`".DB_PREFIX."feed_manager_taxonomy`.`name` FROM `".DB_PREFIX."feed_manager_category` RIGHT JOIN `".DB_PREFIX."feed_manager_taxonomy` ON `".DB_PREFIX."feed_manager_category`.`taxonomy_id`=`".DB_PREFIX."feed_manager_taxonomy`.`taxonomy_id` WHERE `".DB_PREFIX."feed_manager_category`.`category_id` = '".$category_id."' OR `".DB_PREFIX."feed_manager_taxonomy`.`status` = '1' ORDER BY LENGTH(`".DB_PREFIX."feed_manager_taxonomy`.`name`) DESC LIMIT 1;");
        return $query->row;
    }

    public function getTaxonomyCustom($category_id, $feed_name)
    {
        if ($this->hasTable(DB_PREFIX."feed_manager_custom_category") != 0) {
            $query = $this->db->query("SELECT `taxonomy_id`,`taxonomy_name` FROM `".DB_PREFIX."feed_manager_custom_category` WHERE `category_id` = '".$category_id."' AND `feed_name` = '".$feed_name."' LIMIT 1;");
            if ($query->row) {
                return $query->row;
            }
        }
        return array('taxonomy_id' => '', 'taxonomy_name' => '');
    }

    public function getProductTabData($product_id, $lang_id)
    {
        if ($this->hasTable(DB_PREFIX."feed_manager_product") == 0) {
            return array('color'=>'', 'age_group'=>'adult', 'gender'=>'unisex');
        } else {//has table installed - should be always
            $query = $this->db->query("SELECT `gtp`.`color`, `gtp`.`age_group`, `gtp`.`gender` FROM `".DB_PREFIX."feed_manager_product` AS `gtp` WHERE `gtp`.`product_id` = '".(int)$product_id."';");
        }

        if (isset($query) && $query->rows) {
            return $query->row;
        }
        return array('color'=>'', 'age_group'=>'adult', 'gender'=>'unisex');
    }

    public function getProductAttributes($product_id, $attributes, $lang_id)
    {
        if (!empty($attributes)) {
            $query = $this->db->query("SELECT `text`,`attribute_id` FROM `".DB_PREFIX."product_attribute` AS `pa` WHERE `pa`.`product_id` = '".(int)$product_id."' AND `pa`.`language_id` = '".(int)$lang_id."' AND (`pa`.`attribute_id` = '".implode("' OR `pa`.`attribute_id` = '",$attributes)."');");
            $attribute = array();
            foreach ($query->rows as $value) {
                $attribute[$value['attribute_id']]=$value['text'];
            }
            return $attribute;
        }
        return array();
    }

    public function getAllProductAttributes($product_id, $lang_id) {
		$query=$this->db->query(
            "SELECT `pa`.`attribute_id` AS `attribute_id`,`agd`.`attribute_group_id` AS `attribute_group_id`,`pa`.`text` AS `value`,`ad`.`name` AS `name`,`agd`.`name` AS `group_name`
            FROM `".DB_PREFIX."product_attribute` AS `pa`
            LEFT JOIN `".DB_PREFIX."attribute` AS `a`
            ON `pa`.`attribute_id` = `a`.`attribute_id`
            LEFT JOIN `".DB_PREFIX."attribute_description` AS `ad`
            ON `a`.`attribute_id` = `ad`.`attribute_id`
            LEFT JOIN `".DB_PREFIX."attribute_group_description` AS `agd`
            ON `agd`.`attribute_group_id` = `a`.`attribute_group_id`
            WHERE `pa`.`product_id` = '".(int)$product_id."' AND `pa`.`language_id` = '".(int)$lang_id."' AND `ad`.`language_id` = '".(int)$lang_id."' AND `agd`.`language_id` = '".(int)$lang_id."' GROUP BY `attribute_id`;");
		return $query->rows;
	}

    public function getTaxonomyName($taxonomy_id)
    {
        if ($this->hasTable(DB_PREFIX."feed_manager_taxonomy") == 0) {
            return "";
        }
        $query = $this->db->query("SELECT `name` FROM `".DB_PREFIX."feed_manager_taxonomy` WHERE `".DB_PREFIX."feed_manager_taxonomy`.`taxonomy_id` = '".$taxonomy_id."';");
        return ($query->num_rows == 0 ? '' : $query->row['name']);
    }

    public function getCategories($lang_id, $shop_id, $white_category_id = array(), $black_category_id = array(), $parent_id = 0) {
		$query = $this->db->query(
            "SELECT * FROM " . DB_PREFIX . "category c
            LEFT JOIN " . DB_PREFIX . "category_description cd
            ON (c.category_id = cd.category_id)
            LEFT JOIN " . DB_PREFIX . "category_to_store c2s
            ON (c.category_id = c2s.category_id)
            WHERE c.parent_id = '".(int)$parent_id."'
            AND cd.language_id = '".(int)$lang_id."'
            AND c2s.store_id = '".(int)$shop_id."'
            AND c.status = '1' ORDER BY c.sort_order, LCASE(cd.name)");
            $list = array();
            foreach ($query->rows as $key => $value) {
                if (!in_array($value['category_id'], $black_category_id) && (empty($white_category_id) || in_array($value['category_id'], $white_category_id))) {
                    $list[$value['parent_id']][$value['category_id']] = $value;
                    $list = array_merge($list, $this->getCategories($lang_id, $shop_id, $white_category_id, $black_category_id, $value['category_id']));
                }
            }

		return $list;
	}

    public function getProductExtraType($product_id,$attribute_id,$lang)
    {
        $query = $this->db->query("SELECT `pa`.`text` FROM `".DB_PREFIX."product_attribute` AS `pa` WHERE `pa`.`product_id` = '".(int)$product_id."' AND `pa`.`attribute_id` = '".(int)$attribute_id."' AND `pa`.`language_id` = '".$lang."'  LIMIT 1;");
        if ($query->rows)
            return $query->row['text'];
    }

    public function isApparel($taxonomy_id)
    {
        if ($this->hasTable(DB_PREFIX."feed_manager_taxonomy") == 0) {
            return false;
        }
        $query = $this->db->query("SELECT count(*) AS `count` FROM `".DB_PREFIX."feed_manager_taxonomy` WHERE ".DB_PREFIX."feed_manager_taxonomy.`taxonomy_id` = '".$taxonomy_id."' AND `".DB_PREFIX."feed_manager_taxonomy`.`name` LIKE 'Apparel & Accessories%';");
        return ($query->row['count'] > 0);
    }

    public function getOptions($product_id, $options, $lang_id)
    {
        $option_data = $this->getProductOptions($product_id, $options, $lang_id);
        $option_combos=array();
        $product_options=array();
        $not_required_option = array();
        foreach ($option_data as $option) {
            $option_id = $option['option_id'];
            $option_value_id = $option['option_value_id'];
            $option_combos[$option_id][$option_value_id]=$option_value_id;
            $product_options[$option_value_id] = $option;
            if ($option['required'] == '0' && !array_key_exists($option_id, $not_required_option)) {
                $not_option =
                array(
                    'name' => '',//x
                    'price_prefix' => '+',
                    'price' => 0,
                    'quantity' => 0,
                    'type' => '',
                    'required' => 0,
                    'option_id' => $option_id,
                    'subtract' => 0,
                    'weight_prefix' => '+',
                    'weight' => 0,
                    'option_value_id' => '',//0
                    'product_option_value_id' => '',
                    'product_option_id' => '',
                    'option_name' => ''
                );
                $not_required_option[$option_id] = $not_option;
            }
        }

        if (!empty($not_required_option)) {//not required options
            foreach ($not_required_option as $key => $value) {
                $option_combos[$key]['_'.$key]='_'.$key;
                $product_options['_'.$key] = $value;
            }
        }

        $option_combos = array_values($option_combos);
        $option_count = count($option_combos);
        $combos = array();

        for ($i = 0; $i < $option_count; $i++) {
            if ($i == 0) {
                $combos = $option_combos[$i];
            }
            if ($i+1 < $option_count) {
                $combos=$this->model_extension_feed_google_merchant_center->getOptionCombination($combos, $option_combos[$i+1]);
            }
        }
        foreach ($combos as $key => $value) {
            $option_combo = explode('-',$key);
            foreach ($option_combo as $option_value_id) {
                if (stripos($key, '-')===false) {
                    $combos[$key]=array($key => $key);
                }
                $combos[$key][$option_value_id]=$product_options[$option_value_id];
            }
        }
        return $combos;
    }

    public function getProductOptions($product_id, $options, $lang_id)
    {
        if (!empty($options)) {
            //get upc,image column
            $upc_column = '';
            $img_column = '';
            $columns = array();
            $columns_query = $this->db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".DB_PREFIX."product_option_value';");
            foreach($columns_query->rows as $column) {
                $columns[] = $column['COLUMN_NAME'];
            }
            if (in_array('upc', $columns)) {
                $upc_column = "`pov`.`upc` as `upc`,";
            } elseif (in_array('mp_optionean', $columns)) {
                $upc_column = "`pov`.`mp_optionean` as `upc`,";
            }
            if (in_array('image', $columns)) {
                $img_column = "`pov`.`image` as `image`,";
            } elseif (in_array('opt_image', $columns)) {
                $img_column = "`pov`.`opt_image` as `image`,";
            } elseif (in_array('ob_image', $columns)) {
                $img_column = "`pov`.`ob_image` as `image`,";
            }
            $query = $this->db->query("SELECT
            `ovd`.`name`,
            `pov`.`price_prefix`,
            `pov`.`price`,
            `pov`.`quantity`,
            `pov`.`option_id`,
            `pov`.`subtract`,
            `pov`.`weight_prefix`,
            `pov`.`weight`,
            `pov`.`option_value_id`,
            `pov`.`product_option_value_id`,
            `pov`.`product_option_id`,
            ".$upc_column.$img_column."
            `o`.`type`,
            `po`.`required`,
            `od`.`name` AS `option_name`
            FROM `".DB_PREFIX."product_option_value` AS `pov`
            INNER JOIN `".DB_PREFIX."option_value_description` AS `ovd`
            ON `ovd`.`option_value_id` = `pov`.`option_value_id`
            INNER JOIN `".DB_PREFIX."option` AS `o`
            ON `pov`.`option_id` = `o`.`option_id`
            INNER JOIN `".DB_PREFIX."product_option` AS `po`
            ON `pov`.`product_option_id` = `po`.`product_option_id`
            INNER JOIN `".DB_PREFIX."option_description` AS `od`
            ON `od`.`option_id` = `o`.`option_id`
            WHERE `pov`.`product_id` = '".(int)$product_id."'
            AND `ovd`.`language_id` = '".(int)$lang_id . "'
            AND `od`.`language_id` = '".(int)$lang_id . "'
            AND (`pov`.`option_id` = '".implode("' OR `pov`.`option_id` = '", $options)."')
            ORDER BY `pov`.`option_value_id`;");//AND (pov.subtract = '0' OR pov.quantity > 0)
            if ($query->rows) {
                return $query->rows;
            }
        }
        return array(
            array(
            'name' => '',
            'price_prefix' => '+',
            'price' => 0,
            'quantity' => 0,
            'type' => '',
            'required' => 1,
            'option_id' => '',
            'subtract' => 0,
            'weight_prefix' => '+',
            'weight' => 0,
            'option_value_id' => '',
            'product_option_value_id' => '',
            'product_option_id' => '',
            'option_name' => ''
            )
        );
    }

    public function combineOptions($options1, $options2)
    {
        $combine = array();
        foreach ($options1 as $key1 => $value1) {
            foreach ($options2 as $key2 => $value2) {
                $id = $key1.'-'.$key2;
                $combine[$id] = array($key1 => $value1, $key2 => $value2);
            }
        }
        return $combine;
    }

    public function getOptionCombination($options1, $options2)
    {
        $combine = array();
        foreach ($options1 as $key1 => $value1) {
            foreach ($options2 as $key2 => $value2) {
                $id = $key1.'-'.$key2;
                $combine[$id] = null;
            }
        }
        return $combine;
    }

    public static function setParameterURL($url, $parameter, $parameterValue)
    {
        $url = parse_url($url);
        parse_str(html_entity_decode($url["query"]), $parameters);
        unset($parameters[$parameter]);
        $parameters[$parameter] = $parameterValue;
        return $url["path"]."?".urldecode(http_build_query($parameters));
    }

    public function getLowestPriceOption($product_id)
    {
        $query = $this->db->query("SELECT MIN(`".DB_PREFIX."product_option_value`.`price`) AS `price` FROM `".DB_PREFIX."product_option_value`
        WHERE `".DB_PREFIX."product_option_value`.`product_id` = '".(int)$product_id."' AND (`".DB_PREFIX."product_option_value`.`subtract` = '0' OR `".DB_PREFIX."product_option_value`.`quantity` > 0) AND `".DB_PREFIX."product_option_value`.`price_prefix` = '+' AND `".DB_PREFIX."product_option_value`.`price` > 0
        GROUP BY `".DB_PREFIX."product_option_value`.`product_id`
        ORDER BY `".DB_PREFIX."product_option_value`.`price` ASC LIMIT 1");
        if ($query->rows)
            return $query->row['price'];
        return 0;
    }

    public function getTax($only_percentage = true)
    {
        $query = $this->db->query("SELECT `iso_code_2`,`iso_code_3`,`rate`,`c`.`name`,`type` FROM `".DB_PREFIX."tax_rate` AS `tr` LEFT JOIN `".DB_PREFIX."zone_to_geo_zone` AS `tgz` ON `tr`.`geo_zone_id`=`tgz`.`geo_zone_id` RIGHT JOIN `".DB_PREFIX."country` AS `c` ON `c`.`country_id`=`tgz`.`country_id` WHERE ".($only_percentage ? "`type` = 'P'" : "`rate` IS NOT NULL")." GROUP BY `c`.`iso_code_3`;");
        return $query->rows;
    }

    public function getShipping()
    {
        $query = $this->db->query("SELECT `c`.`iso_code_2` FROM `".DB_PREFIX."zone_to_geo_zone` AS `ztgz` LEFT JOIN `".DB_PREFIX."country` AS `c` ON (`c`.`country_id`=`ztgz`.`country_id`) GROUP BY `c`.`iso_code_2`;");
        return $query->rows;
    }

    public function getLangID($lang_code)
    {
        $query = $this->db->query("SELECT `language_id` FROM `".DB_PREFIX."language` WHERE `code` = '".$lang_code."' ORDER BY `status` DESC;");
        return isset($query->row['language_id']) ? $query->row['language_id'] : '';
    }

    public function getProductCount($store)
    {
        $sql = "SELECT `p`.`product_id` FROM `".DB_PREFIX."product` AS `p`";
        $sql .= " LEFT JOIN `".DB_PREFIX."product_to_store` AS `p2s` ON (`p`.`product_id` = `p2s`.`product_id`) WHERE `p`.`date_available` <= NOW() AND `p2s`.`store_id` = '" . (int)$store . "'";
        $sql .= " GROUP BY `p`.`product_id`";

        $query = $this->db->query($sql);
        return count($query->rows);
    }

    public function getProducts($lang, $store, $start = 0, $limit = 1000)
    {
        $customer_group = (int)$this->config->get('config_customer_group_id');
        if (isset($_GET['customer_group'])) {
            $customer_group = (int)$_GET['customer_group'];
        }
        $sql = "SELECT `p`.`product_id` FROM `".DB_PREFIX."product` AS `p`";
        $sql .= " LEFT JOIN `".DB_PREFIX."product_description` AS `pd` ON (`p`.`product_id` = `pd`.`product_id`) LEFT JOIN `".DB_PREFIX."product_to_store` AS `p2s` ON (`p`.`product_id` = `p2s`.`product_id`) WHERE `pd`.`language_id` = '" . (int)$lang . "' AND `p`.`date_available` <= NOW() AND `p2s`.`store_id` = '" . (int)$store . "'";
        $sql .= " GROUP BY `p`.`product_id` ORDER BY `p`.`product_id` ASC";
        if ($start < 0) {
            $start = 0;
        }
        if ($limit < 1) {
            $limit = 1000;
        }
        $sql .= " LIMIT " . (int)$start . "," . (int)$limit;
        $product_data = array();
        $query = $this->db->query($sql);
        foreach ($query->rows as $result) {
            $product_data[$result['product_id']] = $this->getProduct($result['product_id'],$lang,$store,$customer_group);
        }
        return $product_data;
    }

    public function getProduct($product_id, $lang, $store, $customer_group, $quantity = 1)
    {
        $query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image, m.name AS manufacturer, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . $customer_group . "' AND pd2.quantity <= ".$quantity." AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . $customer_group . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT date_start FROM " . DB_PREFIX . "product_special ds WHERE ds.product_id = p.product_id AND ds.customer_group_id = '" . $customer_group . "' AND ((ds.date_start = '0000-00-00' OR ds.date_start < NOW()) AND (ds.date_end = '0000-00-00' OR ds.date_end > NOW())) ORDER BY ds.priority ASC, ds.price ASC LIMIT 1) AS date_start, (SELECT date_end FROM " . DB_PREFIX . "product_special de WHERE de.product_id = p.product_id AND de.customer_group_id = '" . $customer_group . "' AND ((de.date_start = '0000-00-00' OR de.date_start < NOW()) AND (de.date_end = '0000-00-00' OR de.date_end > NOW())) ORDER BY de.priority ASC, de.price ASC LIMIT 1) AS date_end, (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND customer_group_id = '" . $customer_group . "' LIMIT 1) AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$lang . "' LIMIT 1) AS stock_status, (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int)$lang . "' LIMIT 1) AS weight_class, (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int)$lang . "' LIMIT 1) AS length_class, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id LIMIT 1) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id LIMIT 1) AS reviews, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$lang . "' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$store . "'");
        if ($query->num_rows) {
            return array(
                'product_id'       => $query->row['product_id'],
                'name'             => $query->row['name'],
                'description'      => $query->row['description'],
                'meta_title'       => (array_key_exists('meta_title', $query->row) ? $query->row['meta_title'] : ''),
                'meta_description' => $query->row['meta_description'],
                'meta_keyword'     => $query->row['meta_keyword'],
                'tag'              => $query->row['tag'],
                'model'            => $query->row['model'],
                'sku'              => $query->row['sku'],
                'upc'              => $query->row['upc'],
                'ean'              => $query->row['ean'],
                'jan'              => $query->row['jan'],
                'isbn'             => $query->row['isbn'],
                'mpn'              => $query->row['mpn'],
                'location'         => $query->row['location'],
                'quantity'         => $query->row['quantity'],
                'stock_status'     => $query->row['stock_status'],
                'image'            => $query->row['image'],
                'manufacturer_id'  => $query->row['manufacturer_id'],
                'manufacturer'     => $query->row['manufacturer'],
                'price'            => $query->row['price'],
                'special'          => ($query->row['special'] ? $query->row['special'] : $query->row['discount']),
                'date_start'       => $query->row['date_start'],
                'date_end'         => $query->row['date_end'],
                'reward'           => $query->row['reward'],
                'points'           => $query->row['points'],
                'tax_class_id'     => $query->row['tax_class_id'],
                'date_available'   => $query->row['date_available'],
                'weight'           => $query->row['weight'],
                'weight_class_id'  => $query->row['weight_class_id'],
                'length'           => $query->row['length'],
                'width'            => $query->row['width'],
                'height'           => $query->row['height'],
                'length_class_id'  => $query->row['length_class_id'],
                'subtract'         => $query->row['subtract'],
                'rating'           => round($query->row['rating']),
                'reviews'          => $query->row['reviews'] ? $query->row['reviews'] : 0,
                'minimum'          => $query->row['minimum'],
                'sort_order'       => $query->row['sort_order'],
                'status'           => $query->row['status'],
                'date_added'       => $query->row['date_added'],
                'date_modified'    => $query->row['date_modified'],
                'viewed'           => $query->row['viewed'],
                'shipping'         => $query->row['shipping']
            );
        } else {
            return false;
        }
    }

    public function getCategory($category_id,$lang,$store)
    {
        $query = $this->db->query("SELECT DISTINCT * FROM `".DB_PREFIX."category` AS `c` LEFT JOIN `".DB_PREFIX."category_description` AS `cd` ON (`c`.`category_id` = `cd`.`category_id`) LEFT JOIN `".DB_PREFIX."category_to_store` AS `c2s` ON (`c`.`category_id` = `c2s`.`category_id`) WHERE `c`.`category_id` = '" . (int)$category_id . "' AND `cd`.`language_id` = '" . (int)$lang. "' AND `c2s`.`store_id` = '" . (int)$store . "' AND `c`.`status` = '1'");
        return $query->row;
    }

    public function hasTable($tableName)
    {
        $query=$this->db->query("SHOW tables LIKE '".$tableName."';");
        return count($query->rows);
    }

    public function getImages($product_id, $main_image)
    {
        $main_image = addslashes($main_image);
        $query = $this->db->query("SELECT DISTINCT `image` FROM `".DB_PREFIX."product_image` WHERE `product_id` = '".(int)$product_id."' AND `image` != '".$main_image."' ORDER BY `sort_order` ASC");
        return $query->rows;
    }

    //moved from the feed file
    public function getPath($parent_id, $lang_id, $store_id, $current_path = '')
    {
        $category_info = $this->getCategory($parent_id, $lang_id, $store_id);
        if ($category_info) {
            $path="";
            if (!$current_path) {
                $new_path = $category_info['category_id'];
            } else {
                $new_path = $category_info['category_id'] . '_' . $current_path;
            }
            if ($parent_id != $category_info['parent_id']) {
                $path = $this->getPath($category_info['parent_id'], $lang_id, $store_id, $new_path);
            }

            if ($path) {
                return $path;
            } else {
                return $new_path;
            }
        }
    }

    public function getImageUrl($image, $width, $height, $enable_image_cache, $base_url)
    {
        if ((int)$enable_image_cache === 0) {
            $image_url = $base_url.$image;
        } else {
            $image_path = 'cache/'.utf8_substr($image, 0, utf8_strrpos($image, '.')).'-'.(int)$width.'x'.(int)$height.'.'.pathinfo($image, PATHINFO_EXTENSION);
            if ((int)$enable_image_cache && file_exists(DIR_IMAGE.$image_path)) {
                $image_url = $base_url.$image_path;
            } else {
                $image_url = $this->model_tool_image->resize($image, $width, $height);
            }
        }
        $image_url = str_replace('&', '%26', $image_url);
        $image_url = str_replace(' ', '%20', $image_url);
        $image_url = htmlspecialchars($image_url, ENT_COMPAT, 'UTF-8');
        if (strpos($image_url, 'http') === false) {
            $image_url = $base_url.$image_url;
        }
        return $image_url;
    }

    public function getOptionImage($module, $product_option_value_id)
    {
        //check other tables - extern modules
        if ($module != null && !empty($product_option_value_id)) {
            if ($module == 'uber_options') { //Uber Options
                $query = $this->db->query("SELECT `uo_value` FROM `".DB_PREFIX."uber_options` WHERE `uo_pov_id` = '".(int)$product_option_value_id."' AND `uo_key` = 'uo_image_image' LIMIT 1");
                if ($query->rows) {
                    $image = $query->row['uo_value'];
                    if (!empty($image)) {
                        return $image;
                    }
                }
            } elseif ($module == 'poip_option_image') { //Product option image PRO
                $query = $this->db->query("SELECT `image` FROM `".DB_PREFIX."poip_option_image` WHERE `product_option_value_id` = '".(int)$product_option_value_id."' ORDER BY `sort_order` ASC LIMIT 1");
                if ($query->rows) {
                    $image = $query->row['image'];
                    if (!empty($image)) {
                        return $image;
                    }
                }
            }
        }
        return null;
    }

    public function checkTableExist($table_name)
    {
        $query = $this->db->query("SELECT * FROM information_schema.tables WHERE table_name = '".DB_PREFIX.$table_name."' LIMIT 1");
        return ($query->rows ? 1 : 0);
    }

    public function strip_html_tags($text)
    {
        $text = preg_replace(
        array(
          // Remove invisible content
            '@<head[^>]*?>.*?</head>@siu',
            '@<style[^>]*?>.*?</style>@siu',
            '@<script[^>]*?.*?</script>@siu',
            '@<object[^>]*?.*?</object>@siu',
            '@<embed[^>]*?.*?</embed>@siu',
            '@<applet[^>]*?.*?</applet>@siu',
            '@<noframes[^>]*?.*?</noframes>@siu',
            '@<noscript[^>]*?.*?</noscript>@siu',
            '@<noembed[^>]*?.*?</noembed>@siu',
          // Add line breaks before and after blocks
            '@</?((address)|(blockquote)|(center)|(del))@iu',
            '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
            '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
            '@</?((table)|(th)|(td)|(caption))@iu',
            '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
            '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
            '@</?((frameset)|(frame)|(iframe))@iu',
        ),
        array(
            ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
            "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
            "\n\$0", "\n\$0",
        ),
        $text
        );
        return strip_tags($text);
    }

    public function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && strpos($haystack, $needle) === 0) {
                return true;
            }
        }
        return false;
    }

    public function endsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle === substr($haystack, -strlen($needle))) {
                return true;
            }
        }
        return false;
    }

    public static function clearDescription($string, $remove)
    {
        while ($this->startsWith($string, $remove)) {
            $string = substr($string, strlen($remove));
        }
        while ($this->endsWith($string, $remove)) {
            $string = substr($string, 0, strlen($string) - strlen($remove));
        }
        return $string;
    }

    public static function decodeChars($string)
    {
        $string = htmlspecialchars_decode($string);
        $string = htmlspecialchars_decode($string);
        $string = str_replace('&nbsp;', ' ', $string);
        //$string = preg_replace('/[\x00-\x1F\x80-\xC0]/u', '', $string); //possible alternative
        //$string = preg_replace('/[[:cntrl:]]/', '', $string);//possible alternative
        //$string = iconv("UTF-8","UTF-8//IGNORE",$string); //re-enocde to utf-8, ignore non utf-8
        $string = preg_replace('/[\x00-\x09\x0B-\x1F\x7F]/', '', $string);
        return $string;
    }

    public static function fixUpperCase($string)
    {
        $tmpString = html_entity_decode($string);
        if (mb_strtoupper($tmpString) === $tmpString) {//is upper case
            $string = mb_substr($string, 0, 1).mb_strtolower(mb_substr($string, 1, null));
            $string = preg_replace_callback(
                '/[.!?].*?\w/',
                function ($matches) {
                    return mb_strtoupper($matches[0]);
                },
                $string
            );
            $string = str_replace(" i ", " I ", $string);
            $string = str_replace(" i'", " I'", $string);
        }
        return $string;
    }

    public function getShippingInfo($countries, $extensions, $single_zone= 0)
    {
        $shipping_options = array();//'countries' => $countries
        foreach ($extensions as $value) {
            $this->load->model('extension/shipping/'.$value['code']);
            foreach ($countries as $country) {
                foreach ($country['zones'] as $zones) {
                    if ($country['country']['status'] == "1") {
                        $quote = $this->{'model_extension_shipping_'.$value['code']}->getQuote($zones);
                        if ($quote && $quote['error'] == false) {
                            $shipping_options[$country['country']['iso_code_3']][$zones['code']][$value['code']] = $quote['quote'];
                            if ($single_zone) {
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $shipping_options;
    }

    public function getCountriesByIso($iso_codes = array())
    {
        $iso_append = empty($iso_codes) ? "" : " AND (`c`.`iso_code_3` = '".implode("' OR `c`.`iso_code_3` = '", $iso_codes)."' OR `c`.`iso_code_2` = '".implode("' OR `c`.`iso_code_2` = '", $iso_codes)."')";
        $query = $this->db->query("SELECT `z`.`country_id` AS `country_id`,
        `c`.`name` AS `country_name`,
        `c`.`iso_code_3` AS `iso_code_3`,
        `c`.`iso_code_2` AS `iso_code_2`,
        `c`.`address_format` AS `address_format`,
        `c`.`postcode_required` AS `postcode_required`,
        `z`.`zone_id` AS `zone_id`,
        `z`.`name` AS `zone_name`,
        `z`.`code` AS `zone_iso`
        FROM `".DB_PREFIX."country` AS `c`
        inner JOIN `".DB_PREFIX."zone` AS `z`
        ON `c`.`country_id` = `z`.`country_id`
        WHERE `c`.`status` = 1 AND `z`.`status` = 1".$iso_append." ORDER BY `c`.`country_id`");
        $countries = array();
        foreach ($query->rows as $value) {
            $countries[$value['country_id']]['country'] = array('country_id' => (int)$value['country_id'], 'name' => $value['country_name'], 'iso_code_2' => $value['iso_code_2'], 'iso_code_3' => $value['iso_code_3'], 'address_format' => $value['address_format'], 'postcode_required' => $value['postcode_required'], 'status' => 1);
            $countries[$value['country_id']]['zones'][(int)$value['zone_id']] = array('zone_id' => (int)$value['zone_id'], 'country_id' => (int)$value['country_id'], 'name' => $value['zone_name'], 'code' => $value['zone_iso'], 'iso_code_2' => $value['iso_code_2'], 'iso_code_3' => $value['iso_code_3']);
        }
        return $countries;
    }
}
?>
