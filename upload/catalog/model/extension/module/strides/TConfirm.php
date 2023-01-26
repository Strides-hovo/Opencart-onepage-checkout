<?php

trait TConfirm
{

    public function confirm()
    {
        if (!$this->checkCart()) {
            $this->redirect = $this->url->link('checkout/checkout', '', true);
        }
        $request = $this->request->post;
       
        if ( $this->customer->isLogged() && isset($request['shipping_address']) && $request['shipping_address'] === 'existing' ) {
            if ( $address_id = $request['shipping_address_id'] ?? null  ) {
                $this->load->model('account/address');
                $addres_info = $this->model_account_address->getAddress($address_id);
                $this->setAddressByCustomer( $addres_info );
            }
        }
        elseif ( isset($request['account']) && $request['account'] === 'register') {
            $this->register();
        }
        
        $this
            ->setCustomer()
            ->setAddress()
            ->setCustomFields()
            ->setTotals();
         
        if (!isset($request['shipping_address_id']) ) {
            $this->validateUserData();
        }

        if (array_key_exists('shipping_address', $request) && !isset($request['shipping_address_id']) ) {
            $this
                ->validateAddressData()
                ->setAddress('payment', 'shipping');
        }
        elseif(array_key_exists('shipping_address', $request) && array_key_exists('account', $request) ){
                $this
                ->validateAddressData('shipping_')
                ->setAddress('shipping', 'shipping');
        }
        
        $this
            ->validatePrivacy()
            ->ValidateComment();

        $data = [
            'errors' => $this->errors,
            'redirect' => $this->redirect,
            'order_data' => $this->session(),
        ];
    
        if ( empty($this->errors ) ) {
            if (! isset($this->session->data['order_id']) ) {
                $this->setOrder_data( $this->session() ); // сливаем в дату
                $this->session->data['order_id'] = $this->model_checkout_order->addOrder( $this->getOrder_data() ); // сохраняем в базу
                
                unset($this->session->data['strides_checkout']); // очишаем сессиою
            } 
            else {
                $data = $this->updateOrder();
            }
        }

        return $data;
    }



    public function updateOrder()
    {
        $order_id = $this->session->data['order_id'];
        
        $order_data = $this->model_checkout_order->getOrder( $order_id );

        if ( !$order_data ) {
            unset($this->session->data['order_id']);
            return [];
        }

        $this->setSession($order_data);
        $this
            ->setShippingMethod()
            ->setPaymentMethod()
            ->setProducts()
            ->setVauchers()
            ->setMarketing();
            
        $this->setOrder_data( $this->session() ); // сливаем в дату
        if (empty($this->errors )) {
            $this->model_checkout_order->editOrder($order_id, $this->getOrder_data() );
            unset($this->session->data['strides_checkout']); // очишаем сессиою
        }
            
        $data = [
            'errors' => $this->errors,
            'redirect' => $this->redirect,
            'order_data' => $this->getOrder_data(),
        ];

        return $data;

    }
}
