<?php



require_once __DIR__ . '/THelper.php';
require_once __DIR__ . '/TSeter.php';
require_once __DIR__ . '/StridesCustomPayment.php';

class StridesModel extends \Model{

    use  THelper, TSeter;
    protected $order_id;
    protected $order_data = [];
    protected $db_data = [];
    protected $setting = [];
    protected $redirect;
    protected $errors = [];

    

    public $custom_payment;

    protected const code = 'strides_checkout_payment';
    private const PAYMENT_FIELDS = [
        'payment_minimum', 'payment_total',
        'payment_order_status_id', 'payment_geo_zone_id',
        'payment_status', 'payment_sort_order',
    ];

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->custom_payment = new StridesCustomPayment($registry);

        $this->load->model('checkout/order');
        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');
        $this->load->model('tool/image');
        $this->load->model('setting/setting');
        $this->order_id = $this->session->data['order_id'] ?? null;
        $this->setting = $this->getConfigFields();
        $this->db_data = $this->getDates();
        

        if ( isset($this->session->data['order_id'])  ) {
            $this->setOrder_data( $this->model_checkout_order->getOrder( $this->session->data['order_id'] ));
        } 
        else {
            $this->DEFAULT_DATA();
        }
     

        // dd( $this->tpayment , 0, 1);
    }












    public function getDates(): ?array
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "strides_checkout");
        if ($query->row && strlen($query->row['custom_field']) > 5) {
            $query->row['custom_field'] = json_decode($query->row['custom_field'], true);
        }
        return $query->row;
    }

    public function getConfigFields(): ?array
    {
        return $this->model_setting_setting->getSetting('module_strides_checkout');
    }





}
