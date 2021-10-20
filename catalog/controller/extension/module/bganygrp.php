<?php
class ControllerExtensionModulebganygrp extends Controller {	
	private $error = array();
	private $modpath = 'module/bganygrp'; 
	private $modpath_model = 'total/bganygrptotal';
	private $modtpl = 'default/template/module/bganygrp.tpl'; 
	private $modtpl_popup = 'default/template/module/bganygrp_popup.tpl'; 
	private $modtpl_atdesctab = 'default/template/module/bganygrp_atdesctab.tpl'; 
	private $modname = 'bganygrp';
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
			$this->modtpl = 'extension/module/bganygrp';
			$this->modtpl_popup = 'extension/module/bganygrp_popup';
			$this->modtpl_atdesctab = 'extension/module/bganygrp_atdesctab';
			
			$this->modpath = 'extension/module/bganygrp';
			$this->modpath_model = 'extension/total/bganygrptotal';
		} else if(substr(VERSION,0,3)=='2.2') {
			$this->modtpl = 'module/bganygrp';
			$this->modtpl_popup = 'module/bganygrp_popup';
			$this->modtpl_atdesctab = 'module/bganygrp_atdesctab';
		} 
		
		if(substr(VERSION,0,3)>='3.0') { 
			$this->modname = 'module_bganygrp';
		} 
		
		if(substr(VERSION,0,3)>='3.0' || substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='2.2') { 
			$this->modssl = true;
		} 
 	} 
	
	public function index() {
 		$data = $this->load->language($this->modpath);
		
		$this->load->model($this->modpath_model);
		
		$data['bganygrp_status'] = $this->setvalue($this->modname.'_status'); 		
		
 		if($data['bganygrp_status'] && isset($this->request->get['product_id'])) { 
			$this->load->model('catalog/product');
			
			$product_info = $this->model_catalog_product->getProduct($this->request->get['product_id']);
			
			if($product_info && (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price'))) {
			
				if(substr(VERSION,0,3) >='3.0' || substr(VERSION,0,3) =='2.3') { 
					$bganygrp_discount_info = $this->model_extension_total_bganygrptotal->checkbganygrpdiscount($product_info['product_id']); 
				} else {
					$bganygrp_discount_info = $this->model_total_bganygrptotal->checkbganygrpdiscount($product_info['product_id']); 
				}
				
				//echo "<pre>"; print_r($bganygrp_discount_info);exit;
			
				if($bganygrp_discount_info) { 					
					$data['offer_heading_text'] = $bganygrp_discount_info['offer_heading_text'];
					$data['offer_content'] = html_entity_decode($bganygrp_discount_info['offer_content'], ENT_QUOTES, 'UTF-8');	
					
					// 1 = above desc tab
					// 2 = popup
					// 3 = at desc tab	
 				
					if($bganygrp_discount_info['display_offer_at'] == 1) {
						return $this->load->view($this->modtpl, $data);
					} 
				}
 			}
		}
	}
	
	public function popup() {
 		$data = $this->load->language($this->modpath);
		
		$this->load->model($this->modpath_model);
		
		$data['bganygrp_status'] = $this->setvalue($this->modname.'_status'); 		
		
 		if($data['bganygrp_status'] && isset($this->request->get['product_id'])) { 
			$this->load->model('catalog/product');
			
			$product_info = $this->model_catalog_product->getProduct($this->request->get['product_id']);
			
			if($product_info && (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price'))) {
			
				if(substr(VERSION,0,3) >='3.0' || substr(VERSION,0,3) =='2.3') { 
					$bganygrp_discount_info = $this->model_extension_total_bganygrptotal->checkbganygrpdiscount($product_info['product_id']); 
				} else {
					$bganygrp_discount_info = $this->model_total_bganygrptotal->checkbganygrpdiscount($product_info['product_id']); 
				}
				
				//echo "<pre>"; print_r($bganygrp_discount_info);exit;
			
				if($bganygrp_discount_info) { 					
					$data['offer_heading_text'] = $bganygrp_discount_info['offer_heading_text'];
					$data['offer_content'] = html_entity_decode($bganygrp_discount_info['offer_content'], ENT_QUOTES, 'UTF-8');	
					
					// 1 = above desc tab
					// 2 = popup
					// 3 = at desc tab	
 				
					if($bganygrp_discount_info['display_offer_at'] == 2) {
						return $this->load->view($this->modtpl_popup, $data);
					}
				}
 			}
		}
	}
	
	public function atdesctab() {
 		$data = $this->load->language($this->modpath);
		
		$this->load->model($this->modpath_model);
		
		$data['bganygrp_status'] = $this->setvalue($this->modname.'_status'); 		
		
 		if($data['bganygrp_status'] && isset($this->request->get['product_id'])) { 
			$this->load->model('catalog/product');
			
			$product_info = $this->model_catalog_product->getProduct($this->request->get['product_id']);
			
			if($product_info && (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price'))) {
			
				if(substr(VERSION,0,3) >='3.0' || substr(VERSION,0,3) =='2.3') { 
					$bganygrp_discount_info = $this->model_extension_total_bganygrptotal->checkbganygrpdiscount($product_info['product_id']); 
				} else {
					$bganygrp_discount_info = $this->model_total_bganygrptotal->checkbganygrpdiscount($product_info['product_id']); 
				}
				
				//echo "<pre>"; print_r($bganygrp_discount_info);exit;
			
				if($bganygrp_discount_info && $bganygrp_discount_info['display_offer_at'] == 3) { 					
					$data['offer_heading_text'] = $bganygrp_discount_info['offer_heading_text'];
					$data['offer_content'] = html_entity_decode($bganygrp_discount_info['offer_content'], ENT_QUOTES, 'UTF-8');	
				} else {
					$data['offer_heading_text'] = false;
					$data['offer_content'] = false;
				}
				
				// 1 = above desc tab
				// 2 = popup
				// 3 = at desc tab	
			
				return $this->load->view($this->modtpl_atdesctab, $data);
 			}
		}
	}
	
	private function getcurrencyprice($price) {
		if(substr(VERSION,0,3) >= '3.0' || substr(VERSION,0,3) =='2.3' || substr(VERSION,0,3) =='2.2') { 
			return $this->currency->format($price, $this->session->data['currency']);
		} else {
			return $this->currency->format($price);
		}
	}
	
	protected function setvalue($postfield) {
		return $this->config->get($postfield);
	}
}