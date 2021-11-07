<?php
class ControllerExtensionFeedCustom extends Controller {
    private $error = array();

    public function install()
    {
        $this->load->model('extension/feed/feed_manager_taxonomy');
        $this->model_extension_feed_feed_manager_taxonomy->install();
    }

    public function index()
    {
        $this->language->load('extension/feed/custom');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        $this->load->model('extension/feed/google_merchant_center');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('feed_custom', $this->request->post);
            $this->model_extension_feed_google_merchant_center->saveSetting($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            if ((int)str_replace('.','',VERSION)>=3000) {
                $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true));
            }
        }
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_feed'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/feed/custom', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/feed/custom', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true);
        $data['text_edit'] = $this->language->get('text_edit');
        //collect settings
        $switch = array(1 => $this->language->get('text_enabled'), 0 => $this->language->get('text_disabled'));
        $options_attributes = array();
        foreach ($this->model_extension_feed_google_merchant_center->getOptionID() as $value) {
            $option_id = $value['option_id'];
            $options_attributes[$option_id]=$value['name'].' (ID: '.$option_id.')';
        }
        $carriers = $this->model_extension_feed_google_merchant_center->getCarriers();
        $image_sizes = array('direct' => $this->language->get('text_direct_image'));
        foreach ($this->model_extension_feed_google_merchant_center->getImageSizes($this->config->get('config_store_id'), 100) as $value) {
            $image_sizes[$value['wh']]=$value['wh'].' '.$value['name'].' ('.$value['theme'].')';
        }
        $image_sizes['600x600'] = '600x600'.$this->language->get('text_minimal_image');//recommend minimal
        $languages = $this->model_extension_feed_google_merchant_center->getLanguages();
        $currencies = $this->model_extension_feed_google_merchant_center->getCurrencies();
        $prefix = 'feed_custom_';
        //create twig settings
        $data['sm_status'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix,'status', $switch, 0);
        $data['sm_save_to_file'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix,'save_to_file', $switch, 0);
        $data['sm_compression'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix,'compression', $switch, 1);
        $data['sm_options'] = $this->model_extension_feed_google_merchant_center->createMultiCheckboxSetting($prefix,'options', $options_attributes, array());
        $data['sm_clear_html'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix,'clear_html', $switch, 1);
        $data['sm_use_meta'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix,'use_meta', $switch, 1);
        $data['sm_image_cache'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix, 'image_cache', $image_sizes, null);
        $data['sm_in_stock_products'] = $this->model_extension_feed_google_merchant_center->createInputSetting($prefix, 'in_stock_products', '');
        $data['sm_disabled_products'] = $this->model_extension_feed_google_merchant_center->createInputSetting($prefix, 'disabled_products', '');
        $data['sm_sold_out_products'] = $this->model_extension_feed_google_merchant_center->createInputSetting($prefix, 'sold_out_products', '');
        $data['sm_language'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix, 'language', $languages, $this->config->get('config_language'));
        $data['sm_currency'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix, 'currency', $currencies, $this->config->get('config_currency'));
        $data['sm_carriers'] = $this->model_extension_feed_google_merchant_center->createMultiCheckboxSetting($prefix,'carriers', $carriers, array());
        $data['sm_shop_address'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix,'shop_address', $switch, 1);
        $data['sm_template_location'] = $this->model_extension_feed_google_merchant_center->createInputSetting($prefix, 'template_location', 'feed_templates');
        $data['entry_feed_url'] = $this->language->get('entry_feed_url');
        $data['help_feed_url'] = $this->language->get('help_feed_url');
        $data['entry_template_name'] = $this->language->get('entry_template_name');
        $data['entry_template_download'] = $this->language->get('entry_template_download');
        $data['text_open'] = $this->language->get('text_open');
        $data['text_copy'] = $this->language->get('text_copy');
        $data['text_copy_success'] = $this->language->get('text_copy_success');
        $data['text_copy_fail'] = $this->language->get('text_copy_fail');
        $http_sep = '';
        if (defined(HTTPS_CATALOG)) {
            $base_url = HTTPS_CATALOG;
        } else {
            $base_url = HTTP_CATALOG;
        }
        if (substr($base_url, -1) != '/') {
            $http_sep = '/';
        }
        $data['feed_template_url'] = 'https://infinia.systems/opencart-feed-templates/';
        $feed_urls = array();
        $template_location = trim($this->config->get($prefix.'template_location'), ' '.DIRECTORY_SEPARATOR);
        if (empty($template_location)) {
            $template_location = 'feed_templates';
        }
        $root_folder = str_replace('catalog/', '', DIR_CATALOG).$template_location;
        if (is_dir($root_folder)) {
            $templates = scandir($root_folder);
            $parameters = array();
            if (in_array('parameters.php', $templates)) {
                include_once($root_folder.'/parameters.php');
            }
            foreach ($templates as $value) {
                if ($value != 'index.php' && $value != 'parameters.php' && substr($value, 0, 1) != "." && !is_dir($root_folder.DIRECTORY_SEPARATOR.$value)) {
                    $feed_urls[$value]=$base_url.$http_sep.'index.php?route=extension/feed/custom&template='.$value.(array_key_exists($value, $parameters) ? $parameters[$value] : '');
                }
            }
        } else {
            $feed_urls[] = str_replace('feed_templates', $template_location, $this->language->get('no_custom_directory'));//basename($template_location);
        }
        if (empty($feed_urls)) {
            $data['feed_urls'] = array(str_replace('feed_templates', $template_location, $this->language->get('no_custom_templates')));
        } else {
            $data['feed_urls'] = $feed_urls;
        }
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/feed/custom', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/feed/custom')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
}
?>
