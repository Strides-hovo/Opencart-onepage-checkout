<?php

class StridesCustomPayment extends Model
{

    private $payment_data;
    private const code = 'strides_checkout_payment';
    private const PAYMENT_FIELDS = [
        'payment_minimum', 'payment_total',
        'payment_order_status_id', 'payment_geo_zone_id',
        'payment_status', 'payment_sort_order',
    ];

    
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('setting/setting');
        $this->payment_data = $this->PaymentData();
    }

    private function PaymentStatus()
    {
        $country_id = $this->CountryId();
        $zone_id = $this->ZoneId();
        $sql =
        "SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get('module_strides_checkout_payment_geo_zone_id') .
        "' AND country_id = '" . (int) $country_id . "' AND (zone_id = '" . (int) $zone_id . "' OR zone_id = '0')";
        $query = $this->db->query($sql);

        return !!$query->row && !!$this->payment_data['module_strides_checkout_payment_status'];

    }

    private function PaymentData(): ?array
    {
        $data = $this->model_setting_setting->getSetting('module_strides_checkout');
        return array_filter($data, function ($val, $key) {
            return in_array(substr($key, strlen('module_strides_checkout_')), self::PAYMENT_FIELDS);
        }, ARRAY_FILTER_USE_BOTH);

    }

    private function PaymentText()
    {
        return [
            self::code => [
                'code' => self::code,
                'title' => $this->language->get('title_' . self::code),
                'terms' => '',
                'sort_order' => $this->payment_data['module_strides_checkout_payment_sort_order'],
            ],
        ];
    }

    private function getPaymentCost()
    {
        return [
            'code' => self::code,
            'title' => $this->language->get('title_' . self::code),
            'value' => $this->calculate(),
            'sort_order' => $this->payment_data['module_strides_checkout_payment_sort_order'],
        ];
    }

    private function calculate()
    {
        $total = $this->cart->getTotal();
        $minimum = $this->payment_data['module_strides_checkout_payment_minimum'];
        $price = $this->payment_data['module_strides_checkout_payment_total'];
        return $total < $minimum ? $price : 0;
    }

    public function replacePaymentMethods(array&$method_data): void
    {
        $status = $this->PaymentStatus();
        if ($status) {
            $method_data = array_replace($method_data, $this->PaymentText());
        }
    }

    public function replaceTotals(array &$data): void
    {

        $status = $this->PaymentStatus();
        if ($status) {
            if (isset($this->session->data['payment_method']) && $this->session->data['payment_method']['code'] == self::code) {
                ['totals' => $totals] = $data;
                $total_data = array_pop($totals);
                $payment_data = $this->getPaymentCost();
                $total_data['value'] = $total_data['value'] + $payment_data['value'];
                $totals[] = $payment_data;
                $totals[] = $total_data;
                $data = [
                    'total' => $total_data['value'],
                    'totals' => $totals,
                ];
            }
        }
    
    }

    public function CountryId()
    {
        if ($this->request->post) {
            $country_id =
                $this->request->post['shipping_country_id'] ??
                $this->request->post['payment_country_id'] ??
                $this->session->data['strides_checkout']['shipping_country_id'] ??
                $this->config->get('config_country_id');
        } else {
            $country_id = 
                $this->session->data['strides_checkout']['payment_country_id'] ??
                $this->config->get('config_country_id');
        }

        return (int) $country_id;
    }

    public function ZoneId()
    {
        if ($this->request->post) {
            $zone_id =
                $this->request->post['shipping_zone_id'] ??
                $this->request->post['payment_zone_id'] ??
                $this->session->data['strides_checkout']['shipping_zone_id'] ??
                $this->config->get('config_zone_id');
        } 
        else {
            $zone_id = $this->session->data['strides_checkout']['shipping_zone_id'] ?? $this->config->get('config_zone_id');
        }
        return (int) $zone_id;

    }

}
