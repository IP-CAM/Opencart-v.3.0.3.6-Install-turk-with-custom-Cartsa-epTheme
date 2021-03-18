<?php
class ControllerExtensionModuleSpecial extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/special');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

		$data['products'] = array();

		$filter_data = array(
			'sort'  => 'pd.name',
			'order' => 'ASC',
			'start' => 0,
			'limit' => $setting['limit']
		);

		$results = $this->model_catalog_product->getProductSpecials($filter_data);

		if ($results) {
			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height']);
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = $result['rating'];
				} else {
					$rating = false;
				}

				/* special */
				if($result['special'] > 0 AND $result['special'] != NULL ){
				$tag_per = ($result['special']*100)/$result['price'];
				$tag_per = round($tag_per);
				if($tag_per == 0){
				$tag_per = 1;
				}else{
				$tag_per = 100-$tag_per;
				}
				$tag = $result['price'] - $result['special'];
				}else{
				$tag = 0;
				$tag_per = 0;
				}

				/* pixeltemplate Images Start */
				$webi_data['webi_images'] = array();
				$webi_results = $this->model_catalog_product->getProductImages($result['product_id']);
				foreach ($webi_results as $webi_result) {
					//echo $webi_result['image']."<br>";
					$webi_data['webi_images'][] = array('popup' => $this->model_tool_image->resize($webi_result['image'],$setting['width'], $setting['height']));
				}

				/* new */
                if(strtotime($result['date_added']) > (time() - (60*60*24*10) )){
					    $is_new = true;
					} else {
					    $is_new = false;
				}

				$data['products'][] = array(
					'manufacturer'=> $result['manufacturer'],
					'is_new'      => $is_new,
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'rating'      => $rating,
					// special
					'tag_per' => $tag_per,
					// Add images Data 
					'webi_images' => $webi_data['webi_images'],
					//End
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
				);
			}

			return $this->load->view('extension/module/special', $data);
		}
	}
}