<?php

trait THelper
{


    public function session( string $key = null)
    {  
        if($key) {
            return $this->session->data['strides_checkout'][$key] ?? null;
        } 
        else {
            return $this->session->data['strides_checkout'] ?? null;
        }
    }




    public function setSession(array $data )
    {
        if (!array_key_exists('strides_checkout', $this->session->data)) {
            $this->session->data['strides_checkout'] = [];
        }
        
        $this->session->data['strides_checkout'] = array_replace($this->session->data['strides_checkout'], $data);
    }


    public function unSession( string $key): void
    {
        unset($this->session->data['strides_checkout'][$key]);
    }



    public function clearMethods()
    {
        unset($this->session->data['shipping_method']);
        unset($this->session->data['shipping_code']);
        unset($this->session->data['payment_method']);
        unset($this->session->data['payment_code']);
        $this->setTotals();
        return $this;
    }

    public function CountryId()
    {
        if ($this->request->post) {
            $country_id = 
                $this->request->post['shipping_country_id'] ??
                $this->request->post['payment_country_id'] ??
                $this->session('payment_country_id') ?? 
                $this->db_data['default_country_id'];
        }
        else{
            $country_id = $this->session('shipping_country_id') ?? 
                          $this->session('payment_country_id') ??
                          $this->config->get('config_country_id');
        }
                
        return  (int)$country_id;
    }




    public function ZoneId()
    {
        if ($this->request->post){
            $zone_id = 
                $this->request->post['shipping_zone_id'] ??
                $this->request->post['payment_zone_id'] ??
                $this->session('shipping_zone_id') ?? $this->db_data['default_zone_id'];
        }
        else{
            $zone_id = $this->session('shipping_zone_id') ?? $this->config->get('config_zone_id');
        }
        return (int)$zone_id;
        
    }


    public function getDataByKey($array, $key)
    {
        return $array[$key] ?? null;
    }



    public function popDataToSessi(bool $flag = false, string $type = 'payment' )
    {
        $result = [];
        $fields = [
            'company',
            'address_1',
            'address_2',
            'city',
            'postcode',
            'country_id',
            'zone_id',
        ];
        $data = $this->session();
        foreach ($fields as $field) {
            if ($flag) {
                $result[$type . '_' . $field] = $data[$type . '_' . $field] ?? null;
            } 
            else {
                 $result[$field] = $data[$type . '_' . $field] ?? null;
            }
           
        }
        return $result;
    }





    protected function setCustomFields(): self
    {
        $data = [];

        if (array_key_exists('checkout_custom_field', $this->request->post) && !empty($this->request->post['checkout_custom_field'])) {
            if ($data['custom_field'] = json_encode($this->request->post['checkout_custom_field']) ?? null) {
                
                $this->setSession($data);
            }
        }
        return $this;
    }




    protected function setCustomer(): self
    {
        $request = $this->request->post;
        $data = [
            'firstname' => $request['firstname'] ?? $this->customer->getFirstName() ?? '',
            'lastname' => $request['lastname'] ?? $this->customer->getLastName()?? '',
            'email' => $request['email'] ?? $this->customer->getEmail() ?? '',
            'telephone' => $request['telephone'] ?? $this->customer->getTelephone() ?? '',
            'fax' => $request['fax'] ?? '',
            'account' => $request['account'] ?? $this->customer->isLogged() ? 'login' : 'guest',
        ];
        
        $this->setSession( $data );
        return $this;
    }



    private static $ADDRESS = [
        'company',
        'address_1',
        'address_2',
        'city',
        'postcode',
        'zone_id',
        'country_id',
    ];




    protected function setAddress(string $from = 'payment', string $to = 'payment'): self
    {
        
        $request = $this->request->post;
        $result = [];

        foreach (self::$ADDRESS as $field) {
            $result[$to . '_' . $field] = $this->getDataByKey($request, $from . '_' . $field);
        }

        $result[$to . '_firstname'] = $this->getDataByKey($request, 'firstname');
        $result[$to . '_lastname'] = $this->getDataByKey($request, 'lastname');

        $country_id = $result[$from . '_country_id'] ?? $this->CountryId();
        $zone_id = $result[$from . '_zone_id'] ?? $this->ZoneId();

        if ($country_info = $this->model_localisation_country->getCountry($country_id)) {
            $result[$to . '_country'] = $country_info['name'];
            $result[$to . '_address_format'] = $country_info['address_format'];
        }
        if ($zone_info = $this->model_localisation_zone->getZone($zone_id)) {
            $result[$to . '_zone'] = $zone_info['name'];
        }
       
        $this->setSession( $result );
 
        return $this;
    }








}