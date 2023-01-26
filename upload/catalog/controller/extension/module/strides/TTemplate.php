<?php

trait TTemplate
{

   


    private function accounts(): self
    {
        $data['accounts'] = $this->_accountData();
        $data['account_open'] = $this->db_data['account_open'];
        $result['accounts'] = $this->load->view('extension/module/strides_checkout/checkout/1/accounts', $data);

        $this->setData($result);

        return $this;
    }

    private function customer(): self
    {

        $data = [
            'fields' => $this->_customerData(),
            'forgotten' => $this->url->link('account/forgotten', '', true),
        ];
        if ($this->customer->isLogged()) {
            $this->load->model('account/address');
            $data['addresses'] = $this->model_account_address->getAddresses();
            $data['type'] = 'shipping';
            $result['personal'] = $this->load->view('extension/module/strides_checkout/checkout/1/personal_login', $data);
        } 
        else {
            $result['personal'] = $this->load->view('extension/module/strides_checkout/checkout/1/personal', $data);
        }

        $this->setData($result);

        return $this;
    }

    private function address(string $type = 'payment'): self
    {
        $data = [];

        $data['fields'] = $this->model->popDataToSessi(false, $type);
        // dd( $this->session->data , 0, 1);
        $data['type'] = $type;
        $data['country_id'] = $this->model->session($type . '_country_id') ?? $this->db_data['default_country_id'] ?: $this->config->get('config_country_id');
        $data['zone_id'] = $this->model->session($type . '_zone_id') ?? $this->db_data['default_zone_id'] ?: $this->config->get('config_zone_id');
        $data['countries'] = $this->model_localisation_country->getCountries();
        $data['zones'] = $this->model_localisation_zone->getZonesByCountryId($data['country_id']);
        $data['country_info'] = $this->model_localisation_country->getCountry($data['country_id']);
        if ($this->customer->isLogged()) {
            $this->load->model('account/address');
            $data['addresses'] = $this->model_account_address->getAddresses();
            
            $data['address_id'] = $this->model->session('payment_address_id') ?? null;
        }

        $result = [
            $type . 's' => $this->load->view('extension/module/strides_checkout/checkout/1/address', $data),
        ];

        $this->setData($result);

        return $this;
    }

    private function CustomFields(): self
    {
        $data['custom_fields'] = $this->_customFields();
        $data['language_id'] = $this->config->get('config_language_id');
        $result['custom_fields'] = $this->load->view('extension/module/strides_checkout/checkout/1/custom_fields', $data);

        $this->setData($result);

        return $this;
    }

    private function CouponVaucherReward(): self
    {
        $data = $this->_CouponVaucherReward();
        $result['coupon_vaucher_reward'] = $this->load->view('extension/module/strides_checkout/checkout/1/coupon_voucher_reward', $data);
        $this->setData( $result );

        return $this;
    }




    private function ShippingMethods(bool $ajax = false)
    {

        $data['shipping_methods'] =  $this->session->data['shipping_methods'];
        $data['code'] =  $this->session->data['shipping_method']['code'] ?? [];
        $result['shipping_methods'] = $this->load->view('extension/module/strides_checkout/checkout/1/shipping_methods', $data);

        $this->setData($result);

        return $ajax ? $result['shipping_methods'] : $this;
    }
  

    private function PaymentMethods(bool $ajax = false)
    {
        $data['payment_methods'] = $this->session->data['payment_methods'];

        $data['code'] = $this->request->post['payment_method'] ?? $this->session->data['payment_method']['code'] ??  null;

        $result['payment_methods'] = $this->load->view('extension/module/strides_checkout/checkout/1/payment_methods', $data);
        $this->setData( $result );

        return $ajax ? $result['payment_methods'] : $this;
    }


    private function Cart(bool $ajax = false)
    {

        try {
            if ($ajax) {
                $this->index();
            }
            
            $data['products'] = $this->model->getProducts();
            $data['vouchers'] = $this->model->getVouchers();
            $data['totals'] = $this->model->getTotals();
            $data['product_remove'] = $this->db_data['product_remove'] ?? false;
            $data['product_qnty_update'] = $this->db_data['product_qnty_update'] ?? false;
        } 
        catch ( \Throwable $th ) {
            header("Location: /");
        }

        $this->load->language('extension/module/strides_checkout');
        $result['cart'] = $this->load->view('extension/module/strides_checkout/checkout/1/cart', $data);

        $this->setData( $result );

        return $ajax ? $result['cart'] : $this;

    }



    protected function CART_TOTAL()
    {
        $cart['total'] = '';
        $count = $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0);
        if ($totals = $this->model->session('totals') ) {
            $total = end( $totals )['value'];
            $cart['total'] = sprintf($this->language->get('text_items'), $count, $this->currency->format($total, $this->session->data['currency']));
        }
       
        $this->setData( $cart );
        return $cart['total'];
    }


    private function TextPrivacy($flag): self
    {
        $data = '';
        $this->load->model('catalog/information');
        if (!$this->customer->isLogged() && $this->config->get('config_account_id') && $flag === 'privacy') {
            $information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));
            if ($information_info) {
                $data = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_checkout_id'), true), $information_info['title'], $information_info['title']);
            }
        }
        if ($this->config->get('config_checkout_id') && $flag === 'agree') {
            $information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));
            if ($information_info) {
                $data = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_account_id'), true), $information_info['title'], $information_info['title']);
            }
        }
        $result['text_' . $flag] = $data;
        $this->setData($result);

        return $this;
    }





    public function confirmButton( bool $ajax = false )
    {
        $result['payment'] = '';
        if (isset($this->session->data['payment_method']['code']) && isset($this->session->data['order_id'])  ) {
            $value = $this->session->data['payment_method']['code'];
            if ($value === 'strides_checkout_payment') {
                $result['payment'] = $this->load->view('extension/module/strides_checkout/checkout/payment/strides_checkout_payment');
            }
            else{
                $result['payment'] = $this->load->controller('extension/payment/' . $value);
            }
        }
        $this->setData( $result );
        return $ajax ? $result['payment'] : $this;
    }




    private function _recalculate()
    {
        $this
            ->CouponVaucherReward()
            ->CustomFields()
            ->ShippingMethods()
            ->PaymentMethods()
            ->Cart()
            ->confirmButton()
            ->CART_TOTAL();
    }





    public function getTemplates( array $names )
    {
        $this->_recalculate();
        // dd( ($this->model->session() )  ,0,1);
        $json = [];
        foreach ($names as $key ) {
            $json[$key] = $this->data[$key] ?? '';
        }
        return $json;
    }
}
