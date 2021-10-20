<?php
class ControllerExtensionbganygrp extends Controller {
	private $error = array();  
	private $modpath = 'extension/bganygrp';
	private $modtpl_list = 'extension/bganygrp_list.tpl';
	private $modtpl_form = 'extension/bganygrp_form.tpl';	
	private $modssl = 'SSL';
	private $token_str = ''; 
	
	public function __construct($registry) {
		parent::__construct($registry);
 		
		if(substr(VERSION,0,3)>='3.0' || substr(VERSION,0,3)=='2.3') { 
 			$this->modpath = 'extension/bganygrp';
 			$this->modtpl_list = 'extension/bganygrp_list';
			$this->modtpl_form = 'extension/bganygrp_form';	 
			$this->modtpl_mail = 'extension/bganygrp_sendquotemail';
 		} else if(substr(VERSION,0,3)=='2.2') {
 			$this->modtpl_list = 'extension/bganygrp_list';
			$this->modtpl_form = 'extension/bganygrp_form';	 
		} 
		 
		if(substr(VERSION,0,3)>='3.0') { 
 			$this->token_str = 'user_token=' . $this->session->data['user_token'];
		} else {
			$this->token_str = 'token=' . $this->session->data['token'];
		}
		
		if(substr(VERSION,0,3)>='3.0' || substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='2.2') { 
			$this->modssl = true;
		} 
 	} 

	public function index() {
		$this->load->language($this->modpath);

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model($this->modpath);

		$this->getList();
	}

	public function add() {
		$this->load->language($this->modpath);

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model($this->modpath);

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_bganygrp->addbganygrp($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link($this->modpath, $this->token_str . $url, $this->modssl));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language($this->modpath);

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model($this->modpath);

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_bganygrp->editbganygrp($this->request->get['bganygrp_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link($this->modpath, $this->token_str . $url, $this->modssl));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language($this->modpath);

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model($this->modpath);

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $bganygrp_id) {
				$this->model_extension_bganygrp->deletebganygrp($bganygrp_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link($this->modpath, $this->token_str . $url, $this->modssl));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'bganygrp_id';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->token_str, $this->modssl)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->modpath, $this->token_str . $url, $this->modssl)
		);

		$data['add'] = $this->url->link($this->modpath.'/add', $this->token_str . $url, $this->modssl);
		$data['delete'] = $this->url->link($this->modpath.'/delete', $this->token_str . $url, $this->modssl);
 
		$data['bganygrps'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$bganygrp_total = $this->model_extension_bganygrp->getTotalbganygrps();

		$results = $this->model_extension_bganygrp->getbganygrps($filter_data);

		foreach ($results as $result) {
			$discount_type = '';
			if($result['discount_type'] == 1) {
				$discount_type = $this->language->get('text_free');
				$discount_value = $this->language->get('text_free');
			} else if($result['discount_type'] == 2) {
				$discount_type = $this->language->get('text_fixed_amount');
				$discount_value = $result['discount_value'];
			} else {
				$discount_type = $this->language->get('text_percentage');
				$discount_value = $result['discount_value'];
			}
			$data['bganygrps'][] = array(
				'bganygrp_id' => $result['bganygrp_id'],
				'name'        => $result['name'],
				'discount_type' => $discount_type,
				'discount_value' => $discount_value,
				'buyqty' => $result['buyqty'],
				'getqty' => $result['getqty'],
 				'status'     => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'edit'        => $this->url->link($this->modpath.'/edit', $this->token_str . '&bganygrp_id=' . $result['bganygrp_id'] . $url, $this->modssl),
				'delete'      => $this->url->link($this->modpath.'/delete', $this->token_str . '&bganygrp_id=' . $result['bganygrp_id'] . $url, $this->modssl)
			);
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');

		$data['column_name'] = $this->language->get('column_name');
		$data['column_discount_type'] = $this->language->get('column_discount_type');
		$data['column_discount_value'] = $this->language->get('column_discount_value');
		$data['column_buyqty'] = $this->language->get('column_buyqty');
		$data['column_getqty'] = $this->language->get('column_getqty');
 		$data['column_status'] = $this->language->get('column_status');
 		$data['column_action'] = $this->language->get('column_action');

		$data['button_add'] = $this->language->get('button_add');
		$data['button_edit'] = $this->language->get('button_edit');
		$data['button_delete'] = $this->language->get('button_delete');
		$data['button_rebuild'] = $this->language->get('button_rebuild');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link($this->modpath, $this->token_str . '&sort=name' . $url, $this->modssl);
 		$data['sort_status'] = $this->url->link($this->modpath, $this->token_str . '&sort=status' . $url, $this->modssl);
 
		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $bganygrp_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link($this->modpath, $this->token_str . $url . '&page={page}', $this->modssl);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($bganygrp_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($bganygrp_total - $this->config->get('config_limit_admin'))) ? $bganygrp_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $bganygrp_total, ceil($bganygrp_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->modtpl_list, $data));
	}

