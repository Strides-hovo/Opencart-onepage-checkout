<?php 

trait TConfirmData{


    private  function _paymentData(): array
    {
        return [
            'payment_firstname'       => '', 
            'payment_lastname'        => '',
            'payment_company'         => '',
            'payment_address_1'       => '',
            'payment_address_2'       => '',
            'payment_city'            => '',
            'payment_postcode'        => '',
            'payment_zone'            => '',
            'payment_zone_id'         => '',
            'payment_country'         => '',
            'payment_country_id'      => '',
            'payment_address_format'  => '',
            'payment_custom_field'    => '',
            'payment_method'          => '',
            'payment_code'            => ''
        ];
    }


    private  function _shiipingData(): array
    {
    return [
        'shipping_firstname'       => '', 
        'shipping_lastname'        => '',
        'shipping_company'         => '',
        'shipping_address_1'       => '',
        'shipping_address_2'       => '',
        'shipping_city'            => '',
        'shipping_postcode'        => '',
        'shipping_zone'            => '',
        'shipping_zone_id'         => '',
        'shipping_country'         => '',
        'shipping_country_id'      => '',
        'shipping_address_format'  => '',
        'shipping_custom_field'    => '',
        'shipping_method'          => '',
        'shipping_code'            => ''
    ];
    }


    private function _configData(): array
    {
        return [
        'invoice_prefix'      => $this->config->get('config_invoice_prefix'),
        'store_id'            => $this->config->get('config_store_id'),
        'store_name'          => $this->config->get('config_name'),
        'store_url'           => $this->config->get('config_store_id') ? $this->config->get('config_url') : HTTP_SERVER,
        'affiliate_id'        => 0,
        'commission'          => 0,
        'marketing_id'        => 0,
        'tracking'            => '',
        'language_id'         => $this->config->get('config_language_id'),
        'currency_id'         => $this->currency->getId($this->session->data['currency']),
        'currency_code'       => $this->session->data['currency'],
        'currency_value'      => $this->currency->getValue($this->session->data['currency']),
        ];
    }


    private function _costomerData(): array
    {
        return [
            'customer_id'           => $this->customer->isLogged() ? $this->customer->getId() : 0,
            'customer_group_id'     => $this->customer->isLogged() ? $this->customer->getGroupId() : $this->config->get('config_customer_group_id'),
            'firstname'             => $this->customer->isLogged() ? $this->customer->getFirstName() : '',
            'lastname'              => $this->customer->isLogged() ? $this->customer->getLastName() : '',
            'email'                 => $this->customer->isLogged() ? $this->customer->getEmail() : '',
            'telephone'             => $this->customer->isLogged() ? $this->customer->getTelephone() : '',
            'ip'                    => $this->request->server['REMOTE_ADDR'],
            'forwarded_ip'          => $this->getDataByKey($this->request->server, 'HTTP_X_FORWARDED_FOR', $this->getDataByKey($this->request->server, 'HTTP_CLIENT_IP')),
            'user_agent'            => $this->getDataByKey($this->request->server, 'HTTP_USER_AGENT'),
            'accept_language'       => $this->getDataByKey($this->request->server, 'HTTP_ACCEPT_LANGUAGE'),
            'custom_field'          => '',
            'comment'               => '',
            'total'                 => '',
        ];
    }


    private function _otherData(): array
    {
        return [
            'products'    => [],
            'totals'      => [],
            'vouchers'    => [],
        ];
    }

    
    public function DEFAULT_DATA(): self
    {
        $this->order_data =  array_merge($this->_paymentData(), $this->_shiipingData(), $this->_configData(), $this->_costomerData(), $this->_otherData() );
        return $this;
    }


    
    public function getOrder_data(): array {
        return $this->order_data;
    }

    
    public function setOrder_data(array $order_data): self {

        $_diff = array_intersect_key($order_data, $this->order_data);
        $this->order_data = array_replace($this->order_data, $_diff);
        return $this;
    }

	
}