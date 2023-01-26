<?php
class ControllerExtensionModuleStridesCheckout extends Controller
{
    private $error = [];
    private $data = [];

    private const PAYMENT_FIELDS = [
        'payment_minimum',  'payment_total',
        'payment_order_status_id', 'payment_geo_zone_id',
        'payment_status', 'payment_sort_order',
    ];

    public function index()
    {
        $this->load->language('extension/module/strides_checkout');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        $this->load->model('extension/module/strides_checkout');
        $this->load->model('localisation/country');

        $this->document->addStyle('view/javascript/strides/css/strides_checkout.css');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_extension_module_strides_checkout->setConfigData()->update_data();
            $this->session->data['success'] = $this->language->get('text_success');
          
            if(isset($this->request->post['save_stay']) && $this->request->post['save_stay'] == 1) {
				$this->response->redirect($this->url->link('extension/module/strides_checkout', 'user_token=' . $this->session->data['user_token'], true));
			}
            else{
				$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
			}
             print_r($this->data );
        }
        
        $this
            ->breadcrumbs()
            ->getDates()
            ->errorData(['warning', 'name', 'product_image_width', 
            'product_image_height','payment_minimum', 'payment_minimum_total',
            'payment_strides_total','payment_sort_order','payment_total'
        ]);
        // 
        if ( !!$this->error) {
            
            // $this->data = array_merge($this->data, $this->error);
        }
      
        $this->load->model('localisation/order_status');
		$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
       
        $this->load->model('localisation/geo_zone');
		$this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();


        $this->data['shipping_methods'] = $this->model_extension_module_strides_checkout->getShippingMethods();
        $this->data['payment_methods'] = $this->model_extension_module_strides_checkout->getPaymentMethods();

        // Get country list
        $this->data['countries'] = $this->model_localisation_country->getCountries();
        $this->data['user_token'] = $this->session->data['user_token'];
        $this->data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $this->data['action'] = $this->url->link('extension/module/strides_checkout', 'user_token=' . $this->session->data['user_token'], true);

        $this->data['header'] = $this->load->controller('common/header');
        $this->data['column_left'] = $this->load->controller('common/column_left');
        $this->data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/strides_checkout', $this->data));
    }

    private function breadcrumbs(): Controller
    {

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true),
        );
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/strides_checkout', 'user_token=' . $this->session->data['user_token'], true),
        );
        return $this;
    }

    public function getDates(): Controller
    {
        $this->load->model('setting/setting');
        $this->load->model('extension/module/strides_checkout');
        $data = $this->model_extension_module_strides_checkout->getData();
        $config = $this->model_setting_setting->getSetting('module_strides_checkout');
        $this->data = array_merge($this->data, $data, $config);
        return $this;
    }

    public function errorData(array $keys): Controller
    {
        foreach ($keys as $key) {
            if (isset($this->error[$key])) {
                $this->data["error_$key"] = $this->request->post[$key];
            } 
			else {
                $this->data["error_$key"] = '';
            }
        }

        return $this;
    }

    protected function validate(): bool
    {
        if (!$this->user->hasPermission('modify', 'extension/module/strides_checkout')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if ( mb_strlen($this->request->post['name'], 'utf8') < 3 || mb_strlen($this->request->post['name'], 'utf8') > 64 ) {
            $this->error['error_name'] = $this->language->get('error_name');
			$this->error['warning'] = $this->language->get('error_warning');
        }
		if (!empty($this->request->post['product_image_width'])) {
			if (!is_numeric($this->request->post['product_width'])) {
				$this->error['error_product_width'] = $this->language->get('error_product_width');
				$this->error['warning'] = $this->language->get('error_warning');
			}
		}

		if (!empty($this->request->post['product_height'])) {
			if (!is_numeric($this->request->post['product_height'])) {
				$this->error['error_product_height'] = $this->language->get('error_product_height');
				$this->error['warning'] = $this->language->get('error_warning');
			}
		}

        foreach (self::PAYMENT_FIELDS as $key ) {
            if ( empty($this->request->post["module_strides_checkout_$key" ] ) ) {
                $this->error['error_'. $key] = $this->language->get('error_'. $key);
            }
            
        }
        return !$this->error;
    }

    public function install(): void
    {
        $this->load->model('setting/setting');
        $this->load->model('extension/module/strides_checkout');
        $setting_data = [
            'module_strides_checkout_status' => 1,
            'module_strides_checkout_coupon_register_status' => 1,
            'module_strides_checkout_coupon_guest_status' => 1,
            'module_strides_checkout_coupon_login_status' => 1,

            'module_strides_checkout_reward_register_status' => 1,
            'module_strides_checkout_reward_guest_status' => 1,
            'module_strides_checkout_reward_login_status' => 1,

            'module_strides_checkout_voucher_register_status' => 1,
            'module_strides_checkout_voucher_guest_status' => 1,
            'module_strides_checkout_voucher_login_status' => 1,
        ];

        $this->model_setting_setting->editSetting('module_strides_checkout', $setting_data);
        $this->model_extension_module_strides_checkout->install();
    }

    public function uninstall() : void
    {
        $this->load->model('setting/setting');
        $this->load->model('extension/module/strides_checkout');
        $this->model_setting_setting->deleteSetting('module_strides_checkout');
        $this->model_setting_module->deleteModulesByCode('module_strides_checkout');
        $this->model_extension_module_strides_checkout->uninstall();
    }
}
