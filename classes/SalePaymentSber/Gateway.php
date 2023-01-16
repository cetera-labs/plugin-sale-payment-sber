<?php
namespace SalePaymentSber;

class Gateway extends \Sale\PaymentGateway\GatewayAtol {
    
    const TEST_URL = 'https://3dsec.sberbank.ru/payment/rest';
    const URL = 'https://securepayments.sberbank.ru/payment/rest';
    
    private $currency = [
        'RUR' => 810,
        'RUB' => 643
    ];

    public static function getInfo2()
	{    
		return [
			'name'        => 'Сбербанк',
			'description' => '',
			'icon'        => '/plugins/sale-payment-sber/images/icon.png',
			'params' => [		
				[
					'name'       => 'userName',
					'xtype'      => 'textfield',
					'fieldLabel' => 'Логин магазина, полученный при подключении',
					'allowBlank' => false,
				],
                [
					'name'       => 'password',
					'xtype'      => 'textfield',
					'fieldLabel' => 'Пароль магазина, полученный при подключении',
					'allowBlank' => false,
				],
				[
					'name'       => 'orderBundle',
					'xtype'      => 'checkbox',
					'fieldLabel' => 'Передача корзины товаров (кассовый чек 54-ФЗ)',
				],
                [
                    'name'       => 'paymentObject',
                    'xtype'      => 'combobox',
                    'fieldLabel' => 'Тип оплачиваемой позиции',
                    'value'      => 1,
                    'store'      => [
                        [1, 'товар'],
                        [2, 'подакцизный товар'],
                        [3, 'работа'],
                        [4, 'услуга'],
                        [5, 'ставка азартной игры'],
                        [6, 'выигрыш азартной игры'],
                        [7, 'лотерейный билет'],
                        [8, 'выигрыш лотереи'],
                        [9, 'предоставление РИД'],
                        [10, 'платёж'],
                        [11, 'агентское вознаграждение'],
                        [12, 'составной предмет расчёта'],
                        [13, 'иной предмет расчёта'],
                    ],
                ],
                [
                    'name'       => 'paymentMethod',
                    'xtype'      => 'combobox',
                    'fieldLabel' => 'Тип оплаты',
                    'value'      => 1,
                    'store'      => [
                        [1, 'полная предварительная оплата до момента передачи предмета расчёта'],
                        [2, 'частичная предварительная оплата до момента передачи предмета расчёта'],
                        [3, 'аванс'],
                        [4, 'полная оплата в момент передачи предмета расчёта'],
                        [5, 'частичная оплата предмета расчёта в момент его передачи с последующей оплатой в кредит'],
                        [6, 'передача предмета расчёта без его оплаты в момент его передачи с последующей оплатой в кредит'],
                        [7, 'оплата предмета расчёта после его передачи с оплатой в кредит'],
                    ],
                ],                
				[
					'name'       => 'taxSystem',
					'xtype'      => 'combobox',
					'fieldLabel' => 'Система налогообложения',
					'value'      => 0,
					'store'      => [
						[0, 'общая СН'],
						[1, 'упрощенная СН (доходы)'],
						[2, 'упрощенная СН (доходы минус расходы)'],
						[3, 'единый налог на вмененный доход'],
						[4, 'единый сельскохозяйственный налог'],
						[5, 'патентная СН'],
					],
				], 
				[
					'name'       => 'taxType',
					'xtype'      => 'combobox',
					'fieldLabel' => 'Ставка НДС для товаров',
					'value'      => 0,
					'store'      => [
						[0, 'без НДС'],
						[1, 'НДС по ставке 0%'],
						[2, 'НДС чека по ставке 10%'],
						[3, 'НДС чека по ставке 18%'],
						[4, 'НДС чека по расчетной ставке 10/110'],
						[5, 'НДС чека по расчетной ставке 18/118'],
                        [6, 'НДС чека по ставке 20%'],
                        [7, 'НДС чека по расчётной ставке 20/120'],
					],
				],               
                [
                    "xtype"          => 'checkbox',
                    "name"           => 'test_mode',
                    "boxLabel"       => 'Тестовый режим',
                    "inputValue"     => 1,
                    "uncheckeDvalue" => 0
                ],                
                [
					'xtype'      => 'displayfield',
					'fieldLabel' => 'URL-адрес для callback уведомлений',
					'value'      => '//'.$_SERVER['HTTP_HOST'].'/cms/plugins/sale-payment-sber/callback.php'
				],							
				
			]			
		]; 
    }
    
