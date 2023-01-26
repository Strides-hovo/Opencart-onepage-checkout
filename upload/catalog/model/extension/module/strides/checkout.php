<?php

require_once __DIR__ . '/StridesModel.php';
require_once __DIR__ . '/TValidate.php';
require_once __DIR__ . '/TAccount.php';
require_once __DIR__ . '/TGetter.php';
require_once __DIR__ . '/TConfirm.php';
require_once __DIR__ . '/TConfirmData.php';
class ModelExtensionModuleStridesCheckout extends StridesModel
{

    use TAccount, TValidate, TGetter, TConfirmData, TConfirm;

    public function __construct($registry)
    {
        parent::__construct($registry);

        // $this->clearMethods();


        // $this
        // ->updateCart();
        //'shipping_code'
        // echo 888;
      
        // ->setShippingMethod()
        // ->setPaymentMethod()
        // ->setShippingMethods()
        // ->setPaymentMethods()
        // ->setProducts()
        // ->setVauchers()
        // ->setMarketing()
        // ->setCustomer()
        // ->setTotals()
        // ;

        // dd($this->session->data , 0, 0);
        // dd( json_encode( $this->session->data )  , 0, 1);
        // echo '-------------- \n';
        
        // dd($this->session(), 0, 1);
        // unset($this->session->data['strides_checkout']);
    }

    public function updateCart()
    {
        $this
            ->setPaymentMethods()
            ->setShippingMethods()
            ->setProducts()
            ->setVauchers()
            ->setMarketing()
            ->setTotals()
        ;
    }

    public function update_address()
    {
        $request = $this->request->post;

        $this->setAddress();
        if (isset($request['shipping_address'])) {
            if ($request['shipping_address'] == '1') {
                $this->setAddress('payment', 'shipping');
            } 
            else if ($request['shipping_address'] == 'new') {
                $this->setAddress('shipping', 'shipping')->setAddress('shipping', 'payment');
            } 
            else if ($request['shipping_address'] == 'existing') {
                $address_id = $request['shipping_address_id'];
                $this->load->model('account/address');
                $addres_info = $this->model_account_address->getAddress($address_id);
                $this->setAddressByCustomer($addres_info);
            }

        } else {
            $this->setAddress('shipping', 'shipping');
        }
        $this
            ->setCustomer()
            ->setCustomFields()
            ->updateCart();
    }


    
}
