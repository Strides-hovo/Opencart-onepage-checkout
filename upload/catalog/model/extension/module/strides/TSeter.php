<?php

trait TSeter
{

    public function setShippingMethod(): self
    {
        $data = [];
         
        if ($this->cart->hasShipping()) {
            $methods = $this->session->data['shipping_methods'] ?? [];
            $method = $this->request->post['shipping_method'] ?? $this->session->data['shipping_method']['code'] ?? null;

            if (($shipping = explode('.', $method)) and count($shipping) > 1) {
                $shipping_method = $methods[$shipping[0]]['quote'][$shipping[1]];
                if ($shipping_method) {
                    $data['shipping_method'] = $shipping_method['title'];
                    $data['shipping_code'] = $this->getDataByKey($this->request->post, 'shipping_method') ?? $shipping_method['code'];
                    $this->session->data['shipping_method'] = $shipping_method;
                }
                // dd($shipping_method);
            } 
            else {
                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_address']);
                $this->errors['shipping_method'] = str_replace('&nbsp;', '', strip_tags($this->language->get('error_no_shipping')));
            }
        } 
        else {
            unset($this->session->data['shipping_address']);
            unset($this->session->data['shipping_method']);
        }

        // $this->setTotals();
        $this->setSession($data);

        return $this;
    }

    public function setPaymentMethod(): self
    {

        $data = [];
        $methods = $this->session->data['payment_methods'] ?? [];
        $code = $this->request->post['payment_method'] ?? $this->session->data['payment_code'] ?? false;

        if ($methods && $code && isset($methods[$code])) {
            $data['payment_method'] = $methods[$code]['title'];
            $data['payment_code'] = $code;
            $this->session->data['payment_method'] = $methods[$code];
        } 
        else {
            $data['payment_method'] = 'no payment method';
            $data['payment_code'] = 'no payment code';
            $this->errors['payment_method'] = str_replace('&nbsp;', '', strip_tags($this->language->get('error_no_payment')));
        }
        // $this->setTotals();
        $this
            ->setSession($data);

        return $this;
    }

    public function setShippingMethods(): self
    {

        $method_data = [];
        $this->load->model('setting/extension');
        $results = $this->model_setting_extension->getExtensions('shipping');

        $address = [
            'country_id' => $this->CountryId(),
            'zone_id' => $this->ZoneId(),
        ];

        foreach ($results as $result) {
            $status = "module_strides_checkout_{$result['code']}_status";
            if ($this->config->get($status)) {
                $this->load->model('extension/shipping/' . $result['code']);
                $quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($address);
                if ($quote) {
                    $method_data[$result['code']] = array(
                        'title' => $quote['title'],
                        'quote' => $quote['quote'],
                        'sort_order' => $quote['sort_order'],
                        'error' => $quote['error'],
                    );
                }
            }
        }

        $sort_order = [];
        foreach ($method_data as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $method_data);

        $this->session->data['shipping_methods'] = $method_data;
        // $this->setTotals();
        return $this;
    }

    public function setPaymentMethods(): self
    {
        $total = $this->cart->getTotal();

        $method_data = [];
        $this->load->model('setting/extension');
        $results = $this->model_setting_extension->getExtensions('payment');
        $address = [
            'country_id' => $this->CountryId(),
            'zone_id' => $this->ZoneId(),
        ];

        foreach ($results as $result) {
            $status = "module_strides_checkout_{$result['code']}_status";
            if ($this->config->get($status)) {
                $this->load->model('extension/payment/' . $result['code']);
                $method = $this->{'model_extension_payment_' . $result['code']}->getMethod($address, $total);
                if ($method) {
                    $method_data[$result['code']] = $method;
                }
            }
        }

        $this->custom_payment->replacePaymentMethods($method_data);

        $sort_order = array();
        foreach ($method_data as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $method_data);

        $this->session->data['payment_methods'] = $method_data;

        return $this;
    }