    public function cancel( )
    {
		$params = [
			'userName'    => $this->params['userName'],
            'password'    => $this->params['password'],
            'orderNumber' => $this->order->id,
		]; 
        
        $url = (isset($this->params["test_mode"]) && $this->params["test_mode"])?self::TEST_URL:self::URL;
        
        $client = new \GuzzleHttp\Client();
		$response = $client->request('POST', $url.'/decline.do', [
			'verify' => false,
			'form_params' => $params
		]);

		$res = json_decode($response->getBody(), true);				
		
		if (!isset($res['errorCode'])) {
			$this->saveTransaction($res['orderId'], $res);
			$this->order->setPaid(\Sale\Order::PAY_CANCEL)->save();		
		}
		else {
            if (isset($this->params["test_mode"]) && $this->params["test_mode"]) {
                print "<pre>Ошибка\n";
                print_r($res);
                print "\n\n\nДанные запроса\n";
                print_r($params);
                print "</pre>";
                die();
            }
            else {
                throw new \Exception($res['errorCode'].': '.$res['errorMessage']);
            }
		} 
    }

	public function pay( $return = '', $payParams = [] )
	{
        header('Location: '.$this->getPayUrl( $return ));
        die();          
	}
    
	public function getStatus() {
		$params = [
			'userName'    => $this->params['userName'],
            'password'    => $this->params['password'],
            'orderNumber' => $this->order->id,
		]; 

        $url = (isset($this->params["test_mode"]) && $this->params["test_mode"])?self::TEST_URL:self::URL;
        
        $client = new \GuzzleHttp\Client();
		$response = $client->request('POST', $url.'/getOrderStatusExtended.do', [
			'verify' => false,
			'form_params' => $params
		]); 

		$res = json_decode($response->getBody(), true);				
		
		if (!isset($res['errorCode'])) {
			return $res;
		}
		else {
            if (isset($this->params["test_mode"]) && $this->params["test_mode"]) {
                print "<pre>Ошибка\n";
                print_r($res);
                print "\n\n\nДанные запроса\n";
                print_r($params);
                print "</pre>";
                die();
            }
            else {
                throw new \Exception($res['errorCode'].': '.$res['errorMessage']);
            }
		}        
	}  

    public function getPayUrl( $return = '', $payParams = [] )
	{
        if (!$return) $return = \Cetera\Application::getInstance()->getServer()->getFullUrl();
        
		$params = [
			'userName'    => $this->params['userName'],
            'password'    => $this->params['password'],
            'orderNumber' => $this->order->id,
            'amount'      => $this->order->getTotal() * 100,
            'returnUrl'   => $return,
            'taxSystem'   => $this->params['taxSystem'],
            'additionalOfdParams' => []
		]; 
        
        if (isset($payParams['timeout'])) {
            $params['sessionTimeoutSecs'] = $payParams['timeout'];
        }
        
        if (isset($this->currency[$this->order->getCurrency()->code])) {
            $params['currency'] = $this->currency[$this->order->getCurrency()->code];
        }

        $phone = preg_replace('/\D/','',$this->order->getPhone());
        
        if ($this->params['orderBundle']) {
            $orderBundle = [
                'cartItems' => [
                    'items' => $this->getItems()
                ],
            ];
            if ($this->order->getEmail()) {
                $orderBundle['customerDetails'] = [
                    'email' => $this->order->getEmail()
                ];
                if ($phone) {
                    $orderBundle['customerDetails']['phone'] = $phone;
                }  
                if ($this->order->getName()) {
                    $orderBundle['customerDetails']['fullName'] = $this->order->getName();
                }
            }            
            $params['orderBundle'] = json_encode($orderBundle);
        }
        else {
            if ($this->order->getEmail()) {
                $params['email'] = $this->order->getEmail();
            }
            if ($phone) {
                $params['phone'] = $phone;
            }            
        }
        
        $url = (isset($this->params["test_mode"]) && $this->params["test_mode"])?self::TEST_URL:self::URL;
        
        $client = new \GuzzleHttp\Client();
		$response = $client->request('POST', $url.'/register.do', [
			'verify' => false,
			'form_params' => $params
		]); 

		$res = json_decode($response->getBody(), true);				
		
		if (!isset($res['errorCode'])) {
			$this->saveTransaction($res['orderId'], $res);
			return $res['formUrl'];			
		}
		else {
            if (isset($this->params["test_mode"]) && $this->params["test_mode"]) {
                print "<pre>Ошибка\n";
                print_r($res);
                print "\n\n\nДанные запроса\n";
                print_r($params);
                print "</pre>";
                die();
            }
            else {
                throw new \Exception($res['errorCode'].': '.$res['errorMessage']);
            }
		}        
    }
    
