<?php
class ModelExtensionFeedGoogleMerchantCenter extends Model {
        public function getBaseCategory() {
        if ($this->hasTable(DB_PREFIX."feed_manager_taxonomy") == 0) {
            return array();
        }
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "feed_manager_taxonomy` WHERE `name` NOT LIKE '%>%' ORDER BY `name` ASC");
		return $query->rows;
	}

    public function getNextCategoryID() {
        $query = $this->db->query("SELECT `auto_increment` FROM INFORMATION_SCHEMA.TABLES WHERE table_name = '".DB_PREFIX."category';");
        if ($query->row) {
            return (int)$query->row['auto_increment']-1;
        }
        return "";
    }

	public function getTaxonomyCategory($category_id) {
        if ($this->hasTable(DB_PREFIX."feed_manager_taxonomy") == 0) {
            return array();
        }
		$taxonomyID="";
		if ($category_id!=""){
			$query = $this->db->query("SELECT taxonomy_id FROM `" . DB_PREFIX . "feed_manager_category` WHERE `category_id` = ".(int)$category_id."");
			if ($query->row) {
				$taxonomyID = $query->row['taxonomy_id'];
			}
		}
        $base_taxonomy = $this->config->get('feed_google_merchant_center_base_taxonomy');
        $base_taxonomy_name = array();
        if (!empty($base_taxonomy)) {
            $query = $this->db->query("SELECT `name` FROM `".DB_PREFIX."feed_manager_taxonomy` WHERE `taxonomy_id` = '".implode("' OR `taxonomy_id` = '",$base_taxonomy)."';");
            foreach ($query->rows as $value) {
                $base_taxonomy_name[]=$value['name'];
            }
        }
        $query = $this->db->query("SELECT taxonomy_id, name, IF(taxonomy_id = '".$taxonomyID."', 1, 0) as status FROM ".DB_PREFIX."feed_manager_taxonomy WHERE ".(empty($base_taxonomy_name) ? "" : "(name LIKE '".implode("%' OR name LIKE '",$base_taxonomy_name)."%') AND ")."taxonomy_id != 0 ORDER BY name ASC;");
		return $query->rows;
	}

	public function saveSetting($data_base) {
        if ($this->hasTable(DB_PREFIX."feed_manager_taxonomy") == 0) {
            return;
        }
		$this->db->query("UPDATE `" . DB_PREFIX . "feed_manager_taxonomy` SET status = '0';");
		if (isset($data_base['google_merchant_base'])){
			foreach($data_base['google_merchant_base'] as $base) {
				$this->db->query("UPDATE `" . DB_PREFIX . "feed_manager_taxonomy` SET status = '1' WHERE `taxonomy_id` LIKE '".$base."';");
			}
		}
	}

	public function saveCategory($taxonomy_id,$category_id) {
        if ($this->hasTable(DB_PREFIX."feed_manager_category") == 0) {
            return;
        }
        if ($category_id == "") {
            $category_id = $this->getNextCategoryID();
        }
		$this->db->query("INSERT INTO `" . DB_PREFIX . "feed_manager_category` SET taxonomy_id = '".$taxonomy_id."', category_id = '".$category_id."' ON DUPLICATE KEY UPDATE taxonomy_id = '".$taxonomy_id."'");
	}

    public function saveCategoryCustom($taxonomy_name ,$taxonomy_id, $category_id, $feed_name) {
        if ($this->hasTable(DB_PREFIX."feed_manager_custom_category") != 0) {
            if ($category_id == "") {
                $category_id = $this->getNextCategoryID();
            }
            if ($taxonomy_name === '' && $taxonomy_id === '') {//delete
                $this->db->query("DELETE FROM `" . DB_PREFIX . "feed_manager_custom_category` WHERE category_id = ".(int)$category_id." AND feed_name = '".$feed_name."';");
            } else {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "feed_manager_custom_category` SET taxonomy_id = '".$taxonomy_id."', taxonomy_name = '".$taxonomy_name."', category_id = '".$category_id."', feed_name = '".$feed_name."' ON DUPLICATE KEY UPDATE taxonomy_id = '".$taxonomy_id."', taxonomy_name = '".$taxonomy_name."'");
            }
        }
	}

	public function removeCategory($category_id) {
        if ($this->hasTable(DB_PREFIX."feed_manager_category") != 0) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "feed_manager_category` WHERE category_id = ".(int)$category_id.";");
        }
        if ($this->hasTable(DB_PREFIX."feed_manager_custom_category") != 0) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "feed_manager_custom_category` WHERE category_id = ".(int)$category_id.";");
        }
	}

	public function saveProduct($product_id, $gender, $age_group, $color) {
        if ($this->hasTable(DB_PREFIX."feed_manager_product") == 0) {
            return;
        }
        $color = trim(str_replace("\t", "", $color));
		$this->db->query("INSERT INTO `" . DB_PREFIX . "feed_manager_product` SET product_id = '".$product_id."', gender = '".$gender."', age_group = '".$age_group."', color = '".$color."' ON DUPLICATE KEY UPDATE gender = '".$gender."', age_group = '".$age_group."', color = '".$color."'");
	}

	public function removeProduct($product_id) {
        if ($this->hasTable(DB_PREFIX."feed_manager_product") == 0) {
            return array();
        }
		$this->db->query("DELETE FROM `" . DB_PREFIX . "feed_manager_product` WHERE product_id = ".(int)$product_id.";");
	}

	public function getColorAgeGender($product_id) {
        if ($this->hasTable(DB_PREFIX."feed_manager_product") == 0) {
            $merchant_center['color'] = '';
            $merchant_center['age_group'] = 'adult';
            $merchant_center['gender'] = 'unisex';
            return $merchant_center;
        }
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "feed_manager_product` WHERE `product_id` = ".(int)$product_id.";");
		$merchant_center = array();

		if ($query->row){
			$merchant_center['color'] = $query->row['color'];
			$merchant_center['age_group'] = $query->row['age_group'];
			$merchant_center['gender'] = $query->row['gender'];
		} else {
			$merchant_center['color'] = '';
			$merchant_center['age_group'] = 'adult';
			$merchant_center['gender'] = 'unisex';
		}
		return $merchant_center;
	}

	public function getOptionID() {
		$query=$this->db->query("SELECT od.option_id AS option_id, od.name AS name
            FROM `".DB_PREFIX."option_description` AS od
            INNER JOIN `".DB_PREFIX."option_value_description` AS ovd
            ON ovd.option_id = od.option_id
            WHERE od.`language_id` = ".(int)$this->config->get('config_language_id')."
            GROUP BY ovd.option_id;");
		return $query->rows;
	}

	public function getAttributes() {
		$query=$this->db->query("SELECT attribute_id,name FROM `" . DB_PREFIX . "attribute_description` WHERE `language_id` LIKE '".(int)$this->config->get('config_language_id')."';");
		return $query->rows;
	}

    public function getLanguages() {
        $languages = array();
        $query = $this->db->query("SELECT `language_id`, `name`, `code` FROM `".DB_PREFIX."language` WHERE `status` = 1 ORDER BY `language_id`;");
        foreach ($query->rows as $value) {
            $code = $value['code'];
            $languages[$code]=$value['name'].' ('.$code.')'.($code == $this->config->get('config_language') ? ' - Default' : '');
        }
		return $languages;
    }

    public function getCurrencies($disabled = 1) {
        $currencies = array();
        $query = $this->db->query("SELECT `currency_id`, `title`, `code` FROM `".DB_PREFIX."currency` ".($disabled ? "" : "WHERE `status` = 1 ")."ORDER BY `currency_id`;");
        foreach ($query->rows as $value) {
            $code = $value['code'];
            $currencies[$code]=$value['title'].' ('.$code.')'.($code == $this->config->get('config_currency') ? ' - Default' : '');
        }
		return $currencies;
    }

    public function getCarriers() {
        $carriers = array();
        $this->load->model('setting/extension');
        foreach ($this->model_setting_extension->getInstalled('shipping') as $value) {
            if ($this->config->get('shipping_' . $value . '_status')) {//only enabled
                $this->load->language('extension/shipping/' . $value, 'extension');
                $carriers[$value] = $this->language->get('extension')->get('heading_title');
            }
        }
		return $carriers;
    }

    public function hasTable($tableName) {
        $query=$this->db->query("SHOW tables LIKE '".$tableName."';");
        return count($query->rows);
    }

    public function getSettingData($prefix, $name, $default_value = null)
    {
        $name = $prefix.$name;
        if (isset($this->request->post[$name])) {
            return $this->request->post[$name];
        } elseif ($this->config->get($name)!='') {
            return $this->config->get($name);
        }
        return $default_value;
    }

	public function createInputSetting($prefix, $name, $default_value = null)
	{
		$id = 'input-'.str_replace('_', '-', $prefix.$name);
		return '<div class="form-group">
			<label class="col-sm-2 control-label" for="'.$id.'"><span data-toggle="tooltip" title="'.$this->language->get('help_'.$name).'">'.$this->language->get('entry_'.$name).'</span></label>
			<div class="col-sm-10">
				<input type="text" name="'.$prefix.$name.'" value="'.$this->getSettingData($prefix, $name, $default_value).'" placeholder="'.$this->language->get('entry_'.$name).'" id="'.$id.'" class="form-control" />
			</div>
		</div>';
	}

    public function createInputSettingDynamic($prefix, $name, $values, $lang_postfix, $title = '', $placeholders = array())
	{
        reset($values);//might not be needed
        $for = 'input-'.str_replace('_', '-', $prefix.$name.'-'.key($values));
		$setting = '<div class="form-group">
			<label class="col-sm-2 control-label" for="'.$for.'"><span data-toggle="tooltip" title="'.$this->language->get('help_'.$lang_postfix).'">'.$title.$this->language->get('entry_'.$lang_postfix).'</span></label>';
            $num = (int)10/count($values);
            $use_placeholder = (count($placeholders) === count($values));
            $index = 0;
            foreach ($values as $key => $value) {
                $id = 'input-'.str_replace('_', '-', $prefix.$name.'-'.$key);
                $placeholder = ($use_placeholder ? $placeholders[$index] : '');
                $setting.= '<div class="col-sm-'.$num.'">
    				<input type="text" name="'.$prefix.$name.'_'.$key.'" value="'.$value.'" placeholder="'.$placeholder.'" id="'.$id.'" class="form-control" />
    			</div>';
                $index++;
            }
			$setting.= '</div>';
		return $setting;
	}

    public function getTaxonomyCustom($category_id, $feed_name) {
        if ($this->hasTable(DB_PREFIX."feed_manager_custom_category") == 0) {
            return array('taxonomy_name' => '', 'taxonomy_id' => '');
        }
		$query = $this->db->query("SELECT `taxonomy_name`, `taxonomy_id` FROM `" . DB_PREFIX . "feed_manager_custom_category` WHERE `category_id` = ".(int)$category_id." AND `feed_name` = '".$feed_name."'");
        if (count($query->row) === 0) {
            return array('taxonomy_name' => '', 'taxonomy_id' => '');
        }
        return $query->row;
	}

	public function createTextareaSetting($prefix, $name, $default_value)
	{
		$id = 'input-'.str_replace('_', '-', $prefix.$name);
		return '<div class="form-group">
			<label class="col-sm-2 control-label" for="'.$id.'"><span data-toggle="tooltip" data-html="true" title="'.$this->language->get('help_'.$name).'">'.$this->language->get('entry_'.$name).'</span></label>
			<div class="col-sm-10">
				<textarea name="'.$prefix.$name.'" rows="5" placeholder="'.$this->language->get('entry_'.$name).'" id="'.$id.'" class="form-control">'.$this->getSettingData($prefix, $name, $default_value).'</textarea>
			</div>
		</div>';
	}

	public function createNumberSetting($prefix, $name, $default_value, $step = 1)
	{
		$id = 'input-'.str_replace('_', '-', $prefix.$name);
		return '<div class="form-group">
			<label class="col-sm-2 control-label" for="'.$id.'"><span data-toggle="tooltip" title="'.$this->language->get('help_'.$name).'">'.$this->language->get('entry_'.$name).'</span></label>
			<div class="col-sm-2">
				<input type="number" step="'.$step.'" name="'.$prefix.$name.'" value="'.$this->getSettingData($prefix, $name, $default_value).'" placeholder="'.$this->language->get('entry_'.$name).'" id="'.$id.'" class="form-control" />
			</div>
		</div>';
	}

	public function createCheckboxSetting($prefix, $name, $options, $default_value)
	{
		$id = 'input-'.str_replace('_', '-', $prefix.$name);
		$selected = $this->getSettingData($prefix, $name, $default_value);
		$html = '<div class="form-group">
			<label class="col-sm-2 control-label" for="'.$id.'"><span data-toggle="tooltip" data-html="true" title="'.$this->language->get('help_'.$name).'">'.$this->language->get('entry_'.$name).'</span></label>
			<div class="col-sm-10">
				<select name="'.$prefix.$name.'" id="'.$id.'" class="form-control">';
		foreach ($options as $key => $value) {
				$html .= '<option '.($selected == $key ? 'selected="selected" ' : ' ').'value="'.$key.'">'.$value.'</option>';
		}
		$html .='</select>
			</div>
		</div>';
		return $html;
	}

	public function createSettingsTitle($prefix, $name)
	{
		$html = '<legend>'.$this->language->get('text_'.$name).'</legend>';
		if ($this->language->get('help_'.$name) != '') {
				$html .= '<div class="alert alert-info"><i class="fa fa-info-circle"></i>'.$this->language->get('help_'.$name).'
					<button type="button" class="close" data-dismiss="alert">&times;</button>
				</div>';
		}
		return $html;
	}

	public function createMultiCheckboxSetting($prefix, $name, $options, $default_value = array())
	{
		$id = 'input-'.str_replace('_', '-', $prefix.$name);
        $selected = $this->getSettingData($prefix, $name, $default_value);
		$html =	'<div class="form-group">
				<label class="col-sm-2 control-label" for="'.$id.'"><span data-toggle="tooltip" data-html="true" title="'.$this->language->get('help_'.$name).'">'.$this->language->get('entry_'.$name).'</span></label>
				<div class="col-sm-10">
					<div class="well well-sm" style="height: 150px; overflow: auto;">';
					foreach ($options as $key => $value) {
						$html .= '<div class="checkbox switching"><label style="display: block;"><input type="checkbox" name="'.$prefix.$name.'[]" value="'.$key.'"'.(in_array($key , $selected) ? ' checked="checked"' : '' ).' />'.$value.'</label></div>';
					}
					$html .='</div>
				</div>
			</div>';
		return $html;
	}

    public function getImageSizes($store_id, $min_size = 500) {
        $query=$this->db->query("SELECT * FROM
    (SELECT SUBSTRING_INDEX(SUBSTR(`key`,LOCATE('image',`key`) + 6), '_', 1) AS `name`, SUBSTR(`key`, 1, LOCATE('image',`key`)-2) AS `theme`, GROUP_CONCAT(`value` ORDER BY `key` DESC SEPARATOR 'x') AS `wh`
    FROM `".DB_PREFIX."setting`
    WHERE `key` LIKE '%image%' AND (`key` LIKE '%height%' || `key` LIKE '%width%') AND `store_id` LIKE ".(int)$store_id." AND `value` >= ".(int)$min_size." GROUP BY `name` ORDER BY `name`
    ) AS `image_sizes`
    WHERE ROUND((LENGTH(`wh`)-LENGTH( REPLACE (`wh`, 'x', '')))/LENGTH('x')) LIKE 1;");
        return $query->rows;
    }

    public function getCustomFeedCategoryNames() {
        $template_location = trim($this->config->get('feed_custom_template_location'), ' '.DIRECTORY_SEPARATOR);
        if (empty($template_location)) {
            $template_location = 'feed_templates';
        }
        $root_folder = str_replace('catalog/', '', DIR_CATALOG).$template_location;
        $feed_names = array();
        if (is_dir($root_folder)) {
            $templates = scandir($root_folder);
            foreach ($templates as $value) {
                if ($value != 'index.php' && $value != 'parameters.php' && substr($value, 0, 1) != "." && !is_dir($root_folder.DIRECTORY_SEPARATOR.$value)) {
                    $feed_template = file_get_contents($root_folder.DIRECTORY_SEPARATOR.$value);
                    if (strpos($feed_template, '#custom_category_name#') !== false || strpos($feed_template, '#custom_category_id#') !== false) {
                        $feed_names[]=$value;
                    }
                }
            }
        }
        return $feed_names;
    }

    public function clearFeedName($name)
    {
        return preg_replace('/[^a-z0-9_]/', '', mb_strtolower(str_replace(' ', '_', str_replace('.', '_', $name))));
    }
}
?>
