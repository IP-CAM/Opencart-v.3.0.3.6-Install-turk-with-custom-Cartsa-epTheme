<?php
class ControllerExtensionModuleHTML extends Controller {
	public function index($setting) {
		$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');
$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/opencart.css');
$this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.jquery.js');
		if (isset($setting['module_description'][$this->config->get('config_language_id')])) {
			$data['heading_title'] = html_entity_decode($setting['module_description'][$this->config->get('config_language_id')]['title'], ENT_QUOTES, 'UTF-8');
			$data['html'] = html_entity_decode($setting['module_description'][$this->config->get('config_language_id')]['description'], ENT_QUOTES, 'UTF-8');

			return $this->load->view('extension/module/html', $data);
		}
	}
}