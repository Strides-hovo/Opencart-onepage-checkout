<?php

class ModelExtensionModuleStridesCheckout extends Model
{

    const FIELDS = [
        'id' => 'INTEGER(1) NOT NULL DEFAULT 1',
        'status' => 'BOOLEAN DEFAULT 1',
        'name' => 'VARCHAR(100) NOT NULL DEFAULT "Strides OnePage Checkout"',
        'layout' => 'INTEGER(1) NOT NULL DEFAULT 1',
        'default_country_id' => 'INTEGER(8)',
        'default_zone_id' => 'INTEGER(8)',

        'register_checkout' => 'BOOLEAN DEFAULT 1',
        'guest_checkout' => 'BOOLEAN DEFAULT 1',
        'login_checkout' => 'BOOLEAN DEFAULT 1',
        'account_open' => 'ENUM ("register","guest","login") DEFAULT "guest"',

        'product_qnty_update' => 'BOOLEAN DEFAULT 1',
        'product_remove' => 'BOOLEAN DEFAULT 1',
        'product_width' => 'INTEGER(5) DEFAULT 80',
        'product_height' => 'INTEGER(5) DEFAULT 80',

        'comment_status' => 'BOOLEAN DEFAULT 1',
        'comment_required' => 'BOOLEAN DEFAULT 1',
        'show_newsletter' => 'BOOLEAN DEFAULT 1',
        'show_privacy' => 'BOOLEAN DEFAULT 1',
        'show_term' => 'BOOLEAN DEFAULT 1',
        'show_custom_fields' => 'BOOLEAN DEFAULT 1',
        'custom_field' => 'JSON',
    ];

    public function install(): void
    {
        $data = "";
        foreach (self::FIELDS as $key => $item) {
            $data .= $key . " " . $item . ", ";
        }
        $data = rtrim($data, ',');

        $sql = "CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "strides_checkout ($data PRIMARY KEY (`id`))";
        $sql2 = "INSERT INTO " . DB_PREFIX . "strides_checkout VALUES()";
        $this->db->query($sql);
        $this->db->query($sql2);
    }

    public function uninstall(): void
    {
        $this->db->query("DROP TABLE " . DB_PREFIX . "strides_checkout");
    }

    public function getData(): ?array
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "strides_checkout");
        return $query->row;
    }

    public function update_data(): void
    {
        $data = "";
        $dates = array_intersect_key($this->request->post, self::FIELDS); // bezopasnost
        foreach ($dates as $key => $item) {
            if (is_array($item)) {
                $item = json_encode($item);
            }
            $data .= "`" . $key . "` = '" . $item . "', ";
        }
        $data = rtrim($data, ', ');
        $sql = "UPDATE " . DB_PREFIX . "strides_checkout SET $data WHERE id = 1";
       
        $this->db->query($sql);

    }

    public function setConfigData(): Model
    {
        $this->load->model('setting/setting');
        $data = array_intersect_key($this->request->post, array_flip(preg_grep('/module_strides_checkout_/', array_keys($this->request->post))));
        $this->model_setting_setting->editSetting('module_strides_checkout', $data);

        return $this;
    }

    public function getPaymentMethods(): array
    {
        $payment_methods = glob(DIR_APPLICATION . 'controller/extension/payment/*.php');
        $result = array();
        foreach ($payment_methods as $payment) {
            $payment = basename($payment, '.php');
            $this->load->language('extension/payment/' . $payment);
            $payment_status = $this->config->get('payment_' . $payment . '_status');
            if (isset($payment_status)) {
                $result[] = array(
                    'status' => $payment_status,
                    'code' => $payment,
                    'title' => $this->language->get('heading_title'),
                );
            }
        }
        return $result;
    }

    public function getShippingMethods(): array
    {
        $shipping_methods = glob(DIR_APPLICATION . 'controller/extension/shipping/*.php');
        $result = array();
        foreach ($shipping_methods as $shipping) {
            $shipping = basename($shipping, '.php');
            $this->load->language('extension/shipping/' . $shipping);
            $shipping_status = $this->config->get('shipping_' . $shipping . '_status');
            if (isset($shipping_status)) {
                $result[] = array(
                    'status' => $shipping_status,
                    'code' => $shipping,
                    'title' => $this->language->get('heading_title'),
                );
            }
        }
        return $result;
    }

}
