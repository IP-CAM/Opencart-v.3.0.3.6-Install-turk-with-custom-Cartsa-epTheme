<?php

class ControllerCatalogProductManager extends Controller
{
    public function index()
    {
        $this->load->language('catalog/product_manager');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/product_manager');

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link(
                'common/dashboard',
                'user_token=' . $this->session->data['user_token'],
                true
            ),
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                'catalog/product_manager',
                'user_token=' . $this->session->data['user_token'],
                true
            ),
        ];

        $ids = $this->model_catalog_product_manager->getHasProducOption([35,47,42,30],12);

        foreach ($ids as $id) {
			$data['deneme'][] = array(
				'product_id'  => $id['product_id']
			);
		}

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        return $this->response->setOutput(
            $this->load->view('catalog/product_manager', $data)
        );
    }
}
