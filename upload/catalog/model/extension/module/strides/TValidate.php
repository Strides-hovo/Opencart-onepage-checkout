<?php

trait TValidate
{

    

    private function validateAddressData( string $key = 'payment_'): self
    {
        $data = $this->request->post;
      
        if ((utf8_strlen(trim($data[$key . 'address_1'])) < 3) || (utf8_strlen(trim($data[$key . 'address_1'])) > 128)) {
            $this->errors[$key . 'address_1'] = $this->language->get('error_address_1');
        }

        if ((utf8_strlen($data[$key . 'city']) < 2) || (utf8_strlen($data[$key . 'city']) > 32)) {
            $this->errors[$key . 'city'] = $this->language->get('error_city');
        }

        $country_info = $this->model_localisation_country->getCountry($data[$key . 'country_id']);

        if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($data[$key . 'postcode'])) < 2 || utf8_strlen(trim($data[$key . 'postcode'])) > 10)) {
            $this->errors[$key . 'postcode'] = $this->language->get('error_postcode');
        }

        if ($data[$key . 'country_id'] == '') {
            $this->errors[$key . 'country'] = $this->language->get('error_country');
        }

        if (!isset($data[$key . 'zone_id']) || $data[$key . 'zone_id'] == '' || !is_numeric($data[$key . 'zone_id']) || $data[$key . 'zone_id'] == 0) {
            $this->errors[$key . 'zone'] = $this->language->get('error_zone');
        }

        return $this;
    }

    private function validateUserData( bool $register = false): self
    {
        $data = $this->request->post;
        // firstname
        if ((utf8_strlen(trim($data['firstname'])) < 1) || (utf8_strlen(trim($data['firstname'])) > 32)) {
            $this->errors['firstname'] = $this->language->get('error_firstname');
        }

        // lastname
        if ((utf8_strlen(trim($data['lastname'])) < 1) || (utf8_strlen(trim($data['lastname'])) > 32)) {
            $this->errors['lastname'] = $this->language->get('error_lastname');
        }

        // email
        if ((utf8_strlen($data['email']) > 96) || !preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $data['email'])) {
            $this->errors['email'] = $this->language->get('error_email');
        } 
        else if ($register && $this->model_account_customer->getTotalCustomersByEmail($data['email'])) {
            $this->errors['email'] = $this->language->get('error_exists');
        }

        // telephone
        if ((utf8_strlen($data['telephone']) < 3) || (utf8_strlen($data['telephone']) > 32)) {
            $this->errors['telephone'] = $this->language->get('error_telephone');
        }
       
        return $this;
    }

    private function validatePassword(): self
    {
        $data = $this->request->post;

        if ((utf8_strlen(html_entity_decode($data['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($data['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
            $this->errors['password'] = $this->language->get('error_password');
        }

        if ($data['confirm'] != $data['password']) {
            $this->errors['confirm'] = $this->language->get('error_confirm');
        }

        return $this;
    }

    private function validateMethods(string $type): self
    {
        $data = $this->request->post;
        if ((!isset($data["{$type}_method"]))) {
            $this->errors["{$type}_method"] = str_replace('&nbsp;', '', strip_tags($this->language->get("error_no_{$type}")));
            $this->redirect = $this->url->link('checkout/checkout', '', true);
        }
        
        return $this;
    }



    private function validatePrivacy(): self
    {
        
        if (!$this->customer->isLogged() && $this->config->get('config_account_id')) {
            $this->load->model('catalog/information');

            $information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));

            if ($information_info && !isset($this->request->post['privacy']) && $this->db_data['show_privacy']) {
                $this->errors['privacy'] = sprintf($this->language->get('error_agree'), $information_info['title']);
            }
        }

        if ($this->config->get('config_checkout_id')) {
            $this->load->model('catalog/information');

            $information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));

            if ($information_info && !isset($this->request->post['agree']) && $this->db_data['show_term']) {
                $this->errors['agree'] = sprintf($this->language->get('error_agree'), $information_info['title']);
            }
        }

        if ($this->config->get('config_account_id') == $this->config->get('config_checkout_id')) {
            unset($this->errors['privacy']);
        }
        return $this;
    }



    public function ValidateComment(): self
    {
        $data = ['comment' => ''];

        if ($this->db_data['comment_status']) {
            if ( ($this->db_data['comment_required'] || !isset($this->request->post['comment'])) && utf8_strlen(trim($this->request->post['comment'])) < 3 ) {
                $this->errors['comment'] = $this->language->get('error_comment');
            } 
            else {
                $data['comment'] = $this->db->escape(trim($this->request->post['comment']));
            }
        }
        
        $this->setSession($data);
        return $this;
    }



    public function ValidateProducts()
    {
        $products = $this->cart->getProducts();
		foreach ($products as $product) {
			$product_total = 0;

			foreach ($products as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}
			if ($product['minimum'] > $product_total) {
				$this->redirect = $this->url->link('checkout/checkout');
				break;
			}
		}
    }



    public function checkCart(): bool
    {
        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            return false;
        }
        // Validate minimum quantity requirements.
        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }
            if ($product['minimum'] > $product_total) {
                return false;
            }
        }

        return true;
    }

}
