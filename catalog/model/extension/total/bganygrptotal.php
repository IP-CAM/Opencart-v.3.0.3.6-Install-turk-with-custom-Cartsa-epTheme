<?php
class ModelExtensiontotalbganygrptotal extends Controller {	
	private $error = array();
	private $modpath = 'total/bganygrptotal'; 
 	private $modname = 'bganygrptotal';
	private $modname_module = 'bganygrp';
	private $modtext = 'Buy Any get Any From Products Group';
	private $modid = '28491';
	private $modssl = 'SSL';
	private $modemail = 'opencarttools@gmail.com'; 
	private $langid = 0;
	private $storeid = 0;
	private $custgrpid = 0;
	
	public function __construct($registry) {
		parent::__construct($registry);
		
		$this->langid = (int)$this->config->get('config_language_id');
		$this->storeid = (int)$this->config->get('config_store_id');
		$this->custgrpid = (int)$this->config->get('config_customer_group_id');
 		
		if(substr(VERSION,0,3)>='3.0' || substr(VERSION,0,3)=='2.3') { 
			$this->modtpl = 'extension/total/bganygrptotal';
			$this->modpath = 'extension/total/bganygrptotal';
 		} else if(substr(VERSION,0,3)=='2.2') {
			$this->modtpl = 'total/bganygrptotal';
		} 
		
		if(substr(VERSION,0,3)>='3.0') { 
			$this->modname = 'total_bganygrptotal';
			$this->modname_module = 'module_bganygrp';
		} 
		
		if(substr(VERSION,0,3)>='3.0' || substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='2.2') { 
			$this->modssl = true;
		} 
 	} 
	
