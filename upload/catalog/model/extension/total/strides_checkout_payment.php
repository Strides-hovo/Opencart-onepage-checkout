<?php
class ModelExtensionTotalStridesCheckoutPayment extends Model {
	public function getTotal($total) {
		$this->load->language('extension/module/strides/checkout');

		$total['totals'][] = array(
			'code'       => 'strides_checkout_payment',
			'title'      => $this->language->get('text_surcharge'),
			'value'      => max(0, $total['value']),
			'sort_order' => $total['sort_order']
		);
	}
}