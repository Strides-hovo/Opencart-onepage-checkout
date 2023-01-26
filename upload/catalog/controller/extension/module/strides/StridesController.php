<?php

require_once __DIR__ . '/TData.php';

class StridesController extends Controller
{

    use TData;

    protected $model;
    // protected $order_data;
    protected $db_data;
    protected $db_setting;
    private const PAYMENT_FIELDS = [
        'payment_minimum', 'payment_total',
        'payment_order_status_id', 'payment_geo_zone_id',
        'payment_status', 'payment_sort_order',
    ];

    

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->model('setting/setting');
        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');
        $this->load->model('account/customer');
        $this->load->model('extension/module/strides/checkout');
        $this->load->language('extension/module/strides_checkout');

        $this->model = $this->model_extension_module_strides_checkout;
        $this->order_data = $this->model->getOrder_data();
        $this->db_data = $this->model->getDates();
        $this->db_setting = $this->model->getConfigFields();
        
    }



   




   



 




  




 



   




}