	public function getTotal($total) {
		$data['bganygrptotal_status'] = $this->setvalue($this->modname_module.'_status'); 	
		
		if ($this->cart->hasProducts() && $data['bganygrptotal_status']) { 
			
			$sub_total = 0;
			$buy_product = array();
			$get_product = array();	
			
			foreach ($this->cart->getProducts() as $product) {
				$bganygrp_discount_info = $this->checkbganygrpdiscount($product['product_id']); 
				
				if($bganygrp_discount_info) {	
					$bganygrp_id = $bganygrp_discount_info['bganygrp_id'];
					
					if($bganygrp_discount_info['buyflag']) { 
						$buy_product[$bganygrp_id]['bganygrp_discount_info'] = $bganygrp_discount_info;
						for($x = 1; $x <= $product['quantity']; $x++) {
							$buy_product[$bganygrp_id]['products'][] = $product;
						}
					}
					
					if($bganygrp_discount_info['getflag']) { 
						$get_product[$bganygrp_id]['bganygrp_discount_info'] = $bganygrp_discount_info;
						for($x = 1; $x <= $product['quantity']; $x++) {
							$get_product[$bganygrp_id]['products'][] = $product;
						}
					}
				}
			}
			
			if($buy_product && $get_product) {
				
				$sub_total = $this->cart->getSubTotal();
				
				foreach($get_product as $getproduct) {
					$info = $getproduct['bganygrp_discount_info'];
					$bganygrp_id = $info['bganygrp_id'];
					
					if(isset($buy_product[$bganygrp_id]) && count($buy_product[$bganygrp_id]['products']) >= (int)$info['buyqty']) {
						usort($getproduct['products'], array($this, "sortByPrice")); 
						
						$getfreeqty = floor((count($buy_product[$bganygrp_id]['products']) / $info['buyqty']) * $info['getqty']);
						for($i = 0; $i < min($getfreeqty, count($getproduct['products'])); $i++) {
							
							$discount_total = 0;
				
							$discount = 0;
							
							$product = $getproduct['products'][$i];
							//$product['price'] = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
							$freeqty = 1;
							
							// 1 = free
							// 2 = fixed amount
							// 3 = percentage
							
 							if($info['discount_type'] == 1) {  // free
								$discount = ($product['price'] * $freeqty);
							}
							if($info['discount_type'] == 2) { // fixed amount
								$discount = ($info['discount_value'] * $freeqty);
							}
							if($info['discount_type'] == 3) { // percentage
 								$discount = ($product['price'] / 100 * $info['discount_value']) * $freeqty;
							} 
							
 							if(isset($discount_total_array[$product['product_id']])) {
								$discount += $discount_total_array[$product['product_id']]['discount'];
							}
							$discount_total_array[$product['product_id']] = array(
								'title' => sprintf($info['ordertotaltext'], $product['name']),
								'discount' => $discount,
								'product' => $product,
								'sort_order' => $this->config->get($this->modname.'_sort_order')
							);
 							
						}
  						 
					}
				}
				
				if(isset($discount_total_array) && $discount_total_array) { 
					foreach($discount_total_array as $discount_totalarray) { 
						
						$product = $discount_totalarray['product'];
						$discount = $discount_totalarray['discount'];
						
						if ($product['tax_class_id']) {
							$tax_rates = $this->tax->getRates($product['total'] - ($product['total'] - $discount), $product['tax_class_id']);
							
							if(substr(VERSION,0,3)>='3.0' || substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='2.2') {
								foreach ($tax_rates as $tax_rate) {
									if ($tax_rate['type'] == 'P') {
										$total['taxes'][$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
									}
								}
							} else {
								foreach ($tax_rates as $tax_rate) {
									if ($tax_rate['type'] == 'P') {
										$taxes[$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
									}
								}
							} 
						}
						
						if ($discount > $total) {
							$discount = $total;
						}
						
						if ($discount > 0) {
							if(substr(VERSION,0,3)>='3.0' || substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='2.2') {
								$total['totals'][] = array(
									'code'       => 'bganygrptotal',
									'title'      => $discount_totalarray['title'],
									'value'      => -$discount,
									'sort_order' => $discount_totalarray['sort_order']
								);
			
								$total['total'] -= $discount;
							} else {
								$total_data[] = array(
									'code'       => 'bganygrptotal',
									'title'      => $discount_totalarray['title'],
									'value'      => -$discount,
									'sort_order' => $discount_totalarray['sort_order']
								);
			
								$total -= $discount;
							}
						} 
					}
				}
				//echo "<pre>"; print_r($discount_total_array); exit;
			} 	 
		}
	}
	
	public function checkbganygrpdiscount($product_id) {
		if($this->config->get((substr(VERSION,0,3)>='3.0' ? 'module_bganygrp_status' : 'bganygrp_status'))) { 
			$bganygrp_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "bganygrp WHERE status = 1");
			if($bganygrp_query->num_rows) {
				foreach($bganygrp_query->rows as $result) {
					$checkproduct = $this->validateproduct($result, $product_id);
					if($checkproduct) {
						$ribbontext = json_decode($result['ribbontext'], true);
						$ordertotaltext = json_decode($result['ordertotaltext'], true);
						$offer_heading_text = json_decode($result['offer_heading_text'], true);
						$offer_content = json_decode($result['offer_content'], true);
						
						$result['ribbontext'] = $ribbontext[$this->langid];
						$result['ordertotaltext'] = $ordertotaltext[$this->langid];
						$result['offer_heading_text'] = $offer_heading_text[$this->langid];
						$result['offer_content'] = $offer_content[$this->langid]; 
						
						$result['buyflag'] = isset($checkproduct['buyflag']) ? $checkproduct['buyflag'] : false;
						$result['getflag'] = isset($checkproduct['getflag']) ? $checkproduct['getflag'] : false;
	 
						return $result;
					}
				}
			} else {
				return false;
			} 
		}
	}
	
	public function validateproduct($data, $product_id) {
 		// check store and customer group 
		$data['store'] = (isset($data['store']) && $data['store'] != '') ? explode(",",$data['store']) : array();
		$data['customer_group'] = (isset($data['customer_group']) && $data['customer_group']) ? explode(",",$data['customer_group']) : array();
 		
		if($data && in_array($this->storeid, $data['store']) && in_array($this->custgrpid, $data['customer_group'])) {
			// check product , category , manufacturer
			
			//=================== grouping ==================//
			$buyflag = false;
			$getflag = false;
			
 			if( (isset($data['grpproduct']) && $data['grpproduct']) || (isset($data['grpcategory']) && $data['grpcategory']) || (isset($data['grpmanufacturer']) && $data['grpmanufacturer'])) {	
			 
				// product
				$product_array = ($data['grpproduct']) ? explode(",",$data['grpproduct']) : array();
							
				if($data['grpproduct'] && in_array($product_id , $product_array)) { $buyflag = true; } 
					
				// category
				if($data['grpcategory'] && $buyflag == false) {
					
					$category_array = ($data['grpcategory']) ? explode(",",$data['grpcategory']) : array();
					
					foreach($category_array as $category_id) {
						$category_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category where 1 and product_id = '".(int)$product_id."' and category_id = '".(int)$category_id."' ");
						if($category_query->num_rows) { $buyflag = true; }
					}
				}
					
				// manufacturer
				if($data['grpmanufacturer'] && $buyflag == false) {
				
					$manufacturer_array = ($data['grpmanufacturer']) ? explode(",",$data['grpmanufacturer']) : array();
					
					foreach($manufacturer_array as $manufacturer_id) {
						$manufacturer_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product where 1 and product_id = '".(int)$product_id."' and  manufacturer_id = '".(int)$manufacturer_id."'  ");
						if($manufacturer_query->num_rows) { $buyflag = true; }
					} 
				} 
			} else { 
				$buyflag = true; 
			}
			
			if($buyflag) {
				$getflag = true;
			}
			
  				
			if($buyflag || $getflag) {
				return array('flag' => true, 'buyflag' => $buyflag, 'getflag' => $getflag);
			} 
		} else {
			return false;
		} 
	} 
 	
	private function sortByPrice($a, $b) {
		return $a['price'] - $b['price'];
	}
	
	protected function setvalue($postfield) {
		return $this->config->get($postfield);
	}
}