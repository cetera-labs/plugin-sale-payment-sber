<?php
namespace SalePaymentSber;

class Gateway extends \Sale\PaymentGateway\GatewayAbstract {
    
    const TEST_URL = 'https://3dsec.sberbank.ru/payment/rest';
    const URL = 'https://securepayments.sberbank.ru/payment/rest';
    
    private $currency = [
        'RUR' => 810,
        'RUB' => 643
    ];
    
	public static function getInfo()
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
					'value'      => '//'.$_SERVER['HTTP_HOST'].'/plugins/sale_payment_sberbank/callback.php'
				],							
				
			]			
		]; 
    }

	public function pay( $return = '' )
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
        
        if (isset($this->currency[$this->order->getCurrency()->code])) {
            $params['currency'] = $this->currency[$this->order->getCurrency()->code];
        }
        
        if ($this->params['orderBundle']) {
            $params['orderBundle'] = json_encode([
                'cartItems' => [
                    'items' => $this->getItems()
                ],
            ]);
        }
        
        $url = $this->params["test_mode"]?self::TEST_URL:self::URL;
        
        $client = new \GuzzleHttp\Client();
		$response = $client->request('POST', $url.'/register.do', [
			'verify' => false,
			'form_params' => $params
		]); 

		$res = json_decode($response->getBody(), true);				
		
		if (!$res['errorCode']) {
			$this->saveTransaction($res['orderId'], $res);
			header('Location: '.$res['formUrl']);
			die();				
		}
		else {
            if ($this->params["test_mode"]) {
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
    
    public function checkStatus($orderId) {
		$params = [
			'userName'    => $this->params['userName'],
            'password'    => $this->params['password'],
            'orderId'     => $orderId,
		]; 

        $url = $this->params["test_mode"]?self::TEST_URL:self::URL;
        
        $client = new \GuzzleHttp\Client();
		$response = $client->request('POST', $url.'/getOrderStatusExtended.do', [
			'verify' => false,
			'form_params' => $params
		]); 

		$res = json_decode($response->getBody(), true);	
        
        if ($res['orderStatus'] == 2) {
            $this->order->paymentSuccess();
        }

        return $res;
    }
    
    public static function isRefundAllowed() {
        return true;
    }
    
    public function refund( $items = null ) {
    }    
    
}