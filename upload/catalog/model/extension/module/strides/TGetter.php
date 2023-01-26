<?php

trait TGetter
{

    public function getProducts(): array
    {
        $result = [];
        $width = $this->db_data['product_width'] ?? $this->config->get($this->config->get('config_theme') . '_image_cart_width');
        $height = $this->db_data['product_height'] ?? $this->config->get($this->config->get('config_theme') . '_image_cart_height');

        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $_image = $product['image'] ?: 'no_image.png';
            $option_data = [];
            $recurring = '';

            $image = $this->model_tool_image->resize($_image, $width, $height);

            foreach ($product['option'] as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['value'];
                    $option_data[] = array(
                        'name' => $option['name'],
                        'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value),
                    );
                }
            }

            if ($product['recurring']) {
                $frequencies = array(
                    'day' => $this->language->get('text_day'),
                    'week' => $this->language->get('text_week'),
                    'semi_month' => $this->language->get('text_semi_month'),
                    'month' => $this->language->get('text_month'),
                    'year' => $this->language->get('text_year'),
                );

                if ($product['recurring']['trial']) {
                    $recurring = sprintf($this->language->get('text_trial_description'), $this->registry->get('currency')->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax'))), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
                }

                if ($product['recurring']['duration']) {
                    $recurring .= sprintf($this->language->get('text_payment_description'), $this->registry->get('currency')->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax'))), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                } else {
                    $recurring .= sprintf($this->language->get('text_payment_cancel'), $this->registry->get('currency')->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax'))), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                }
            }

            $result[] = array(
                'key' => isset($product['key']) ? $product['key'] : '',
                'cart_id' => isset($product['cart_id']) ? $product['cart_id'] : '',
                'product_id' => $product['product_id'],
                'name' => $product['name'],
                'thumb' => $image,
                'model' => $product['model'],
                'option' => $option_data,
                'recurring' => $recurring,
                'quantity' => $product['quantity'],
                'subtract' => $product['subtract'],
                'price' => $this->registry->get('currency')->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
                'total' => $this->registry->get('currency')->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'], $this->session->data['currency']),
                'href' => $this->url->link('product/product', 'product_id=' . $product['product_id'], true),
            );
        }

        return $result;
    }

    public function getVouchers(): array
    {
        $result = [];
        if ($this->session('vouchers') && !empty($this->session('vouchers'))) {
            foreach ($this->session('vouchers') as $voucher) {
                $result[] = array(
                    'description' => $voucher['description'],
                    'amount' => $this->registry->get('currency')->format($voucher['amount'], $this->session->data['currency']),
                );
            }
        }

        return $result;
    }

    public function getTotals(): array
    {
        // order totals
        $result = [];
        // $this->setTotals();
        if ($totals = $this->session('totals')) {
            foreach ($totals as $total) {
                $result[] = array(
                    'title' => $total['title'],
                    'text' => $this->registry->get('currency')->format($total['value'], $this->session->data['currency']),
                );
            }
        }

        return $result;
    }

}
