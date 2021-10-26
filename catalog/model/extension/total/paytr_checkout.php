<?php
class ModelExtensionTotalPaytrCheckout extends Model {
	public function getTotal($total) {
		$this->load->language('extension/payment/paytr_checkout');

		$total['totals'][] = array(
			'code'       => 'paytr_checkout',
			'title'      => $this->language->get('text_total'),
			'value'      => max(0, $total['total']),
			'sort_order' => '8'
		);
	}
}