	protected function getForm() {
		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_form'] = !isset($this->request->get['bganygrp_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		$data['text_none'] = $this->language->get('text_none');
		$data['text_default'] = $this->language->get('text_default');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		
		$data['text_free'] = $this->language->get('text_free');
		$data['text_fixed_amount'] = $this->language->get('text_fixed_amount');
		$data['text_percentage'] = $this->language->get('text_percentage');	
		$data['text_above_desc_tab'] = $this->language->get('text_above_desc_tab');	
		$data['text_at_popup'] = $this->language->get('text_at_popup');	
		$data['text_at_desc_tab'] = $this->language->get('text_at_desc_tab');	 
		
		$data['tab_general'] = $this->language->get('tab_general');
		$data['tab_group'] = $this->language->get('tab_group');
		$data['tab_displayoffer'] = $this->language->get('tab_displayoffer');
		
		$data['button_remove'] = $this->language->get('button_remove');		
		$data['button_add_discount'] = $this->language->get('button_add_discount');		
  
		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_discount_type'] = $this->language->get('entry_discount_type');
		$data['entry_discount_value'] = $this->language->get('entry_discount_value'); 
		$data['entry_buyqty'] = $this->language->get('entry_buyqty');
		$data['entry_getqty'] = $this->language->get('entry_getqty');
		
		$data['entry_ribbontext'] = $this->language->get('entry_ribbontext');
		$data['entry_ordertotaltext'] = $this->language->get('entry_ordertotaltext');
		$data['entry_display_offer_at'] = $this->language->get('entry_display_offer_at');
 		$data['entry_offer_heading_text'] = $this->language->get('entry_offer_heading_text');	
		$data['entry_offer_content'] = $this->language->get('entry_offer_content');	
		
		$data['entry_grpproduct'] = $this->language->get('entry_grpproduct');
		$data['entry_grpcategory'] = $this->language->get('entry_grpcategory');
		$data['entry_grpmanufacturer'] = $this->language->get('entry_grpmanufacturer');	
		$data['entry_store'] = $this->language->get('entry_store');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
 		$data['entry_status'] = $this->language->get('entry_status');
 
  		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

 		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		} 
		
		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->token_str, $this->modssl)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->modpath, $this->token_str . $url, $this->modssl)
		);

		if (!isset($this->request->get['bganygrp_id'])) {
			$data['action'] = $this->url->link($this->modpath.'/add', $this->token_str . $url, $this->modssl);
		} else {
			$data['action'] = $this->url->link($this->modpath.'/edit', $this->token_str . '&bganygrp_id=' . $this->request->get['bganygrp_id'] . $url, $this->modssl);
		}

		$data['cancel'] = $this->url->link($this->modpath, $this->token_str . $url, $this->modssl);

		if (isset($this->request->get['bganygrp_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$bganygrp_info = $this->model_extension_bganygrp->getbganygrp($this->request->get['bganygrp_id']);
		}

 		if(substr(VERSION,0,3)>='3.0') { 
 			$data['user_token'] = $this->session->data['user_token']; 
		} else {
			$data['token'] = $this->session->data['token'];
		}

		$this->load->model('localisation/language');
  		$languages = $this->model_localisation_language->getLanguages();
		foreach($languages as $language) {
			if(substr(VERSION,0,3)>='3.0' || substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='2.2') {
				$imgsrc = "language/".$language['code']."/".$language['code'].".png";
			} else {
				$imgsrc = "view/image/flags/".$language['image'];
			}
			$data['languages'][] = array("language_id" => $language['language_id'], "name" => $language['name'], "imgsrc" => $imgsrc);
		}
 
  		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($bganygrp_info)) {
			$data['name'] = $bganygrp_info['name'];
		} else {
			$data['name'] = '';
		}
		
		if (isset($this->request->post['discount_type'])) {
			$data['discount_type'] = $this->request->post['discount_type'];
		} elseif (!empty($bganygrp_info)) {
			$data['discount_type'] = $bganygrp_info['discount_type'];
		} else {
			$data['discount_type'] = 1;
		}
		
		if (isset($this->request->post['discount_value'])) {
			$data['discount_value'] = $this->request->post['discount_value'];
		} elseif (!empty($bganygrp_info)) {
			$data['discount_value'] = $bganygrp_info['discount_value'];
		} else {
			$data['discount_value'] = 0;
		}
		
		if (isset($this->request->post['buyqty'])) {
			$data['buyqty'] = $this->request->post['buyqty'];
		} elseif (!empty($bganygrp_info)) {
			$data['buyqty'] = $bganygrp_info['buyqty'];
		} else {
			$data['buyqty'] = 0;
		}
		
		if (isset($this->request->post['getqty'])) {
			$data['getqty'] = $this->request->post['getqty'];
		} elseif (!empty($bganygrp_info)) {
			$data['getqty'] = $bganygrp_info['getqty'];
		} else {
			$data['getqty'] = 0;
		}
		
		if (isset($this->request->post['ribbontext'])) {
			$data['ribbontext'] = $this->request->post['ribbontext'];
		} elseif (!empty($bganygrp_info)) {
			$data['ribbontext'] = json_decode($bganygrp_info['ribbontext'], true);
		} else {
			$data['ribbontext'] = array();
		} 
		
		if (isset($this->request->post['ordertotaltext'])) {
			$data['ordertotaltext'] = $this->request->post['ordertotaltext'];
		} elseif (!empty($bganygrp_info)) {
			$data['ordertotaltext'] = json_decode($bganygrp_info['ordertotaltext'], true);
		} else {
			$data['ordertotaltext'] = array();
		} 
		
		if (isset($this->request->post['display_offer_at'])) {
			$data['display_offer_at'] = $this->request->post['display_offer_at'];
		} elseif (!empty($bganygrp_info)) {
			$data['display_offer_at'] = $bganygrp_info['display_offer_at'];
		} else {
			$data['display_offer_at'] = 1;
		}
		
		if (isset($this->request->post['offer_heading_text'])) {
			$data['offer_heading_text'] = $this->request->post['offer_heading_text'];
		} elseif (!empty($bganygrp_info)) {
			$data['offer_heading_text'] = json_decode($bganygrp_info['offer_heading_text'], true);
		} else {
			$data['offer_heading_text'] = array();
		} 
		
		if (isset($this->request->post['offer_content'])) {
			$data['offer_content'] = $this->request->post['offer_content'];
		} elseif (!empty($bganygrp_info)) {
			$data['offer_content'] = json_decode($bganygrp_info['offer_content'], true);
		} else {
			$data['offer_content'] = array();
		} 
		
		// Buy 
		//product
 		if (isset($this->request->post['grpproduct'])) {
			$data['grpproduct'] = $this->request->post['grpproduct'];
		} elseif (!empty($bganygrp_info)) {
			$data['grpproduct'] = $bganygrp_info['grpproduct'];
			$data['grpproduct'] = ($bganygrp_info['grpproduct']) ? explode(",",$bganygrp_info['grpproduct']) : array();
 		} else {
			$data['grpproduct'] = array();
		}
		
		$data['grpproduct_data'] = array();
		$this->load->model('catalog/product');
		
 		if($data['grpproduct']) {
 			foreach ($data['grpproduct'] as $product_id) {
				$product_info = $this->model_catalog_product->getProduct($product_id);
	
				if ($product_info) {
					$data['grpproduct_data'][] = array(
						'product_id' => $product_info['product_id'],
						'name'       => $product_info['name']
					);
				}
			}
		}
		
		// category
 		if (isset($this->request->post['grpcategory'])) {
			$data['grpcategory'] = $this->request->post['grpcategory'];
		} elseif (!empty($bganygrp_info)) {
			$data['grpcategory'] = $bganygrp_info['grpcategory'];
 			$data['grpcategory'] = ($bganygrp_info['grpcategory']) ? explode(",",$bganygrp_info['grpcategory']) : array();
		} else {
			$data['grpcategory'] = array();
		}
		
		$data['grpcategory_data'] = array();
		$this->load->model('catalog/category');
		
 		if($data['grpcategory']) {
 			foreach ($data['grpcategory'] as $category_id) {
				$category_info = $this->model_catalog_category->getCategory($category_id);
	
				if ($category_info) {
					$data['grpcategory_data'][] = array(
						'category_id' => $category_info['category_id'],
						'name'       => $category_info['name']
					);
				}
			}
		}
 		
		// manufacturer
 		if (isset($this->request->post['grpmanufacturer'])) {
			$data['grpmanufacturer'] = $this->request->post['grpmanufacturer'];
		} elseif (!empty($bganygrp_info)) {
			$data['grpmanufacturer'] = $bganygrp_info['grpmanufacturer'];
 			$data['grpmanufacturer'] = ($bganygrp_info['grpmanufacturer']) ? explode(",",$bganygrp_info['grpmanufacturer']) : array();
		} else {
 			$data['grpmanufacturer'] = array();
		}
		
		$data['grpmanufacturer_data'] = array();
		$this->load->model('catalog/manufacturer');
		
 		if($data['grpmanufacturer']) {
 			foreach ($data['grpmanufacturer'] as $manufacturer_id) {
				$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($manufacturer_id);
	
				if ($manufacturer_info) {
					$data['grpmanufacturer_data'][] = array(
						'manufacturer_id' => $manufacturer_info['manufacturer_id'],
						'name'       => $manufacturer_info['name']
					);
				}
			}
		}
		// store
		$this->load->model('setting/store');

		$data['stores'] = $this->model_setting_store->getStores();
		
		if (isset($this->request->post['store'])) {
			$data['store'] = $this->request->post['store'];
		} elseif (!empty($bganygrp_info)) {
			$data['store'] = explode(",",$bganygrp_info['store']);
		} else {
			$data['store'] = array();
		}
		
		// customer_group
		$data['customer_groups'] = $this->getCustomerGroups();
		
		if (isset($this->request->post['customer_group'])) {
			$data['customer_group'] = $this->request->post['customer_group'];
		} elseif (!empty($bganygrp_info)) {
			$data['customer_group'] = explode(",",$bganygrp_info['customer_group']);
		} else {
			$data['customer_group'] = array();
		} 

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($bganygrp_info)) {
			$data['status'] = $bganygrp_info['status'];
		} else {
			$data['status'] = true;
		}
 
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->modtpl_form, $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', $this->modpath)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 2) || (utf8_strlen($this->request->post['name']) > 255)) {
			$this->error['name'] = $this->language->get('error_name');
		}
		
		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}
		
		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', $this->modpath)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	} 
	
	public function getCustomerGroups($data = array()) {
 		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_group cg LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (cg.customer_group_id = cgd.customer_group_id) WHERE cgd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY cgd.name ASC");
 		return $query->rows;
	} 
}
