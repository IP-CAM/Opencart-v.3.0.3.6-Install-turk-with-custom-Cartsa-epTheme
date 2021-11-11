<?php
class ModelExtensionTotalPaymentMethodFee extends Model {
	public function getTotal($total) {
		if ($this->config->get('total_payment_method_fee_status') && isset($this->session->data['payment_method'])) {
			$rules = $this->config->get('total_payment_method_fee_rules');

			if (is_array($rules)) {
				// find the matching rules
				$matching_rules = array();

				foreach ($rules as $rule) {
					if ($rule['status'] && $this->session->data['payment_method']['code'] == $rule['payment_code'] && in_array($this->config->get('config_store_id'), $rule['stores']) && ($total['total'] >= (float)$rule['value']) && ($total['total'] > (float)$rule['total'])) {
						$matching_rules[] = $rule;
					}
				}

				foreach ($matching_rules as $matching_rule) {
					$value_total = 0;

					if ($matching_rule) {
						if ($matching_rule['type'] == 'percentage') {
							$value = $total['total'] / 100 * $matching_rule['value'];

							if ($matching_rule['method'] == 'discount') {
								$value_total -= $value;
							} else {
								$value_total += $value;
							}
						} elseif ($matching_rule['type'] == 'fixed') {
							$value = $matching_rule['method'] == 'discount' ? -$matching_rule['value'] : $matching_rule['value'];

							if ($this->config->get('total_payment_method_fee_tax_class_id')) {
								$tax_rates = $this->tax->getRates($value, $this->config->get('total_payment_method_fee_tax_class_id'));

								foreach ($tax_rates as $tax_rate) {
									if (!isset($total['taxes'][$tax_rate['tax_rate_id']])) {
										$total['taxes'][$tax_rate['tax_rate_id']] = $tax_rate['amount'];
									} else {
										$total['taxes'][$tax_rate['tax_rate_id']] += $tax_rate['amount'];
									}
								}
							}

							$value_total += $value;
						}

						if ($value_total) {
							$names = $matching_rule['title'];

							$name = trim($names[$this->config->get('config_language_id')]['name']);

							if (!$name) {
								$name = $this->session->data['payment_method']['title'];
							}

							$total['totals'][] = array(
								'code'       => 'payment_method_fee',
								'title'      => $name,
								'value'      => $value_total,
								'sort_order' => $this->config->get('total_payment_method_fee_sort_order')
							);

							$total['total'] += $value_total;
						}
					}
				}
			}
		}
	}
}