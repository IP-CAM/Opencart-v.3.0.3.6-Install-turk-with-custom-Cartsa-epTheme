<?php
class ControllerExtensionTotalPaymentMethodFee extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/total/payment_method_fee');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('total_payment_method_fee', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/total/payment_method_fee', 'user_token=' . $this->session->data['user_token'] . '&type=total', true));
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['rules'])) {
			$data['error_rules'] = $this->error['rules'];
		} else {
			$data['error_rules'] = array();
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/total/payment_method_fee', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/total/payment_method_fee', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true);

		if (isset($this->request->post['total_payment_method_fee_status'])) {
			$data['total_payment_method_fee_status'] = $this->request->post['total_payment_method_fee_status'];
		} else {
			$data['total_payment_method_fee_status'] = $this->config->get('total_payment_method_fee_status');
		}

		if (isset($this->request->post['total_payment_method_fee_sort_order'])) {
			$data['total_payment_method_fee_sort_order'] = $this->request->post['total_payment_method_fee_sort_order'];
		} else {
			$data['total_payment_method_fee_sort_order'] = $this->config->get('total_payment_method_fee_sort_order');
		}

		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		if (isset($this->request->post['total_payment_method_fee_tax_class_id'])) {
			$data['total_payment_method_fee_tax_class_id'] = $this->request->post['total_payment_method_fee_tax_class_id'];
		} else {
			$data['total_payment_method_fee_tax_class_id'] = $this->config->get('total_payment_method_fee_tax_class_id');
		}

		if (isset($this->request->post['total_payment_method_fee_rules'])) {
			$data['total_payment_method_fee_rules'] = $this->request->post['total_payment_method_fee_rules'];
		} else {
			$data['total_payment_method_fee_rules'] = is_array($this->config->get('total_payment_method_fee_rules')) ? $this->config->get('total_payment_method_fee_rules') : array();
		}

		// payment methods
		$this->load->model('setting/extension');
		$methods = $this->model_setting_extension->getInstalled('payment');

		$data['methods'] = array();

		foreach ($methods as $code) {
			$this->language->load('extension/payment/' . $code, 'extension');

			$data['methods'][] = array(
				'name'   => strip_tags($this->language->get('extension')->get('heading_title')),
				'code'   => $code
			);
		}

		// stores
		$this->load->model('setting/store');

		$data['stores'] = array();
		
		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);
		
		$stores = $this->model_setting_store->getStores();

		foreach ($stores as $store) {
			$data['stores'][] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name']
			);
		}

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/total/payment_method_fee', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/total/payment_method_fee')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (isset($this->request->post['total_payment_method_fee_rules'])) {
			foreach ($this->request->post['total_payment_method_fee_rules'] as $row=>$rule) {
				if ($rule['payment_code'] === '') {
					$this->error['rules'][$row]['payment_code'] = $this->language->get('error_payment_code');
				}
				if ((float)$rule['total'] < 0) {
					$this->error['rules'][$row]['total'] = $this->language->get('error_total');
				}
				if ((float)$rule['value'] <= 0) {
					$this->error['rules'][$row]['value'] = $this->language->get('error_value');
				}
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}
}