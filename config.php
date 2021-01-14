<?php
if (class_exists("\Sale\Payment")) {
    \Sale\Payment::addGateway('\SalePaymentSber\Gateway');
}

if (isset($_GET['orderId'])) {
    
    try {
        $order = \Sale\Order::getByTransaction($_GET['orderId']);
        $gateway = $order->getPaymentGateway();
        if ($gateway) {
            $res = $gateway->checkStatus($_GET['orderId']);  
        }        
    }
    catch(\Exception $e) {}
}