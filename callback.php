<?php
$application->connectDb();
$application->initSession();
$application->initPlugins();

ob_start();

try {
    
	//list($oid) = explode('-x-',$_REQUEST['orderNumber']);
    $oid = $_REQUEST['orderNumber'];
	$order = \Sale\Order::getById( $_REQUEST['orderNumber'] );
	$gateway = $order->getPaymentGateway();
    
    $oid = $gateway->getOrderByTransaction( $_REQUEST['mdOrder'] );
    if ($oid != $order->id) {
        throw new \Exception('Order check failed');
    }
		
	$gateway->saveTransaction($_REQUEST['mdOrder'], $_REQUEST);
		
	// Операция подтверждена
	if  ($_REQUEST['operation'] == 'deposited' && $_REQUEST['status'] > 0) {
		$order->paymentSuccess();
        $gateway->sendReceiptSell();
	}
	
	header("HTTP/1.1 200 OK");
	print 'OK';		
	
}
catch (\Exception $e) {
	
	header( "HTTP/1.1 500 ".trim(preg_replace('/\s+/', ' ', $e->getMessage())) );
	print $e->getMessage();
	 
}

$data = ob_get_contents();
ob_end_flush();
//file_put_contents(__DIR__.'/log'.time().'.txt', $data);