	public function getItems()
	{
        $items = [];
        
        $i = 1;
        foreach ($this->order->getProducts() as $p) {
            $items[] = [
                'positionId' => $i++,
                'name' => $p['name'],
                'quantity' => [
                    'value' => intval($p['quantity']),
                    'measure' => 'шт.'
                ],
                'itemCode' => $p['id'],
                'tax' => [
                    'taxType' => $this->params['taxType']
                ],
                'itemPrice' => $p['price'] * 100,
                'itemAttributes' => [
                    'attributes' => [
                        ['name' => 'paymentMethod', 'value' => $this->params['paymentMethod']],
                        ['name' => 'paymentObject', 'value' => $this->params['paymentObject']]
                    ]
                ]
            ];
        }
        return $items;
    }        
        
    public static function isRefundAllowed() {
        return true;
    }
    
    private function getOrderId() {
        $data = $this->getTransactions();
        
        if (!count($data)) {
            throw new \Exception('Нет информации о платеже');
        }
        $orderId = null;
        foreach ($data as $d) {
            if (isset($d['data']['orderId'])) {
                $orderId = $d['data']['orderId'];
                break;
            }
            if (isset($d['data']['mdOrder'])) {
                $orderId = $d['data']['mdOrder'];
                break;
            }            
        }
        if (!$orderId) {
            throw new \Exception('Не получилось определить параметры платежа');
        }

        return $orderId;
    }
    
    public function refund( $items = null ) {
              
		$params = [
			'userName'    => $this->params['userName'],
            'password'    => $this->params['password'],
            'orderId'     => $this->getOrderId(),
            'amount'      => $this->order->getTotal() * 100,
		];
        
        if ($items !== null) {
            $i = [];
            $amount = 0;
            foreach ($items as $key => $item) {
                if ($item['quantity_refund'] <= 0) continue;
                $price = $item['price'] * 100;
                $amount += intval($item['quantity_refund']) * $price;
                $i[] = [
                    'positionId' => $key+1,
                    'name'       => $item['name'],
                    'quantity' => [
                        'value'   => intval($item['quantity_refund']),
                        'measure' => 'шт.'
                    ],
                    'itemAmount' => intval($item['quantity_refund']) * $price,  
                    'itemCode'   => $item['id'], 
                    'itemPrice'  => $price, 
                ];
            }
            
            $params['refundItems'] = json_encode([
                'items' => $i
            ]); 
            $params['amount'] = $amount;
        }
        
        //print_r($params);
        //return;        

        $url = $this->params["test_mode"]?self::TEST_URL:self::URL;
        
        $client = new \GuzzleHttp\Client();
		$response = $client->request('POST', $url.'/refund.do', [
			'verify' => false,
			'form_params' => $params,
		]);

        $res = json_decode($response->getBody(), true);

		if (!isset($res['errorCode'])) {
            $res = $this->sendReceiptRefund( $items );
			return;		
		}
		else {
            throw new \Exception($res['errorCode'].': '.$res['errorMessage']);
		}        
        
    } 
    
}