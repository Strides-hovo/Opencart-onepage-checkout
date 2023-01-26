<?php

trait TRequest
{

    private $json;

    public function dispatcher()
    {

        $request = $this->request->post;
        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            header('Location: /');
            exit;
        }
        if ($action = $request['action'] ?? null) {
            switch ($action) {
                case 'qty':
                    if (preg_match('/([0-9]+)/', $request['key'], $matches)) {
                        return $this->cart_update(['cart_id' => $matches[0], 'quantity' => $request['quantity']]);
                    }
                    break;
                case 'delete':
                    return $this->cart_delete($request['cart_id']);
                case 'payment_country':
                case 'shipping_country':
                    return $this->address_update();
                case 'payment_method':
                    return $this->payment_method();
                case 'shipping_method':
                    return $this->shipping_method();
                case 'final':
                    return $this->confirm();
                case 'coupon':
                    return $this->coupon();
                case 'voucher':
                    return $this->voucher();
                case 'reward':
                    return $this->reward();
                case 'islogin':
                    return $this->islogin();
            }
        }

    }

    private function cart_update(array $request)
    {

        $this->cart->update($request['cart_id'], $request['quantity']);
        $json = [];

        if (!$this->model->checkCart()) {
            $json['redirect'] = $this->url->link('checkout/checkout', '', true);
        } 
        else {
            $this->model->setProducts()->setTotals();
            
            $json = $this->getTemplates(['total', 'shipping_methods', 'payment_methods', 'cart', 'payment']);
            $json['action'] = 'cart';
        }
        exit(json_encode($json));
    }

    public function cart_delete(int $cart_id)
    {
        $this->cart->remove($cart_id);

        if (isset($this->session->data['vouchers']) && isset($this->session->data['vouchers'][$cart_id])) {
            unset($this->session->data['vouchers'][$cart_id]);
        }
        $this->model->updateCart();
        if (!$this->model->checkCart()) {
            $json['redirect'] = $this->url->link('checkout/checkout', '', true);
        } else {
            $json = $this->getTemplates(['total', 'shipping_methods', 'payment_methods', 'cart']);
            $json['action'] = 'cart';
        }

        exit(json_encode($json));
    }

    private function address_update()
    {

        $request = $this->request->post;
        $this->load->model('localisation/zone');

        $type = $request['type'];
        $country_id = $request[$type . '_country_id'];
        $session_country_id = $this->model->session($type . '_country_id') ?? 0;
        $zone = '';

        if ($country_id != $session_country_id) {
            $zone = json_encode($this->model_localisation_zone->getZonesByCountryId($country_id));
        }
        
        $this->model->update_address();
        $json = $this->getTemplates(['total', 'shipping_methods', 'payment_methods', 'cart', 'payment']);

        $json = array_merge($json, [
            'zone' => $zone,
            'action' => $zone ? 'address_update' : 'method_update',
            'text_select' => $this->language->get('text_select'),
            'text_none' => $this->language->get('text_none'),
            'type' => $type,
        ]);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));

    }

    public function payment_method()
    {

        $this->model->setPaymentMethod();
        $json = $this->getTemplates(['total', 'shipping_methods', 'payment_methods', 'cart', 'payment']);
        $json['action'] = 'cart';
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function shipping_method()
    {
        $this->model->setShippingMethod();

        $json = $this->getTemplates(['total', 'shipping_methods', 'payment_methods', 'cart', 'payment']);
        $json['action'] = 'cart';
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function confirm()
    {
        try {
            $result = $this->model->confirm();
            if (!empty($result) && !$result['errors'] && !$result['redirect']) {
                $data = array_merge(
                    $result,
                    ['session_data' => $this->session->data, 'action' => 'final'],
                    $this->getTemplates(['total', 'shipping_methods', 'payment_methods', 'cart', 'payment'])
                );
            } else {
                $data = $result;
            }
        } catch (\Throwable$th) {
            throw $th;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function payment_confirm()
    {
        $json = [];
        if (isset($this->session->data['payment_method']['code']) && $this->session->data['payment_method']['code'] == 'strides_checkout_payment') {
            $this->load->model('checkout/order');
            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('module_strides_checkout_payment_order_status_id'));
            $json['redirect'] = $this->url->link('checkout/success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function coupon()
    {

        $json = [];
        try {
            $this->load->language('extension/total/coupon');
            $this->load->model('extension/total/coupon');

            $coupon = $this->request->post['coupon'] ?? '';

            if ($this->model_extension_total_coupon->getCoupon($coupon)) {
                $this->session->data['coupon'] = $this->request->post['coupon'];
                $this->session->data['success'] = $this->language->get('text_success');
            } else if (empty($this->request->post['coupon'])) {
                unset($this->session->data['coupon']);
                $json['error'] = $this->language->get('error_empty');
                $json['redirect'] = $this->url->link('checkout/checkout');
                throw new ErrorException('error');
            } else {
                $json['error'] = $this->language->get('error_coupon');
            }
        } catch (\Throwable$th) {
            exit(json_encode($json));
        }

        $this->model->setTotals();
        $json = array_merge($json, $this->getTemplates(['total', 'shipping_methods', 'payment_methods', 'cart', 'payment']));
        $json['action'] = 'cart';
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function voucher()
    {

        $json = [];
        $this->load->language('extension/total/voucher');
        $this->load->model('extension/total/voucher');

        try {
            $voucher = $this->request->post['voucher'] ?? '';

            if ($this->model_extension_total_voucher->getVoucher($voucher)) {
                $this->session->data['voucher'] = $this->request->post['voucher'];
                $this->session->data['success'] = $this->language->get('text_success');
            } else if (empty($this->request->post['voucher'])) {
                $json['error'] = $this->language->get('error_empty');
                $json['redirect'] = $this->url->link('checkout/checkout');
                unset($this->session->data['voucher']);
                throw new ErrorException('error');
            } else {
                $json['error'] = $this->language->get('error_voucher');
            }
        } catch (\Throwable$th) {
            exit(json_encode($json));
        }

        $this->model->setTotals();
        $json = array_merge($json, $this->getTemplates(['total', 'shipping_methods', 'payment_methods', 'cart', 'payment']));
        $json['action'] = 'cart';

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));

    }

    public function reward()
    {

        $json = [];
        $this->load->language('extension/total/reward');
        $points = $this->customer->getRewardPoints();
        $points_total = 0;

        foreach ($this->cart->getProducts() as $product) {
            if ($product['points']) {
                $points_total += $product['points'];
            }
        }
        $json['redirect'] = $this->url->link('checkout/checkout');
        if (!isset($this->request->post['reward']) || !filter_var($this->request->post['reward'], FILTER_VALIDATE_INT) || ($this->request->post['reward'] <= 0)) {
            $json['error'] = $this->language->get('error_reward');
        }

        if ($this->request->post['reward'] > $points) {
            $json['error'] = sprintf($this->language->get('error_points'), $this->request->post['reward']);
        }

        if ($this->request->post['reward'] > $points_total) {
            $json['error'] = sprintf($this->language->get('error_maximum'), $points_total);
        }

        if (!isset($json['error'])) {
            $this->session->data['reward'] = abs($this->request->post['reward']);
            $this->session->data['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Summary of islogin
     * if customer is login
     */
    public function islogin()
    {

        $this->model->update_address();
        $json = $this->getTemplates(['total', 'shipping_methods', 'payment_methods', 'cart', 'payment']);
        $json['action'] = 'cart';

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function login()
    {

        $data = $this->ValidateLogon();
        $json = [];

        if (isset($data['errors']) || isset($data['redirect'])) {
            exit(json_encode($data));
        }

        unset($this->session->data['guest']);

        // Default Shipping Address
        $this->load->model('account/address');

        if ($this->config->get('config_tax_customer') == 'payment') {
            $this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
        }

        if ($this->config->get('config_tax_customer') == 'shipping') {
            $this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
        }

        // Wishlist
        if (isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist'])) {
            $this->load->model('account/wishlist');

            foreach ($this->session->data['wishlist'] as $key => $product_id) {
                $this->model_account_wishlist->addWishlist($product_id);

                unset($this->session->data['wishlist'][$key]);
            }
        }

        // Check if customer has been approved.
        $customer_info = $this->model_account_customer->getCustomerByEmail($data['email']);

        if ($customer_info && !$customer_info['status']) {
            $json['errors']['warning'] = $this->language->get('error_approved');
        }

        if (!isset($json['errors'])) {
            if (!$this->customer->login($data['email'], $data['password'])) {
                $json['errors']['warning'] = $this->language->get('error_login');
                $this->model_account_customer->addLoginAttempt($data['email']);
            } else {
                $this->model_account_customer->deleteLoginAttempts($data['email']);
                $json['redirect'] = $this->url->link('checkout/checkout', '', true);
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function ValidateLogon()
    {
        $data = $this->request->post;

        if ((!$email = $data['login_email']) || (utf8_strlen($data['login_email']) > 96) || !preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $data['login_email'])) {
            $json['errors']['login_email'] = $this->language->get('error_email');
        }
        if ((!$password = $data['login_password']) || utf8_strlen(html_entity_decode($data['login_password'], ENT_QUOTES, 'UTF-8')) < 4) {
            $json['errors']['password'] = $this->language->get('error_password');
        }
        if ($this->customer->isLogged()) {
            $json['redirect'] = $this->url->link('account/account', '', true);
        }

        $login_info = $this->model_account_customer->getLoginAttempts($data['login_email']);
        if ($login_info && ($login_info['total'] >= $this->config->get('config_login_attempts')) && strtotime('-1 hour') < strtotime($login_info['date_modified'])) {
            $json['errors']['warning'] = $this->language->get('error_attempts');
        }

        if (isset($json['errors'])) {
            return $json;
        }
        return [
            'email' => $email,
            'password' => $password,
        ];

    }

}
