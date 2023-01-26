<?php


require_once __DIR__ . '/TTemplate.php';
require_once __DIR__ . '/TRequest.php';
require_once __DIR__ . '/StridesController.php';

// url index.php?route=extension/module/strides/checkout
class ControllerExtensionModuleStridesCheckout extends StridesController
{

    use TTemplate, TRequest;
    private $data = [];
    private $setting;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->setting = $this->model_setting_setting;
       
        $this->model
            ->setShippingMethods()
            ->setPaymentMethods()
            ->setTotals();
    }


    public function index()
    {
        
        $this
            ->accounts()
            ->address()
            ->address('shipping')
            ->customer()
            ->CustomFields()
            ->CouponVaucherReward()
            ->ShippingMethods()
            ->PaymentMethods()
            ->TextPrivacy('privacy')
            ->TextPrivacy('agree')
            ->Cart();

            $this->data['layout'] = 1;
            $this->data['is_logged_in'] = $this->customer->isLogged();
            $this->data['comment_status'] = $this->db_data['comment_status'] ?? false;
            $this->data['comment_required'] = $this->db_data['comment_required'] ?? false;
            $this->data['show_newsletter'] = $this->db_data['show_newsletter'] ?? false;
            $this->data['entry_newsletter'] = sprintf($this->language->get('entry_newsletter'), $this->config->get('config_name'));
            $this->data['show_term'] = $this->db_data['show_term'] ?? false;
            $this->data['show_privacy'] = $this->db_data['show_privacy'] ?? false;
            $this->data['text_privacy'] = $this->data['text_privacy'] ?? false;
            $this->data['text_agree'] = $this->data['text_agree'] ?? false;
            $this->data['bg_color'] = $this->db_setting['module_strides_checkout_bg_color'] ?? '#db3b77';
        
        return $this->getData();
    }

    public function setData( $data ): void
    {
        $this->data = array_merge( $this->data, $data);
    }

    public function getData()
    {
        return $this->data;
    }

    public function getDataByKey( $key )
    {
        return $this->data[$key] ?? null;
    }

}