    public function setTotals(): self
    {

        $this
        ->setShippingMethods()
        ->setShippingMethod()
        ->setPaymentMethods()
        ->setPaymentMethod()
        ;


        $totals = [];
        $taxes = $this->cart->getTaxes();
        $total = 0;

        // Because __call can not keep var references so we put them into an array.
        $total_data = array(
            'totals' => &$totals,
            'taxes' => &$taxes,
            'total' => &$total,
        );

        $this->load->model('setting/extension');
        $results = $this->model_setting_extension->getExtensions('total');
        $sort_order = [];

        foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
        }

        array_multisort($sort_order, SORT_ASC, $results);
        foreach ($results as $result) {
            if ($this->config->get('total_' . $result['code'] . '_status')) {
                $this->load->model('extension/total/' . $result['code']);
                $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
            }
        }

        $sort_order = [];
        foreach ($totals as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $totals);

        $data = [
            'total' => $total,
            'totals' => $totals,
        ];

        $this->custom_payment->replaceTotals($data);

        $this->setSession($data);

        return $this;
    }

    public function setProducts(): self
    {
        // order products
        $data = [];
        $_products = $this->cart->getProducts();
        if (!$_products) {
            return $this;
        }
        foreach ($_products as $product) {
            $option_data = [];

            foreach ($product['option'] as $option) {
                $option_data[] = array(
                    'product_option_id' => $option['product_option_id'],
                    'product_option_value_id' => $option['product_option_value_id'],
                    'option_id' => $option['option_id'],
                    'option_value_id' => $option['option_value_id'],
                    'name' => $option['name'],
                    'value' => $option['value'],
                    'type' => $option['type'],
                );
            }

            $data['products'][] = array(
                'product_id' => $product['product_id'],
                'name' => $product['name'],
                'model' => $product['model'],
                'option' => $option_data,
                'download' => $product['download'],
                'quantity' => $product['quantity'],
                'subtract' => $product['subtract'],
                'price' => $product['price'],
                'total' => $product['total'],
                'tax' => $this->tax->getTax($product['price'], $product['tax_class_id']),
                'reward' => $product['reward'],
            );
        }
        
        // $this

        
        // ->setTotals()

        // ;


        $this->setSession($data);

        return $this;
    }

    protected function setVauchers(): self
    {
        /* vouchers */
        $data = [];

        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $voucher) {
                $data['vouchers'][] = array(
                    'description' => $voucher['description'],
                    'code' => substr(md5(mt_rand()), 0, 10),
                    'to_name' => $voucher['to_name'],
                    'to_email' => $voucher['to_email'],
                    'from_name' => $voucher['from_name'],
                    'from_email' => $voucher['from_email'],
                    'voucher_theme_id' => $voucher['voucher_theme_id'],
                    'message' => $voucher['message'],
                    'amount' => $voucher['amount'],
                );
            }
        }

        $this->setSession($data);
        return $this;
    }

    protected function setMarketing(): self
    {
        $data = [];
        if (isset($this->request->cookie['tracking'])) {
            $data['tracking'] = $this->request->cookie['tracking'];
            $subtotal = $this->cart->getSubTotal();

            // Affiliate
            $this->load->model('account/customer');
            $affiliate_info = $this->model_account_customer->getAffiliateByTracking($this->request->cookie['tracking']);

            if ($affiliate_info) {
                $data['affiliate_id'] = $affiliate_info['affiliate_id'];
                $data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
            } else {
                $data['affiliate_id'] = 0;
                $data['commission'] = 0;
            }

            // Marketing
            $this->load->model('checkout/marketing');

            $marketing_info = $this->model_checkout_marketing->getMarketingByCode($this->request->cookie['tracking']);

            if ($marketing_info) {
                $data['marketing_id'] = $marketing_info['marketing_id'];
            } else {
                $data['marketing_id'] = 0;
            }
        } else {
            $data['affiliate_id'] = 0;
            $data['commission'] = 0;
            $data['marketing_id'] = 0;
            $data['tracking'] = '';
        }

        $this->setSession($data);
        return $this;
    }

    public function setAddressByCustomer($addres_info)
    {
        $types = ['payment', 'shipping'];
        $data = [];
        if (!empty($addres_info) > 0 && is_array($addres_info)) {
            foreach ($addres_info as $key => $value) {
                foreach ($types as $type) {
                    $data[$type . '_' . $key] = $value;
                }
            }
        }
        $this->setSession($data);
    }

}
