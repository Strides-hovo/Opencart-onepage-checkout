<?php

trait TAccount
{

    public function register()
    {

        $this->load->language('checkout/checkout');

        // Validate if customer is already logged out.
        if ($this->customer->isLogged()) {
            $this->redirect = $this->url->link('checkout/checkout', '', true);
        }

        // Validate cart has products and has stock.
        if (!$this->checkCart() || $this->ValidateProducts()) {
            $this->redirect = $this->url->link('checkout/checkout');
        }

        // Validate minimum quantity requirements.
        if (!$this->redirect) {
            $this->load->model('account/customer');
            $this
                ->validateUserData()
                ->validateAddressData()
                ->validatePassword();
            $requestData = $this->register_helper();

            if ($this->config->get('config_account_id')) {
                $this->load->model('catalog/information');

                $information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));

                if ($information_info && !isset($this->request->post['agree'])) {
                    $this->errors['warning'] = sprintf($this->language->get('error_agree'), $information_info['title']);
                }
            }

            // Customer Group
            if (isset($this->request->post['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->post['customer_group_id'], $this->config->get('config_customer_group_display'))) {
                $customer_group_id = $this->request->post['customer_group_id'];
            } else {
                $customer_group_id = $this->config->get('config_customer_group_id');
            }

            // Custom field validation
            $this->load->model('account/custom_field');

            $custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

            foreach ($custom_fields as $custom_field) {
                if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
                    $this->errors['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                } elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
                    $this->errors['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                }
            }

            // Captcha
            if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array) $this->config->get('config_captcha_page'))) {
                $captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

                if ($captcha) {
                    $this->errors['captcha'] = $captcha;
                }
            }
        }

        if (!$this->errors && !$this->redirect && $requestData) {
            $customer_id = $this->model_account_customer->addCustomer($requestData);

            // Default Payment Address
            $this->load->model('account/address');

            $address_id = $this->model_account_address->addAddress($customer_id, $requestData);

            // Set the address as default
            $this->model_account_customer->editAddressId($customer_id, $address_id);

            // Clear any previous login attempts for unregistered accounts.
            $this->model_account_customer->deleteLoginAttempts($this->request->post['email']);

            $this->session->data['account'] = 'register';

            $this->load->model('account/customer_group');

            $customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);

            if ($customer_group_info && !$customer_group_info['approval']) {
                $this->customer->login($this->request->post['email'], $this->request->post['password']);

                $this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());

                if (!empty($this->request->post['shipping_address'])) {
                    $this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
                }
            } else {
                $this->redirect = $this->url->link('account/success');
            }

            unset($this->session->data['guest']);
        }
        $json = [
            'error' => $this->errors,
            'redirect' => $this->redirect,
        ];
        return $json;
        // $this->response->addHeader('Content-Type: application/json');
        // $this->response->setOutput(json_encode($json));
    }

    
    public function register_helper()
    {
        $result = [];
        $fields = [
            'address_1',
            'address_2',
            'city',
            'company',
            'postcode',
            'country_id',
            'zone_id',
        ];

        foreach ($fields as $field) {
            $result[$field] = $this->request->post['payment_' . $field] ?? '';
        }
        $result['firstname'] = $this->request->post['firstname'] ?? '';
        $result['lastname'] = $this->request->post['lastname'] ?? '';
        $result = array_replace($this->request->post, $result);

        return $this->errors ? false : $result;

    }

}
