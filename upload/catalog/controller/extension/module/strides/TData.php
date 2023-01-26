<?php

trait TData
{

    private static $custom_fields = [
        'receipt' => [1 => ['title' => 'ppp2']],
		'invoice' => [
			1 => [
				'title' => 'ppp',
				'fields' => [
                        [
                            'name' => 'tax_office',
                            'title' => '%s'
                        ],
                        [
                            'name' => 'number_of_vat',
                            'title' => '%s'
                        ],
                        [
                            'name' => 'activity',
                            'title' => '%s'
                        ],

                    ]
				]
			],
    ];

    public function _accountData()
    {
        return array_keys(array_filter([
            'register_checkout' => $this->db_data['register_checkout'],
            'login_checkout' => $this->db_data['login_checkout'],
            'guest_checkout' => $this->db_data['guest_checkout'],
        ]));
    }



    public function _customerData()
    {
        $result = [];
        $fields = [
            'firstname', 'lastname', 'email', 'telephone', 'postcode',
        ];
        $data = $this->model->session() ?? [];
        foreach ($fields as $field) {
            $result[$field] = $data[$field] ?? null;
        }
        return $result;
    }

    public function _customFields(): array
    {
        $custom_fields = [
            'receipt' => ['title' => 'Receipt'],
            'invoice' => [
                    'title' => 'Invoice',
                    'fields' => [
                            [
                                'name' => 'tax_office',
                                'title' => $this->language->get('text_tax_office')
                            ],
                            [
                                'name' => 'number_of_vat',
                                'title' => $this->language->get('text_number_of_vat')
                            ],
                            [
                                'name' => 'activity',
                                'title' => $this->language->get('text_activity')
                            ],

                        ]
                    ]
                
            ];
        return $this->db_data['custom_field'] ?? $custom_fields;
    }

    public function _CouponVaucherReward(): array
    {
        $account = $this->customer->isLogged() ? 'login' : 'guest';
        $vaucher_status = $this->config->get('total_voucher_status') && $this->db_setting["module_strides_checkout_voucher_{$account}_status"] ?? false;
        $coupon_status = $this->config->get('total_coupon_status') && $this->db_setting["module_strides_checkout_coupon_{$account}_status"] ?? false;
        $reward_status = $this->config->get('total_reward_status') && $this->db_setting["module_strides_checkout_reward_{$account}_status"] ;
        
        $data = [
            'voucher_status' => $vaucher_status,
            'coupon_status' => $coupon_status,
            'reward_status' => $reward_status,
            'voucher' => $this->session->data['voucher'] ?? null,
            'coupon' => $this->session->data['coupon'] ?? null,
            'reward' => $this->getRewardPoints(),
        ];
        return $data;
    }


   

    public function getRewardPoints()
    {
        $points = $this->customer->getRewardPoints();
        $points_total = 0;
        foreach ($this->cart->getProducts() as $product) {
            if ($product['points']) {
                $points_total += $product['points'];
            }
        }
        if ($points > $points_total) {
            return $this->session->data['reward'] ?? 0;
        } 
        else {
            return $points > $points_total;
        }

    }

    public function getAddressById()
    {
        $address_id = $this->customer->getAddressId();
        $this->load->model('account/address');
        if ($address_id) {
            $address_info = $this->model_account_address->getAddress($address_id);
        } else {
            $addresses = $this->model_account_address->getAddresses();
            $address_id = is_array($addresses) && count($addresses) ? array_key_first($addresses) : null;
            $address_info = $address_id !== null ? $addresses[$address_id] : null;
        }
        return $address_info;
    }













}
