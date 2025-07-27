<?php


	class Controllers{
		
		private $db;
		private $coreModel;

		public function __construct($db){
			$this->db = $db;
			$this->coreModel = new CoreModels($db);
		}
		
		public function fetchDataPlans(){
			
			$data = array();
			$network = $_POST['network'];
			$planetf_code = $this->coreModel->network_detection($network);
			$planetf_url = "https://softconnet.com.ng/api/reseller/list";
			$ringo_url = "https://www.api.ringo.ng/api/agent/p2";
			
			$planetf_response = $this->coreModel->curlRequest($planetf_url, 'POST', ['service' => 'data', 'coded' => $planetf_code ], ['Authorization: ' . PLANETF_API_KEY ]);
			//error_log(print_r($planetf_response['response']['data'],TRUE));
			$postData = json_encode([
                "serviceCode" => 'DTA',
				"network" => $network
            ]);

            $headers = [
                'Content-Type: application/json',
				'email: ' . RINGO_API_EMAIL, // Use defined constant
				'password: ' . RINGO_API_PASSWORD // Use defined constant
            ];

			$ringo_response = $this->coreModel->curlRequest($ringo_url, 'POST', $postData, $headers);
			$ringo_resp = isset($ringo_response['response']['message']) ? [] : $ringo_response['response'];
			foreach($ringo_resp ?? [] as $r){
				$increasedPrice = $this->coreModel->calculateIncreasedPrice($r['price']);
				$com  = $increasedPrice - $r['price'];
				$calculate_com = $this->coreModel->calculatePercentage($com, 40);
				$data[] = array(
					"value" => $r['product_id'],
					"type" => $r['category'],
					"name" => $r['allowance'],
					"com" => $calculate_com,
					"price" => ceil($increasedPrice),
					"api" => 'ringo',
					"profit" => $com - $calculate_com
				);
			}
			//error_log(print_r($ringo_response,TRUE));
			$p = $this->coreModel->filterArrayByPrice($planetf_response['response']['data'] ?? [], 'level1');
			$p = array_column(array_reverse($p), null, 'code');
			//error_log(print_r($p,TRUE));
			foreach($p as $d){
				$increasedPrice = $this->coreModel->calculateIncreasedPrice($d['level1']);
				$com  = $increasedPrice - $d['level1'];
				$calculate_com = $this->coreModel->calculatePercentage($com, 40);
				$data[] = array(
					"value" => $d['code'],
					"type" => $d['type'],
					"name" => $d['name'],
					"com" => $calculate_com,
					"price" => ceil($increasedPrice),
					"api" => 'planetf',
					"profit" => $com - $calculate_com
				);
			}
			
			return $data;
			
		}


			public function fetchCablePlanList() {
				$data = [
					"status" => false,
					"message" => "Invalid request",
					"cable_plans" => [],
					"cable_addons" => []
				];

				$cable = [];
				$addons = [];

				// Validate input
				if (!isset($_POST['network']) || !isset($_POST['smart_card'])) {
					$data['message'] = "Missing required parameters";
					return $data;
				}

				$network = trim($_POST['network']);
				$smart_card = trim($_POST['smart_card']);

				// Predefined endpoints
				$planetf_url = "https://softconnet.com.ng/api/reseller/validate";
				$ringo_url = "https://www.api.ringo.ng/api/agent/p2";

				// Simulate DSTV only
				if (strtoupper($network) === "DSTV") {
					// Normally sent as JSON to Ringo
					$postData = json_encode([
						"serviceCode" => "V-TV",
						"type" => "DSTV",
						"smartCardNo" => $smart_card
					]);

					$headers = [
						'Content-Type: application/json',
						'email: ' . RINGO_API_EMAIL, // Use defined constant
						'password: ' . RINGO_API_PASSWORD // Use defined constant
					];

					$addonData = json_encode([
						"code" => "PRWE36"
					]);

					$cableplan_response = $this->coreModel->curlRequest($ringo_url, 'POST', $postData, $headers);
					$addon_response = $this->coreModel->curlRequest("https://www.api.ringo.ng/api/dstv/addon", 'POST', $addonData, $headers);

					// Mocked responses for testing
					/*$cableplan_response = [
						'response' => [
							'customerName' => 'AZEEZ FALETI',
							'product' => [
								[
									"name" => "DStv Padi",
									"code" => "ng_dstv_nltese36",
									"month" => 1,
									"price" => 4400,
									"period" => 1,
									"old_code" => ''
								],
								[
									"name" => "DStv Compact",
									"code" => "ng_dstv_compe36",
									"month" => 1,
									"price" => 19000,
									"period" => 1,
									"old_code" => ''
								],
								[
									"name" => "DStv Confam",
									"code" => "ng_dstv_nnj2e36",
									"month" => 1,
									"price" => 11000,
									"period" => 1,
									"old_code" => ''
								],
								[
									"name" => "DStv Premium",
									"code" => "ng_dstv_prwe36",
									"month" => 1,
									"price" => 44500,
									"period" => 1,
									"old_code" => ''
								],
							],
							'message' => 'Successful',
							'status' => 200,
							'smartCardNo' => '7039460893',
							'type' => 'DSTV'
						],
						'httpCode' => 200,
						'error' => null
				];

				$addon_response = [
					"response" => [
						"message" => "Successful",
						"status" => 200,
						"product" => [
							[
								"name" => "Compact + Showmax",
								"code" => "ng_dstv_showcompe36",
								"month" => 1,
								"price" => 20750,
								"period" => 1
							],
							[
								"name" => "ASIAE36 + SHOWMAXE36",
								"code" => "ng_dstv_showasiae36",
								"month" => 1,
								"price" => 18400,
								"period" => 1
							],
							[
								"name" => "Padi + Showmax",
								"code" => "ng_dstv_shownltese36",
								"month" => 1,
								"price" => 7900,
								"period" => 1
							],
							[
								"name" => "DStv French Plus",
								"code" => "ng_dstv_frn15e36",
								"month" => 1,
								"price" => 24500,
								"period" => 1
							],
							[
								"name" => "PremiumFrench + Showmax",
								"code" => "ng_dstv_showprwfrnse36",
								"month" => 1,
								"price" => 69000,
								"period" => 1
							],
							[
								"name" => "DStv French Touch",
								"code" => "ng_dstv_frn7e36",
								"month" => 1,
								"price" => 7000,
								"period" => 1
							],
							[
								"name" => "DStv HDPVR Access Service",
								"code" => "ng_dstv_hdpvre36",
								"month" => 1,
								"price" => 6000,
								"period" => 1
							],
							[
								"name" => "GWALLE36 + SHOWMAXE36",
								"code" => "ng_dstv_showgwalle36",
								"month" => 1,
								"price" => 7300,
								"period" => 1
							],
							[
								"name" => "Premium + Showmax",
								"code" => "ng_dstv_showprwe36",
								"month" => 1,
								"price" => 44500,
								"period" => 1
							],
							[
								"name" => "Great Wall Africa Bouquet",
								"code" => "ng_dstv_gwalle36",
								"month" => 1,
								"price" => 3800,
								"period" => 1
							],
							[
								"name" => "CompactPlus + Showmax",
								"code" => "ng_dstv_showcomple36",
								"month" => 1,
								"price" => 31750,
								"period" => 1
							],
							[
								"name" => "Confam + Showmax",
								"code" => "ng_dstv_shownnj2e36",
								"month" => 1,
								"price" => 12750,
								"period" => 1
							],
							[
								"name" => "DStv India Add-on",
								"code" => "ng_dstv_asiadde36",
								"month" => 1,
								"price" => 14900,
								"period" => 1
							],
							[
								"name" => "DSTV MOBILE",
								"code" => "ng_dstv_mobmaxi",
								"month" => 1,
								"price" => 790,
								"period" => 1
							],
							[
								"name" => "DStv Movie Bundle Add-on E36",
								"code" => "ng_dstv_movies",
								"month" => 1,
								"price" => 2500,
								"period" => 1
							],
							[
								"name" => "DStv Movie Bundle Add-on E36",
								"code" => "ng_dstv_moviese36",
								"month" => 1,
								"price" => 3500,
								"period" => 1
							],
							[
								"name" => "PRWASIE36  + SHOWMAXE36",
								"code" => "ng_dstv_showprwasie36",
								"month" => 1,
								"price" => 50500,
								"period" => 1
							],
							[
								"name" => "Yanga + Showmax",
								"code" => "ng_dstv_shownnj1e36",
								"month" => 1,
								"price" => 7750,
								"period" => 1
							]
						]
					],
					"httpCode" => 200,
					"error" => null
				];*/

					// Process products
					$cable = [];
					$addons = [];

					// Ensure 'product' exists and is an array
					if (!empty($cableplan_response['response']['product']) && is_array($cableplan_response['response']['product'])) {
						foreach ($cableplan_response['response']['product'] as $r) {
							$cable[] = [
								"value" => $r['code'] ?? '',
								"name" => $r['name'] ?? '',
								"price" => $r['price'] ?? 0
							];
						}
					}

					if (!empty($addon_response['response']['product']) && is_array($addon_response['response']['product'])) {
						foreach ($addon_response['response']['product'] as $a) {
							$addons[] = [
								"value" => $a['code'] ?? '',
								"name" => $a['name'] ?? '',
								"price" => $a['price'] ?? 0
							];
						}
					}

					// Final response
					$data = [
						"status" => ($cableplan_response['response']['status'] ?? 0) == 200,
						"message" => $cableplan_response['response']['message'] ?? 'Unknown response',
						"customerName" => $cableplan_response['response']['customerName'] ?? '',
						"smartCardNo" => $cableplan_response['response']['smartCardNo'] ?? '',
						"cable_plans" => $cable,
						"cable_addons" => $addons
					];

					return $data;

				} 

				if(strtoupper($network) === "GOTV"){

					$headers = [
						'Authorization: '.PLANETF_API_KEY
					];

					$postData = ['service' => 'tv', 'coded' => $network, 'phone' => $smart_card];

					$validate_response = $this->coreModel->curlRequest("https://softconnet.com.ng/api/reseller/validate", 'POST', $postData, $headers);

				
					//$p = $this->coreModel->filterArrayByProperty($res['response']['data'] ?? [], 'type', 'gotv');
					foreach($validate_response['response']['details']['product'] ?? [] as $d){
						
						$cable[] = array(
							"value" => $d['code'] ?? '',
							"name" => $d['name'] ?? '',
							"price" => $d['price'] ?? 0
						);

					} 

					$data = [
						"status" => ($validate_response['response']['success'] ?? 0) == 1,
						"message" => $validate_response['response']['details']['message'] ?? "Unknown Error",
						"customerName" => $validate_response['response']['data'] ?? '',
						"smartCardNo" => $smart_card ?? '',
						"cable_plans" => $cable,
						"cable_addons" => $validate_response
					];

					return $data;
				}

				if(strtoupper($network) === "STARTIMES"){

					$postData = json_encode([
						"serviceCode" => "V-TV",
						"type" => "STARTIMES",
						"smartCardNo" => $smart_card
					]);

					$headers = [
						'Content-Type: application/json',
						'email: ' . RINGO_API_EMAIL, // Use defined constant
						'password: ' . RINGO_API_PASSWORD // Use defined constant
					];

					$cableplan_response = $this->coreModel->curlRequest($ringo_url, 'POST', $postData, $headers);

					/*$cableplan_response = [
					"response" => [
						"customerName" => "Isaac Aboshi",
						"product" => [
							["name" => "DTT_Nova Weekly", "amount" => 700],
							["name" => "DTT_Nova Monthly", "amount" => 2100],
							["name" => "DTT_Basic Weekly", "amount" => 1400],
							["name" => "DTT_Basic Monthly", "amount" => 4000],
							["name" => "DTT_Classic Weekly", "amount" => 2000],
							["name" => "DTT_Classic Monthly", "amount" => 6000],
							["name" => "DTT_Super_T Weekly", "amount" => 3200],
							["name" => "DTT_Super_T Monthly", "amount" => 9500],
							["name" => "DTH_Nova Weekly", "amount" => 700],
							["name" => "DTH_Nova Monthly", "amount" => 2100],
							["name" => "DTH_Basic Weekly", "amount" => 1700],
							["name" => "DTH_BAsic Monthly", "amount" => 5100],
							["name" => "DTH_Classic Weekly", "amount" => 2500],
							["name" => "DTH_Classic Monthly", "amount" => 7400],
							["name" => "DTH_Super Weekly", "amount" => 3300],
							["name" => "DTH_Super Monthly", "amount" => 9800],
							["name" => "Combo_Smart(Basic) Weekly", "amount" => 1700],
							["name" => "Combo_Smart(Basic) Monthly", "amount" => 5100],
							["name" => "Combo_Super Weekly", "amount" => 3300],
							["name" => "Combo_Super Monthly", "amount" => 9800],
							["name" => "Combo_Classic Weekly", "amount" => 2500],
							["name" => "Combo_Classic Monthly", "amount" => 7400],
							["name" => "DTH_Chinese Monthly", "amount" => 21000],
							["name" => "DTH_Global Weekly", "amount" => 7000],
							["name" => "DTH_Global Monthly", "amount" => 21000],
							["name" => "SHS Payment Weekly", "amount" => 2800],
							["name" => "SHS Payment Weekly", "amount" => 4620],
							["name" => "SHS Payment Weekly", "amount" => 4900],
							["name" => "SHS Payment Weekly", "amount" => 9100],
							["name" => "SHS Payment Monthly", "amount" => 12000],
							["name" => "SHS Payment Monthly", "amount" => 19800],
							["name" => "SHS Payment Monthly", "amount" => 21000],
							["name" => "SHS Payment Monthly", "amount" => 39000],
						],
						"message" => "Successful",
						"status" => 200,
						"smartCardNo" => "01831595848",
						"type" => "STARTIMES"
					],
					"httpCode" => 200,
					"error" => null
				];*/

					
					//$p = $this->coreModel->filterArrayByProperty($res['response']['data'] ?? [], 'type', 'gotv');
					foreach($cableplan_response['response']['product'] ?? [] as $k => $d){
						
						$cable[] = array(
							"value" => $d['code'] ?? $k,
							"name" => $d['name'] ?? '',
							"price" => $d['amount'] ?? 0
						);

					} 

					$data = [
						"status" => ($cableplan_response['response']['status'] ?? 0) == 200,
						"message" => $cableplan_response['response']['message'] ?? "Unknown Error",
						"customerName" => $cableplan_response['response']['customerName'] ?? '',
						"smartCardNo" => $smart_card ?? '',
						"cable_plans" => $cable,
						"cable_addons" => ''
					];

					return $data;
				}

				if(strtoupper($network) === "SHOWMAX"){

					$postData = json_encode([
						"serviceCode" => "SHVAL",
						"type" => "SHOWMAX"
					]);

					$headers = [
						'Content-Type: application/json',
						'email: ' . RINGO_API_EMAIL, // Use defined constant
						'password: ' . RINGO_API_PASSWORD // Use defined constant
					];

					$cableplan_response = $this->coreModel->curlRequest($ringo_url, 'POST', $postData, $headers);

					/*$cableplan_response = [
						"response" => [
							"status" => 200,
							"message" => "Successful",
							"products" => [
								[
									"code" => "mobile_only_1",
									"period" => 1,
									"price" => 1600,
									"subscriptionPeriod" => 1,
									"type" => "mobile_only_1",
									"name" => "Entertainment (Mobile) for 1 month"
								],
								[
									"code" => "full_1",
									"period" => 1,
									"price" => 3500,
									"subscriptionPeriod" => 1,
									"type" => "full_1",
									"name" => "Entertainment (All Devices) for 1 month"
								],
								[
									"code" => "sports_mobile_only_1",
									"period" => 1,
									"price" => 4000,
									"subscriptionPeriod" => 1,
									"type" => "sports_mobile_only_1",
									"name" => "Entertainment (Mobile) + PL (Mobile) for 1 month"
								],
								[
									"code" => "full_sports_mobile_only_1",
									"period" => 1,
									"price" => 5400,
									"subscriptionPeriod" => 1,
									"type" => "full_sports_mobile_only_1",
									"name" => "Entertainment (All Devices) + PL (Mobile) Bundle for 1 month"
								],
								[
									"code" => "sports_only_1",
									"period" => 1,
									"price" => 3200,
									"subscriptionPeriod" => 1,
									"type" => "sports_only_1",
									"name" => "Premier League (Mobile) for 1 month"
								],
								[
									"code" => "sports_only_3",
									"period" => 3,
									"price" => 9600,
									"subscriptionPeriod" => 3,
									"type" => "sports_only_3",
									"name" => "Premier League (Mobile) for 3 months"
								],
								[
									"code" => "mobile_only_3",
									"period" => 3,
									"price" => 3800,
									"subscriptionPeriod" => 3,
									"type" => "mobile_only_3",
									"name" => "Entertainment (Mobile) for 3 months"
								],
								[
									"code" => "full_3",
									"period" => 3,
									"price" => 8400,
									"subscriptionPeriod" => 3,
									"type" => "full_3",
									"name" => "Entertainment (All Devices) for 3 months"
								],
								[
									"code" => "sports_mobile_only_3",
									"period" => 3,
									"price" => 12000,
									"subscriptionPeriod" => 3,
									"type" => "sports_mobile_only_3",
									"name" => "Entertainment (Mobile) + PL (Mobile) Bundle for 3 months"
								],
								[
									"code" => "full_sports_mobile_only_3",
									"period" => 3,
									"price" => 16200,
									"subscriptionPeriod" => 3,
									"type" => "full_sports_mobile_only_3",
									"name" => "Entertainment (All Devices) + PL (Mobile) Bundle for 3 months"
								],
								[
									"code" => "mobile_only_6",
									"period" => 6,
									"price" => 6700,
									"subscriptionPeriod" => 6,
									"type" => "mobile_only_6",
									"name" => "Entertainment (Mobile) for 6 months"
								],
								[
									"code" => "full_6",
									"period" => 6,
									"price" => 14700,
									"subscriptionPeriod" => 6,
									"type" => "full_6",
									"name" => "Entertainment (All Devices) for 6 months"
								],
								[
									"code" => "full_sports_mobile_only_6",
									"period" => 6,
									"price" => 32400,
									"subscriptionPeriod" => 6,
									"type" => "full_sports_mobile_only_6",
									"name" => "Entertainment (All Devices) + PL (Mobile) Bundle for 6 months"
								],
								[
									"code" => "sports_mobile_only_6",
									"period" => 6,
									"price" => 24000,
									"subscriptionPeriod" => 6,
									"type" => "sports_mobile_only_6",
									"name" => "Entertainment (Mobile) + PL (Mobile) Bundle for 6 months"
								],
								[
									"code" => "sports_only_6",
									"period" => 6,
									"price" => 18200,
									"subscriptionPeriod" => 6,
									"type" => "sports_only_6",
									"name" => "Premier League (Mobile) for 6 months"
								]
							]
						],
						"httpCode" => 200,
						"error" => null
					];*/

					foreach($cableplan_response['response']['products'] ?? [] as $k => $d){
						
						$cable[] = array(
							"value" => $d['code'] ?? '',
							"name" => $d['name'] ?? '',
							"price" => $d['price'] ?? 0,
							"period" => $d['period'] ?? 1
						);

					}

					$data = [
						"status" => ($cableplan_response['response']['status'] ?? 0) == 200,
						"message" => $cableplan_response['response']['message'] ?? "Unknown Error",
						"customerName" => $cableplan_response['response']['customerName'] ?? '',
						"smartCardNo" => $smart_card ?? '',
						"cable_plans" => $cable,
						"cable_addons" => ''
					];

					return $data;

				}
			}

			public function fetchElectricity() {

					$data = $planetfnetworks = [];

					// Fetch from PlanetF
					$headers = [
						'Authorization: ' . PLANETF_API_KEY
					];

					$postData = ['service' => 'electricity'];

					$fetchnetworks = $this->coreModel->curlRequest("https://softconnet.com.ng/api/reseller/list", 'POST', $postData, $headers);

					$image =  [
						'IBEDC' => '/ibadan.png',
						'EKEDC' => '/eko.png',
						'KEDC' => '/kano.png',
						'PHED' => '/portharcourt.png',
						'PHEDC' => '/portharcourt.png',
						'AEDC' => '/abuja.png',
						'JEDC' => '/jos.png',
						'IKEDC' => '/ikeja.png',
						'KAEDC' => '/kaduna.png',
						'EEDC' => '/enugu.png',
					];

					foreach ($fetchnetworks['response']['data'] ?? [] as $i => $fetchnetwork) {
						$planetfnetworks[] = [
							"name" => $fetchnetwork['name'] ?? '',
							"code" => $fetchnetwork['code'] ?? '',
							"value" => $fetchnetwork['name'].' - '. 2,
							"api" => 'planetf',
							"description" => 'Network 2',
							"img" => $image[$fetchnetwork['code']] ?? '/default.png'
						];
					}

					// Add predefined Ringo networks
					$ringoNetworks = [
						["name" => "AEDC", "code" => "AEDC", "value" => "AEDC - 1", "api" => "ringo", "description" => "Network 1", 'img' => '/abuja.png'],
						["name" => "JEDC", "code" => "JEDC", "value" => "JEDC - 1", "api" => "ringo", "description" => "Network 1", 'img' => '/jos.png'],
						["name" => "KAEDC", "code" => "KAEDC", "value" => "KAEDC - 1", "api" => "ringo", "description" => "Network 1", 'img' => '/kaduna.png'],
						["name" => "IBEDC", "code" => "IBEDC", "value" => "IBEDC - 1", "api" => "ringo", "description" => "Network 1", 'img' => '/ibadan.png'],
						["name" => "IKEDC", "code" => "IKEDC", "value" => "IKEDC - 1", "api" => "ringo", "description" => "Network 1", 'img' => '/ikeja.png'],
						["name" => "EKEDC", "code" => "EKEDC", "value" => "EKEDC - 1", "api" => "ringo", "description" => "Network 1", 'img' => '/eko.png'],
						["name" => "EEDC", "code" => "EEDC", "value" => "EEDC - 1", "api" => "ringo", "description" => "Network 1", 'img' => '/enugu.png'],
						["name" => "KEDC", "code" => "KEDC", "value" => "KEDC - 1", "api" => "ringo", "description" => "Network 1", 'img' => '/kano.png'],
						["name" => "PHED", "code" => "PHED", "value" => "PHED - 1", "api" => "ringo", "description" => "Network 1", 'img' => '/portharcourt.png'],
					];

					$data = array_merge($ringoNetworks, $planetfnetworks);

					return $data;
				}

				public function ValidateElectricityCard() {
					// Validate required inputs
					$requiredParams = ['disco', 'meter_no', 'api'];
					foreach ($requiredParams as $param) {
						if (empty($_POST[$param])) {
							return ['status' => false, 'message' => "Missing required parameter: $param"];
						}
					}

					$disco = trim($_POST['disco']);
					$meterNo = trim($_POST['meter_no']);
					$api = strtolower(trim($_POST['api']));

					try {
						switch ($api) {
							case 'ringo':
								return $this->coreModel->validateWithRingo($disco, $meterNo);
							case 'planetf':
								return $this->coreModel->validateWithPlanetF($disco, $meterNo);
							default:
								return ['status' => false, 'message' => 'Invalid API provider specified'];
						}
					} catch (Exception $e) {
						// Log the error for debugging
						error_log("Electricity validation error: " . $e->getMessage());
						return ['status' => false, 'message' => 'Service temporarily unavailable'];
					}
				}

			public function cablePay() {

				$data = ["status" => false, "message" => "Invalid request"];

				$network = strtoupper(trim($_POST['cable_name'] ?? ''));
				$username = $this->coreModel->decryptCookie($this->coreModel->sanitizeInput($_POST['username']));
				$userInfo = $this->coreModel->fetchuserinfo($username);

				if (!$userInfo) return ["status" => false, "message" => "Ooops, Username not found"];

				$sponsor = $this->coreModel->fetchuserinfo($username);
				$sponsor_username = $sponsor['sponsor'] ?? '';

				$acct_balance = $userInfo['account'];
				$total_price = floatval($_POST['total_price'] ?? 0);
				if ($acct_balance < $total_price) {
					return ["status" => false, "message" => "Insufficient Wallet balance"];
				}

				$commission = 0;

				$commonData = [
					'type' => $_POST['cable_name'] ?? '',
					'cable_code' => $_POST['network_code'] ?? '',
					'cable_name' => $_POST['network_name'] ?? '',
					'smart_card' => $_POST['smart_card'] ?? '',
					'addon_name' => $_POST['addon_name'] ?? '',
					'addon_code' => $_POST['addon_code'] ?? '',
					'customer_name' => $_POST['customer_name'] ?? '',
					'period' => $_POST['period'] ?? 1,
					'total_price' => $total_price,
					'request_id' => $this->coreModel->generateRandomString(16),
				];

				$ringoHeaders = [
					'Content-Type: application/json',
					'email: ' . RINGO_API_EMAIL, // Use defined constant
					'password: ' . RINGO_API_PASSWORD // Use defined constant
				];

				$planetfHeaders = ['Authorization: ' . PLANETF_API_KEY];

				switch ($network) {
					case "DSTV":
						$postData = [
							"serviceCode" => "P-TV",
							"type" => $commonData['type'],
							"smartCardNo" => $commonData['smart_card'],
							"name" => $commonData['cable_name'],
							"code" => $commonData['cable_code'],
							"period" => $commonData['period'],
							"request_id" => $commonData['request_id'],
							"hasAddon" => !empty($commonData['addon_code'])
						];

						if (!empty($commonData['addon_code'])) {
							$postData["addondetails"] = [
								"name" => $commonData['addon_name'],
								"addoncode" => $commonData['addon_code']
							];
						}

						
						$response = $this->coreModel->curlRequest('https://www.api.ringo.ng/api/agent/p2', 'POST', json_encode($postData), $ringoHeaders);
						$success = ($response['response']['status'] ?? 0) == 200;
						$gateway = 'ringo';
						break;

					case "GOTV":
						$payload = [
							'service' => 'tv',
							'coded' => $commonData['cable_code'],
							'phone' => $commonData['smart_card']
						];

						$response = $this->coreModel->curlRequest('https://softconnet.com.ng/api/reseller/pay', 'POST', $payload, $planetfHeaders);
						$success = ($response['response']['success'] ?? 0) == 1;
						$gateway = 'planetf';
						break;

					case "STARTIMES":
						$postData = json_encode([
							"serviceCode" => "P-TV",
							"type" => $commonData['cable_name'],
							"smartCardNo" => $commonData['smart_card'],
							"price" => $commonData['total_price'],
							"request_id" => $commonData['request_id'],
						]);

						$response = $this->coreModel->curlRequest('https://www.api.ringo.ng/api/agent/p2', 'POST', $postData, $ringoHeaders);
						$success = ($response['response']['status'] ?? 0) == 200;
						$gateway = 'ringo';
						break;

					case "SHOWMAX":
						$postData = json_encode([
							"type" => $commonData['cable_name'],
							"phone" => $commonData['smart_card'],
							"price" => $commonData['total_price'],
							"request_id" => $commonData['request_id'],
							"subscriptionType" => $commonData['cable_code'],
							"subscriptionPeriod" => $commonData['period'],
							"amount" => $commonData['total_price'],
							"serviceCode" => "SHPAY",
							"package" => "Showmax Mobile"
						]);

						$response = $this->coreModel->curlRequest('https://www.api.ringo.ng/api/agent/p2', 'POST', $postData, $ringoHeaders);
						$success = ($response['response']['status'] ?? 0) == 200;
						$gateway = 'ringo';
						break;

					default:
						return $data;
				}

				$commission = $this->coreModel->planetf_airtime_commission($network);
				$calculate_commission = $this->coreModel->calculatePercentage($total_price, $commission);
				$adminprofit = $this->coreModel->calculatePercentage($calculate_commission, 60);
				$usersprofit = $this->coreModel->calculatePercentage($calculate_commission, 40);
				$status = $success ? "successful" : "failed";
				$comment = "{$commonData['type']} {$commonData['cable_name']} Subscription to {$commonData['smart_card']} ({$commonData['customer_name']}) for validity period of {$commonData['period']} month/s";
				$this->coreModel->insertHistory($username, $total_price, "Cable Tv", $comment, $status, date("Y-m-d H:i:s"), $gateway,$this->coreModel->generateRandomString(8));

				if ($success) {
					$this->coreModel->deductWallet($total_price, $username);
					$this->coreModel->creditbonus($username, $this->coreModel->calculatePercentage($usersprofit, 60));
					$this->coreModel->creditbonus($sponsor_username, $this->coreModel->calculatePercentage($usersprofit, 40));
					$this->coreModel->insertProfit('Cable', $adminprofit, date("Y-m-d H:i:s"), $network);
					$this->coreModel->insertHistory($username, $this->coreModel->calculatePercentage($usersprofit, 60), "{$commonData['cable_name']} Cable Transaction bonus", "Bonus from {$commonData['cable_name']} cable transaction", $status, date("Y-m-d H:i:s"), $gateway, $this->coreModel->generateRandomString(8));


					if(!empty($sponsor_username)){
						$this->coreModel->creditbonus($sponsor_username, $this->coreModel->calculatePercentage($usersprofit, 40));
						$this->coreModel->insertHistory($sponsor_username, $this->coreModel->calculatePercentage($usersprofit, 40), "Recruit Transaction bonus", "You just earn 40% from $username's transaction", $status, date("Y-m-d H:i:s"), $gateway, $this->coreModel->generateRandomString(8));
					}

					$this->coreModel->sendCustomNotifications([
						[
							'username' => $username, // Upper Upline
							'title' => 'Notification Alert',
							'body' => 'Hi ' . $username  . ', ' . $commonData['type'] . ' ' . $commonData['cable_name'] . ' Subscription to ' . $commonData['smart_card'] . ' (' . $commonData['customer_name'] . ') was successful',
							'url' => MAIN_URL . "/transactionhistory"
						]
					]);
					
				}

				return [
					"status" => $success,
					"message" => $success
						? "$network Subscription to {$commonData['smart_card']} was successful"
						: "$network Subscription to {$commonData['smart_card']} failed"
				];
			}


			public function airtimePay(){
				// Check if the request is valid
				$request_id = $this->coreModel->generateRandomString(16);
				$api = $this->coreModel->sanitizeInput($_POST['api']);
				$network_code = $this->coreModel->sanitizeInput($_POST['network_code']);
				$network_name = $this->coreModel->sanitizeInput($_POST['network_name']);
				$phone = $this->coreModel->sanitizeInput($_POST['phone']);
				$amount = $this->coreModel->sanitizeInput($_POST['amount']);


				$username = $this->coreModel->decryptCookie($this->coreModel->sanitizeInput($_POST['username'])); // You need to set this appropriately
				$user = $this->coreModel->fetchuserinfo($username);

				if (!$user) {
					return ["status" => false, "message" => "Oops, Username not found"];
				}

				if (floatval($user['account']) < floatval($amount)) {
					return ["status" => false, "message" => "Insufficient Wallet balance"];
				}

				$description = "Airtime Recharge";
				$comment = "$network_name Airtime purchase to $phone";
				$date = date("Y-m-d H:i:s");

				// Prepare commission per API
				$commission = 0;
				$endpoint = '';
				$headers = [];
				$params = [];

				switch ($api) {
					case 'ringo':
						$commission = $this->coreModel->ringo_airtime_commission($network_name);
						$endpoint = "https://www.api.ringo.ng/api/agent/p2";
						$headers = [
							'Content-Type: application/json',
							'email: ' . RINGO_API_EMAIL, // Use defined constant
							'password: ' . RINGO_API_PASSWORD // Use defined constant
						];
						$params = json_encode([
							"serviceCode" => "VAR",
							"msisdn" => $phone,
							"amount" => $amount,
							"request_id" => $request_id,
							"product_id" => $network_code
						]);
						break;

					case 'planetf':
						$commission = $this->coreModel->planetf_airtime_commission($network_name);
						$network_code = $this->coreModel->network_detection($network_name);
						$endpoint = "https://softconnet.com.ng/api/reseller/pay";
						$headers = ['Authorization: ' . PLANETF_API_KEY];
						$params = [
							'service' => 'airtime',
							'coded' => $network_code,
							'phone' => $phone,
							'amount' => $amount
						];
						break;

					case 'cashless':
						$commission = $this->coreModel->cashless_airtime_commission($network_name);
						$endpoint = "https://cashless.com.ng/api/airtime";
						$params = [
							'apiToken' => CASHLESS_API_KEY,
							'network' => $network_name,
							'mobile' => $phone,
							'amount' => $amount,
							'ref' => $request_id
						];
						break;

					default:
						return ["status" => false, "message" => "Invalid API specified"];
				}

				// Determine HTTP method
				$method = ($api == 'ringo') ? 'POST' : (($api == 'cashless') ? 'GET' : 'POST');
				$airtimepay_response = $this->coreModel->curlRequest($endpoint, $method, $params, $headers);

				// Determine success flag
				$status = 'failed';
				$success = false;

				if ($api == 'ringo') {
					$success = ($airtimepay_response['response']['status'] ?? 0) == 200;
				} elseif ($api == 'planetf') {
					$success = ($airtimepay_response['response']['success'] ?? 0) == 1;
				} elseif ($api == 'cashless') {
					$success = ($airtimepay_response['response']['code'] ?? 0) == 200;
				}

				$calculated_commission = $this->coreModel->calculatePercentage($amount, $commission);
				$adminprofit = $this->coreModel->calculatePercentage($calculated_commission, 60);
				$usersprofit =$this->coreModel->calculatePercentage($calculated_commission, 40);
				//error_log($calculated_commission);

				if ($success) {
					$status = 'successful';
					$sponsor = $this->coreModel->fetchuserinfo($username);
					$sponsor_username = $sponsor['sponsor'] ?? '';
					$this->coreModel->deductWallet($amount, $username);
					$this->coreModel->insertProfit('Airtime', $adminprofit, $date, $network_name);
					$this->coreModel->creditbonus($username, $this->coreModel->calculatePercentage($usersprofit, 60));
					$this->coreModel->creditbonus($sponsor_username, $this->coreModel->calculatePercentage($usersprofit, 40));
					$this->coreModel->insertHistory($username, $this->coreModel->calculatePercentage($usersprofit, 60), "Airtime Transaction bonus", "Bonus from airtime transaction", $status, $date, $api, $this->coreModel->generateRandomString(8));

					if(!empty($sponsor_username)){
						$this->coreModel->creditbonus($sponsor_username, $this->coreModel->calculatePercentage($usersprofit, 40));
						$this->coreModel->insertHistory($sponsor_username, $this->coreModel->calculatePercentage($usersprofit, 40), "Recruit Transaction bonus", "You just earn 40% from $username's transaction", $status, $date, $api, $this->coreModel->generateRandomString(8));
					}

					$this->coreModel->sendCustomNotifications([
						[
							'username' => $username, // Upper Upline
							'title' => 'Notification Alert',
							'body' => 'Hi ' . $username  . ', ' . $network_name . ' Airtime purchase of &#8358;' . $amount . ' to ' . $phone . ' was successful',
							'url' => MAIN_URL . "/transactionhistory"
						]
					]);
				}

				// Log history
				$this->coreModel->insertHistory($username, $amount, $description, $comment, $status, $date, $api, $this->coreModel->generateRandomString(8));

				

				return [
					"status" => $success,
					"message" => $success
						? "$network_name Airtime purchase of &#8358;$amount to $phone was successful"
						: "$network_name Airtime purchase of &#8358;$amount to $phone failed, please try again later"
				];
			}

			public function dataPay(){

				$request_id = $this->coreModel->generateRandomString(16);
				$api = $this->coreModel->sanitizeInput($_POST['api']);
				$network_code = $this->coreModel->sanitizeInput($_POST['network_code']);
				$network_name = $this->coreModel->sanitizeInput($_POST['network_name']);
				$phone = $this->coreModel->sanitizeInput($_POST['phone']);
				$amount = $this->coreModel->sanitizeInput($_POST['amount']);
				$plan_name = $this->coreModel->sanitizeInput($_POST['plan_name']);
				$com = $this->coreModel->sanitizeInput($_POST['com'] ?? 0);
				$adminprofit = $this->coreModel->sanitizeInput($_POST['profit'] ?? 0);


				$username = $this->coreModel->decryptCookie($this->coreModel->sanitizeInput($_POST['username']));
				$user = $this->coreModel->fetchuserinfo($username);

				if (!$user) {
					return ["status" => false, "message" => "Oops, Username not found"];
				}

				if ($user['account'] < floatval($amount)) {
					return ["status" => false, "message" => "Insufficient Wallet balance"];
				}

				$description = "Data Recharge";
				$comment = "$network_name data plan $plan_name Data purchase to $phone ";
				$date = date("Y-m-d H:i:s");

				// Prepare commission per API
				$commission = 0;
				$endpoint = '';
				$headers = [];
				$params = [];

				switch ($api) {

					case 'ringo':
						$endpoint = "https://www.api.ringo.ng/api/agent/p2";
						$headers = [
							'Content-Type: application/json',
							'email: ' . RINGO_API_EMAIL, // Use defined constant
							'password: ' . RINGO_API_PASSWORD // Use defined constant
						];

						$params = json_encode([
							"serviceCode" => "ADA",
							"msisdn" => $phone,
							"request_id" => $request_id,
							"product_id" => $network_code
						]);
						break;

					case 'planetf':
						$network_code = $this->coreModel->network_detection($network_name);
						$endpoint = "https://softconnet.com.ng/api/reseller/pay";
						$headers = ['Authorization: ' . PLANETF_API_KEY];
						$params = [
							'service' => 'data',
							'coded' => $network_code,
							'phone' => $phone
						];
						break;

					default:
						return ["status" => false, "message" => "Invalid API specified"];
				}

				// Determine HTTP method
				$method = ($api == 'ringo') ? 'POST' : (($api == 'cashless') ? 'GET' : 'POST');
				$datapay_response = $this->coreModel->curlRequest($endpoint, $method, $params, $headers);

				// Determine success flag
				$status = 'failed';
				$success = false;

				if ($api == 'ringo') {
					$success = ($datapay_response['response']['status'] ?? 0) == 200;
				} elseif ($api == 'planetf') {
					$success = ($datapay_response['response']['success'] ?? 0) == 1;
				} elseif ($api == 'cashless') {
					$success = ($datapay_response['response']['code'] ?? 0) == 200;
				}

				if ($success) {

					$status = 'successful';
					$sponsor = $this->coreModel->fetchuserinfo($username);
					$sponsor_username = $sponsor['sponsor'] ?? '';
					$this->coreModel->deductWallet($amount, $username);
					$this->coreModel->insertProfit('Data', $adminprofit, $date, $network_name);
					$this->coreModel->creditbonus($username, $this->coreModel->calculatePercentage($com, 60));
					$this->coreModel->insertHistory($username, $this->coreModel->calculatePercentage($com, 60), "{$network_name} Data Transaction bonus", "Bonus from {$network_name} data transaction", $status, $date, $api, $this->coreModel->generateRandomString(8));

					if(!empty($sponsor_username)){
						$this->coreModel->creditbonus($sponsor_username, $this->coreModel->calculatePercentage($com, 40));
						$this->coreModel->insertHistory($sponsor_username, $this->coreModel->calculatePercentage($com, 40), "Recruit Transaction bonus", "You just earn 40% from $username's transaction", $status, $date, $api, $this->coreModel->generateRandomString(8));
					}

					$this->coreModel->sendCustomNotifications([
						[
							'username' => $username, // Upper Upline
							'title' => 'Notification Alert',
							'body' => 'Hi ' . $username  . ', ' . $network_name . '. Data purchase to ' . $phone . ' was successful',
							'url' => MAIN_URL . "/transactionhistory"
						]
					]);

				}
				// Log history
				$this->coreModel->insertHistory($username, $amount, $description, $comment, $status, $date, $api, $this->coreModel->generateRandomString(8));
				

				return [
					"status" => $success,
					"message" => $success
						? "$network_name plan $plan_name Data purchase to $phone was successful"
						: "$network_name plan $plan_name Data purchase to $phone failed, please try again later"
				];

			}

			public function electricityPay()
			{
				$inputFields = ['api', 'disco', 'meter_no', 'phone', 'amount', 'customer_name', 'username'];
				$inputs = [];

				 foreach ($inputFields as $param) {
					if (empty($_POST[$param])) {
						return ["status" => false, "message" => "Missing required parameter: $param"];
					}
				}

				// Sanitize inputs
				foreach ($inputFields as $field) {
					$inputs[$field] = $this->coreModel->sanitizeInput($_POST[$field] ?? '');
				}

				$inputs['username'] = $this->coreModel->decryptCookie($inputs['username']);
				$request_id = $this->coreModel->generateRandomString(16);
				$description = "Electricity Recharge";
				$date = date("Y-m-d H:i:s");

				$user = $this->coreModel->fetchuserinfo($inputs['username']);

				if (!$user) {
					return ["status" => false, "message" => "Oops, Username not found"];
				}

				if (floatval($user['account']) < floatval($inputs['amount'])) {
					return ["status" => false, "message" => "Insufficient Wallet balance"];
				}

				$response = null;
				$token = '';
				$success = false;

				switch ($inputs['api']) {
					case 'planetf':
						$params = [
							'service' => 'electricity',
							'coded' => $inputs['disco'],
							'phone' => $inputs['meter_no'],
							'type' => 'prepaid',
							'amount' => $inputs['amount']
						];
						$headers = ['Authorization: ' . PLANETF_API_KEY];
						$response = $this->coreModel->curlRequest("https://softconnet.com.ng/api/reseller/pay", "POST", $params, $headers);
						$success = $response['response']['httpCode'] == 200 && isset($response['response']['success']);
						$token = $response['response']['token'] ?? '';
						break;

					case 'ringo':
						$params = json_encode([
							"serviceCode" => "P-ELECT",
							"disco" => $inputs['disco'],
							"meterNo" => $inputs['meter_no'],
							"type" => "PREPAID",
							"amount" => $inputs['amount'],
							"phonenumber" => $inputs['phone'],
							"request_id" => $request_id
						]);
						$headers = [
							'Content-Type: application/json',
							'email: ' . RINGO_API_EMAIL, // Use defined constant
							'password: ' . RINGO_API_PASSWORD // Use defined constant
						];
						$response = $this->coreModel->curlRequest("https://www.api.ringo.ng/api/agent/p2", "POST", $params, $headers);
						$success = $response['response']['httpCode'] == 200 && isset($response['response']['status']);
						$token = $response['response']['token'] ?? '';
						break;

					default:
						return ["status" => false, "message" => "Invalid API provider"];
				}

				if ($success) {

					$comment = "{$inputs['disco']} electricity recharge to {$inputs['meter_no']} ({$inputs['customer_name']}) was successful, Token: $token";

					// Deduct wallet and insert history
					$this->coreModel->deductWallet($inputs['amount'], $inputs['username']);
					$this->coreModel->insertProfit('Electricity', 100, $date, $inputs['disco']);
					$this->coreModel->insertHistory(
						$inputs['username'],
						$inputs['amount'],
						$description,
						$comment,
						true,
						$date,
						$inputs['api'],
						$this->coreModel->generateRandomString(8)
					);

					// Send notification
					$this->coreModel->sendCustomNotifications([
						[
							'username' => $inputs['username'],
							'title' => 'Notification Alert',
							'body' => "Hi {$inputs['username']}, {$inputs['disco']} electricity recharge to {$inputs['meter_no']} ({$inputs['customer_name']}) was successful",
							'url' => MAIN_URL . "/transactionhistory"
						]
					]);

					return ["status" => true, "message" => "Recharge successful", "token" => $token];
				}

				return ["status" => false, "message" => "Recharge failed"];
			}


			public function fetchAllUsers()
			{
				// 1. Input validation with defaults
				$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
				$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
				$search = isset($_POST['search']) ? trim($_POST['search']) : '';

				//error_log("Fetching users: page=$page, limit=$limit, search='$search'");
				
				$offset = ($page - 1) * $limit;
				$users = [];
				$total = 0;

				try {
					// 2. Prepared statements with dynamic search
					$searchSql = '';
					$params = [];
					$paramTypes = '';

					if (!empty($search)) {
						$searchSql = "WHERE username LIKE ? OR email LIKE ?";
						$searchParam = "%" . $this->db->real_escape_string($search) . "%";
						$params = [$searchParam, $searchParam];
						$paramTypes = "ss";
					}

					// 3. Total count query (simplified)
					$stmtTotal = $this->db->prepare("SELECT COUNT(*) AS total FROM members $searchSql");
					if (!empty($params)) {
						$stmtTotal->bind_param($paramTypes, ...$params);
					}
					$stmtTotal->execute();
					$total = $stmtTotal->get_result()->fetch_column();
					$stmtTotal->close();

					// 4. Main data query with proper parameter binding
					$sql = "SELECT id, username, email, account, status, reg_date, sponsor 
							FROM members $searchSql
							ORDER BY reg_date DESC
							LIMIT ? OFFSET ?";
					
					$stmt = $this->db->prepare($sql);
					
					// Dynamic binding based on search
					if (!empty($params)) {
						$params[] = $limit;
						$params[] = $offset;
						$stmt->bind_param($paramTypes . "ii", ...$params);
					} else {
						$stmt->bind_param("ii", $limit, $offset);
					}

					$stmt->execute();
					$result = $stmt->get_result();

					// 5. Formatting results
					while ($row = $result->fetch_assoc()) {
						$users[] = [
							'id' => $row['id'],
							'username' => htmlspecialchars($row['username']),
							'email' => htmlspecialchars($row['email']),
							'balance' => (float)$row['account'],
							'status' => $row['status'],
							'regDate' => $row['reg_date'],
							'sponsor' => htmlspecialchars($row['sponsor']),
							'avatar' => 'https://bit.ly/john-doe'
						];
					}

					$stmt->close();

					return [
						'status' => true,
						'message' => 'Users fetched successfully',
						'data' => $users,
						'total' => $total,
						'page' => $page,
						'limit' => $limit
					];

				} catch (Exception $e) {
					error_log("User fetch error: " . $e->getMessage());
					return [
						'status' => false,
						'message' => 'Failed to fetch users'
					];
				}
			}

			public function fetchAllTransactions()
			{
				// 1. Input validation with defaults
				$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
				$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
				$search = isset($_POST['search']) ? trim($_POST['search']) : '';

				//error_log("Fetching users: page=$page, limit=$limit, search='$search'");
				
				$offset = ($page - 1) * $limit;
				$data = [];
				$total = 0;

				try {
					// 2. Prepared statements with dynamic search
					$searchSql = '';
					$params = [];
					$paramTypes = '';

					if (!empty($search)) {
						$searchSql = "WHERE username LIKE ? OR description LIKE ? OR amount LIKE ?";
						$searchParam = "%" . $this->db->real_escape_string($search) . "%";
						$params = [$searchParam, $searchParam, $searchParam];
						$paramTypes = "sss";
					}

					// 3. Total count query (simplified)
					$stmtTotal = $this->db->prepare("SELECT COUNT(*) AS total FROM transhistory $searchSql");
					if (!empty($params)) {
						$stmtTotal->bind_param($paramTypes, ...$params);
					}
					$stmtTotal->execute();
					$total = $stmtTotal->get_result()->fetch_column();
					$stmtTotal->close();

					// 4. Main data query with proper parameter binding
					$sql = "SELECT id, reference, username, amount, description, comment, status, api_type, date, comment
							FROM transhistory $searchSql
							ORDER BY id DESC
							LIMIT ? OFFSET ?";
					
					$stmt = $this->db->prepare($sql);
					
					// Dynamic binding based on search
					if (!empty($params)) {
						$params[] = $limit;
						$params[] = $offset;
						$stmt->bind_param($paramTypes . "ii", ...$params);
					} else {
						$stmt->bind_param("ii", $limit, $offset);
					}

					$stmt->execute();
					$result = $stmt->get_result();
					$count = 0;

					// 5. Formatting results
					while ($row = $result->fetch_assoc()) {

						$description = $row['description'];

						$count = in_array($description, ["Cable Tv", "Airtime Recharge"]) ? 2 : 1;

						$data[] = [
							'id' => $row['reference'] ?? $row['id'],
							'type' => $this->coreModel->extractFirstWords(htmlspecialchars($row['description']), 1),
							'username' => htmlspecialchars($row['username']),
							'amount' => (float)$row['amount'],
							'status' => $row['status'] ?? 'failed',
							'description' => $row['description'],
							'comment' => $row['comment'],
							'date' => $row['date'],
							'recipient' => $this->coreModel->extractFirstWords($row['comment'], $count),
							'api_type' => $row['api_type'],
						];
					}

					$stmt->close();

					return [
						'status' => true,
						'message' => 'Transaction history fetched successfully',
						'data' => $data,
						'total' => $total,
						'page' => $page,
						'limit' => $limit
					];

				} catch (Exception $e) {
					error_log("User fetch error: " . $e->getMessage());
					return [
						'status' => false,
						'message' => 'Failed to fetch transaction history'
					];
				}
			}

			public function fetchUserTransactions($username = ''){

				$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
				$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
				$search = isset($_POST['search']) ? trim($_POST['search']) : '';
				$username = isset($_POST['username']) ? $this->coreModel->decryptCookie($this->coreModel->sanitizeInput($_POST['username'])) : $this->coreModel->decryptCookie($this->coreModel->sanitizeInput($username ?? ''));

				//error_log("Fetching user transactions: username=$username");
				$offset = ($page - 1) * $limit;
				$data = [];
				$total = 0;

				try {

					$params = [$username];
					$paramTypes = "s";

					$searchSql = "WHERE username = ?";
					if (!empty($search)) {
						$searchSql .= " AND (description LIKE ? OR amount LIKE ?)";
						$searchParam = "%" . $search . "%";
						$params[] = $searchParam;
						$params[] = $searchParam;
						$paramTypes .= "ss";
					}

					// Total count
					$stmtTotal = $this->db->prepare("SELECT COUNT(*) AS total FROM transhistory $searchSql");
					$stmtTotal->bind_param($paramTypes, ...$params);
					$stmtTotal->execute();
					$total = $stmtTotal->get_result()->fetch_column();
					$stmtTotal->close();

					// Main data
					$sql = "SELECT id, reference, username, amount, description, comment, status, api_type, date
							FROM transhistory $searchSql
							ORDER BY id DESC
							LIMIT ? OFFSET ?";
					
					$stmt = $this->db->prepare($sql);
					$params[] = $limit;
					$params[] = $offset;
					$paramTypes .= "ii";

					$stmt->bind_param($paramTypes, ...$params);
					$stmt->execute();
					$result = $stmt->get_result();

					while ($row = $result->fetch_assoc()) {
						$description = $row['description'];

						$count = in_array($description, ["Cable Tv", "Airtime Recharge"]) ? 2 : 1;

						$data[] = [
							'id' => $row['id'],
							'type' => $this->coreModel->extractFirstWords(htmlspecialchars($description), 1),
							'username' => htmlspecialchars($row['username']),
							'amount' => (float)$row['amount'],
							'status' => strtolower($row['status'] ?? 'failed'),
							'description' => $description,
							'comment' => $row['comment'],
							'date' => $row['date'],
							'recipient' => $this->coreModel->extractFirstWords($row['comment'], $count),
							'avatar' => 'https://bit.ly/john-doe'
						];
					}

					$stmt->close();

					return [
						'status' => true,
						'message' => 'Transaction history fetched successfully',
						'data' => $data,
						'total' => $total,
						'page' => $page,
						'limit' => $limit
					];

				} catch (Exception $e) {
					error_log("User fetch error: " . $e->getMessage());
					return [
						'status' => false,
						'message' => 'Failed to fetch transaction history'
					];
				}
			}

			public function fetchMyReferrals(){

				$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
				$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
				$search = isset($_POST['search']) ? trim($_POST['search']) : '';
				$username = $this->coreModel->decryptCookie($this->coreModel->sanitizeInput($_POST['username']));

				$offset = ($page - 1) * $limit;
				$data = [];
				$total = 0;

				try {

					$params = [$username];
					$paramTypes = "s";

					$searchSql = "WHERE sponsor = ?";
					if (!empty($search)) {
						$searchSql .= " AND (username LIKE ?)";
						$searchParam = "%" . $search . "%";
						$params[] = $searchParam;
						$paramTypes .= "s";
					}

					// Total count
					$stmtTotal = $this->db->prepare("SELECT COUNT(*) AS total FROM members $searchSql");
					$stmtTotal->bind_param($paramTypes, ...$params);
					$stmtTotal->execute();
					$total = $stmtTotal->get_result()->fetch_column();
					$stmtTotal->close();

					// Main data
					$sql = "SELECT id, username, reg_date FROM members $searchSql ORDER BY id DESC LIMIT ? OFFSET ?";
					$stmt = $this->db->prepare($sql);
					$params[] = $limit;
					$params[] = $offset;
					$paramTypes .= "ii";

					$stmt->bind_param($paramTypes, ...$params);
					$stmt->execute();
					$result = $stmt->get_result();

					while ($row = $result->fetch_assoc()) {

						$data[] = [
							'id' => $row['id'],
							'username' => htmlspecialchars($row['username']),
							'date' => $row['reg_date'],
						];
					}

					$stmt->close();

					return [
						'status' => true,
						'message' => 'Referrals fetched successfully',
						'data' => $data,
						'total' => $total,
						'page' => $page,
						'limit' => $limit
					];

				} catch (Exception $e) {
					//error_log("User fetch error: " . $e->getMessage());
					return [
						'status' => false,
						'message' => 'Failed to fetch transaction history'
					];
				}
			}

			public function RunLogin(){
				// Check if the request is valid
				if(isset($_POST['username']) && isset($_POST['password'])){

					$username = $this->coreModel->sanitizeInput($_POST['username']);
					$password = $this->coreModel->sanitizeInput($_POST['password']);

					// Fetch user info
					$fetchuserinfo = $this->coreModel->fetchuserinfo($username);

					if ($fetchuserinfo === null) {
						return ["status" => false, "message" => "Ooops, Username not found"];
					}

					if ($fetchuserinfo['status'] < 1) {
						return ["status" => false, "message" => "Ooops, Account not yet activated, kindly log on to your mail to activate your account"];
					}

					// Verify password
					if (!password_verify($password, $fetchuserinfo['password'])) {
						return ["status" => false, "message" => "Invalid username or password"];
					}

					$value = $this->coreModel->encryptCookie($username);

					// Set session variables
					return ["status" => true, "message" => $value];
				}

				return ["status" => false, "message" => "Invalid request"];
			}

			public function fetchStat() {
				$username = $this->coreModel->decryptCookie($this->coreModel->sanitizeInput($_POST['username'] ?? ''));
				
				if (empty($username)) {
					return ["error" => "Invalid username"];
				}

				$wallet_balance = $this->coreModel->fetchuserinfo($username) ?? [];
				
				return [
					"data"         => round($this->coreModel->calculateTransaction($username, "Data") ?? 0, 2),
					"airtime"      => round($this->coreModel->calculateTransaction($username, "Airtime") ?? 0, 2),
					"cable"        => round($this->coreModel->calculateTransaction($username, "Cable") ?? 0, 2),
					"electricity"  => round($this->coreModel->calculateTransaction($username, "Electricity") ?? 0, 2),
					"education"    => round($this->coreModel->calculateTransaction($username, "Education") ?? 0, 2),
					"wallet"       => round(floatval($wallet_balance['account'] ?? 0), 2),
					"trans_history" => $this->fetchUserTransactions($username)['data'] ?? []
				];
			}

			public function fundTransfer(){
				// Check if the request is valid
				if(isset($_POST['username']) && isset($_POST['amount']) && isset($_POST['recipient'])){

					$username = $this->coreModel->decryptCookie($this->coreModel->sanitizeInput($_POST['username']));
					$amount = floatval($this->coreModel->sanitizeInput($_POST['amount']));
					$recipient = $this->coreModel->sanitizeInput($_POST['recipient']);

					if(empty($amount) || empty($recipient) || $amount <= 0) {
						return ["status" => false, "message" => "All input fields are required"];
					}

					// Fetch user info
					$userInfo = $this->coreModel->fetchuserinfo($username);
					if (!$userInfo) {
						return ["status" => false, "message" => "Ooops, Username not found"];
					}

					if (floatval($userInfo['account']) < floatval($amount)) {
						return ["status" => false, "message" => "Insufficient Wallet balance"];
					}

					// Check if recipient exists
					$recipientInfo = $this->coreModel->fetchuserinfo($recipient);
					if (!$recipientInfo) {
						return ["status" => false, "message" => "Recipient not found"];
					}

					// Deduct amount from sender's account
					$this->coreModel->deductWallet($amount, $username);

					// Credit amount to recipient's account
					$this->coreModel->creditWallet($amount, $recipient);
					$trx = $this->coreModel->generateRandomString(8);
					// Log transaction history
					$this->coreModel->insertHistory($username, $amount, "Fund Transfer", "Transfer to $recipient", "successful", date("Y-m-d H:i:s"), 'transfer', $this->coreModel->generateRandomString(8));
					$this->coreModel->insertHistory($recipient, $amount, "Fund Transfer", "Transfer from $username", "successful", date("Y-m-d H:i:s"), 'transfer', $this->coreModel->generateRandomString(8));
					
					$this->coreModel->sendCustomNotifications([
						[
							'username' => $username, // Upper Upline
							'title' => 'Notification Alert',
							'body' => 'Hi ' . $username . ', Fund transfer of &#8358;'. number_format($amount,2) .' to '. $recipient .'was successful',
							'url' => MAIN_URL . "/transactionhistory"
						],
						[
							'username' => $recipient, // Upper Upline
							'title' => 'Notification Alert',
							'body' => 'Hi ' . $recipient . ', You just received &#8358;'. number_format($amount,2) .' from '. $username,
							'url' => MAIN_URL . "/transactionhistory"
						],
					]);
					return ["status" => true, "message" => "Transfer to $recipient was successful"];
				}

				return ["status" => false, "message" => "Invalid request"];
			}

			public function fetchProfileInfo(){

				$username = $this->coreModel->decryptCookie($this->coreModel->sanitizeInput($_POST['username'] ?? ''));
				$userInfo = $this->coreModel->fetchuserinfo($username);
				$fetchvirtualaccount = $this->coreModel->fetchuservirtualwallet($userInfo['email'] ?? "");

				if (!$userInfo) {
					return ["status" => false, "message" => "Ooops, Username not found"];
				}

				return [
					"status" => true,
					"message" => "Profile information fetched successfully",
					"data" => [
						"username" => $userInfo['username'],
						"email" => $userInfo['email'],
						"account_balance" => round($userInfo['account'],2),
						"sponsor" => $userInfo['sponsor'],
						"isAdmin" => $userInfo['isAdmin'] == 0 ? false : true,
						"reg_date" => date("F d, Y h:i A", strtotime($userInfo['reg_date'])),
					],
					"virtual_account" => [
						"acct_name" => $fetchvirtualaccount['acct_name'] ?? "",
						"acct_number" => $fetchvirtualaccount['acct_number'] ?? "",
						"bank_name" => $fetchvirtualaccount['bank_name'] ?? "",
						"status" => $fetchvirtualaccount['status'] ?? 0,
						"reason" => $fetchvirtualaccount['reason'] ?? "",
					]
				];
			}

			public function changePassword(){
				// Check if the request is valid
				if(isset($_POST['username']) && isset($_POST['old_password']) && isset($_POST['new_password'])){

					$username = $this->coreModel->decryptCookie($this->coreModel->sanitizeInput($_POST['username']));
					$old_password = $this->coreModel->sanitizeInput($_POST['old_password']);
					$new_password = $this->coreModel->sanitizeInput($_POST['new_password']);
					$confirm_password = $this->coreModel->sanitizeInput($_POST['confirm_password']);

					if(empty($old_password) || empty($new_password)) {
						return ["status" => false, "message" => "All input fields are required"];
					}

					// Fetch user info
					$userInfo = $this->coreModel->fetchuserinfo($username);
					if (!$userInfo) {
						return ["status" => false, "message" => "Ooops, Username not found"];
					}

					if($confirm_password != $new_password){
						return ["status" => false, "message" => "New password and confirm password do not match"];
					}

					// Verify old password
					if (!password_verify($old_password, $userInfo['password'])) {
						return ["status" => false, "message" => "Old password is incorrect"];
					}

					// Hash new password
					$hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

					// Update password in the database
					$this->db->query("UPDATE members SET password = ? WHERE username = ?", [$hashed_new_password, $username]);

					return ["status" => true, "message" => "Password changed successfully"];

				}

				return ["status" => false, "message" => "Invalid request"];
			}

			public function registerUser(){
				try {
                    
                    // Fields to process
                    $requiredFields = ['username', 'password', 'confirm_password', 'email'];
                    $input = [];
                    $year = date('Y');
					$sponsor = $this->coreModel->sanitizeInput($_POST['sponsor'] ?? 'elite');

                    $this->db->begin_transaction();
    
                    // Sanitize required fields and check if any are empty
                    foreach ($requiredFields as $field) {
                        $input[$field] = $this->coreModel->sanitizeInput($_POST[$field] ?? '');
                        if (empty($input[$field])) {
                            throw new Exception(ucfirst($field) . " is required");
                        }
                    }

                    if(!preg_match("/^[a-zA-Z0-9_]+$/", $input['username'])){
                        throw new Exception("Username can only be alpanumeric");
                    }
            
                    // Check if sponsor exists (if provided)
                    if (isset($sponsor) && !$this->coreModel->fetchuserinfo($sponsor)){
                        throw new Exception("Sponsor username does not exist");
                    }
            
                    // Check if username already exists
                    if ($this->coreModel->fetchuserinfo($input['username'])) {
                        throw new Exception("Username already exists");
                    }

                    // Check if email already exists
                    if ($this->coreModel->fetchuserinfo($input['email'])) {
                        throw new Exception("Email already exists");
                    }

                    if(!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("Invalid email format");
                    }
    
                    // Password match check
                    if ($input['password'] !== $input['confirm_password']) {
                         throw new Exception("Passwords do not match");
                    }
            
                    // Hash password
                    $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
					

                    $activation_link =  MAIN_URL . '/confirmation?user='.$input['username'];

                    $logourl = MAIN_URL . "/elite_png.png";
					$username = $input['username'];

                    $message = <<<EMAIL
						<!DOCTYPE html>
						<html>
						<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
							<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 40px 0;">
							<tr>
								<td align="center">
								<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden;">
									<tr>
									<td align="center" style="padding: 20px; background-color: #719cdbff; color: #ffffff;">
										<h1 style="margin: 0; font-size: 24px;">Welcome to EliteGlobal</h1>
									</td>
									</tr>
									<tr>
									<td style="padding: 30px;">
										<p style="font-size: 16px; color: #333333; margin-bottom: 20px;">
										Hi <strong>$username</strong>,
										</p>
										<p style="font-size: 15px; color: #555555; line-height: 1.6;">
										Welcome to Elite Global Network
										</p>
										<p style="font-size: 15px; color: #555555; line-height: 1.6;">
										Thank you for registering with us! You're just one step away from activating your account.
										</p>
										<p style="font-size: 15px; color: #555555; line-height: 1.6;">
										Please confirm your email address by clicking the button below.
										</p>
										<div style="text-align: center; margin: 30px 0;">
										<a href="$activation_link" style="background-color: #0c4af3ff; color: #ffffff; text-decoration: none; padding: 14px 30px; font-size: 16px; border-radius: 5px; display: inline-block;">
											Confirm Email
										</a>
										</div>
										<p style="font-size: 14px; color: #999999;">
										If you did not register for this service, please ignore this email.
										</p>
									</td>
									</tr>
									<tr>
									<td align="center" style="background-color: #f1f1f1; padding: 20px; font-size: 13px; color: #999999;">
										&copy; $year EliteGlobalNetwork. All rights reserved.
									</td>
									</tr>
								</table>
								</td>
							</tr>
							</table>
						</body>
						</html>
						EMAIL;

					$date = date('Y-m-d H:i:s');
                    // Insert user
                    $stmt = $this->db->prepare("INSERT INTO members (username, email, password, sponsor, reg_date) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $input['username'], $input['email'], $hashedPassword, $sponsor, $date);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to insert user: " . $stmt->error);
                    }
					$stmt->close();

					$this->coreModel->sendmail($input['email'],$input['username'],$message,"Registration Confirmation");

					if(!empty($sponsor) || !is_null($sponsor)){

						$this->coreModel->sendCustomNotifications([
							[
								'username' => $sponsor, // Upper Upline
								'title' => 'Notification Alert',
								'body' => 'Congratulations ' . $sponsor . ', Someone just registered using your referral link',
								'url' => MAIN_URL . "/referrals"
							],
						]);

					}
				
                        $this->db->commit();

                        return ["status" => true, "message" => "Congratulations, registration was successful, Kindly check your email for verification"];

                } catch (Exception $e) {

                    $this->db->rollback();
                    return [
                        'status' => false,
                        'message' => $e->getMessage()
                    ];

                }
			}

			public function accountConfirmation(){
				try {
					$requiredFields = ['username'];
					$input = [];

					// Sanitize required fields and check if any are empty
					foreach ($requiredFields as $field) {
						$input[$field] = $this->coreModel->sanitizeInput($_POST[$field] ?? '');
						if (empty($input[$field])) {
							throw new Exception(ucfirst($field) . " is required");
						}
					}

					if(!$this->coreModel->fetchuserinfo($input['username'])){
						throw new Exception("User does not exist");
					}

					$checkusername = $this->coreModel->fetchuserinfo($input['username']);

					if($checkusername['status'] > 0){
						throw new Exception("Account already activated");
					}

					$status = true;

					$stmt = $this->db->prepare("UPDATE members SET status = ? WHERE username = ?");
                    $stmt->bind_param("ss",$status,$input['username']);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to insert user: " . $stmt->error);
                    }
					$stmt->close();

					return ['status' => true, 'message' => "Account activated successfully"];


				} catch (Exception $th) {
					return [
                        'status' => false,
                        'message' => $th->getMessage()
                    ];
				}
				 
			}

			public function createVirtualWallet(){

				try {
                    
                    // Fields to process
                    $requiredFields = ['firstname', 'middlename', 'lastname', 'phone', 'bvn', 'account_number', 'bank_code'];
                    $input = [];
                    $year = date('Y');
					$username = $this->coreModel->decryptCookie($this->coreModel->sanitizeInput($_POST['username'] ?? ''));
					$email = $this->coreModel->fetchuserinfo($username)['email'];
					$fetchwalletdetails = $this->coreModel->fetchuservirtualwallet($email);
					$date = date('Y-m-d H:i:s');

					$this->db->begin_transaction();

					// Sanitize required fields and check if any are empty
                    foreach ($requiredFields as $field) {
                        $input[$field] = $this->coreModel->sanitizeInput($_POST[$field] ?? '');
                        if (empty($input[$field])) {
                            throw new Exception(ucfirst($field) . " is required");
                        }
                    }

					if($fetchwalletdetails){
						throw new Exception($fetchwalletdetails['reason']);
					};

					$params = [
						"email" => $email,
						"first_name" => $input['firstname'],
						"middle_name" => $input['middlename'],
						"last_name" => $input['lastname'],
						"phone" => $input['phone'],
						"preferred_bank" => "Wema Bank",
						"country" => "NG",
						"bvn" => $input['bvn'],
						"account_number" => $input['account_number'],
						"bank_code" => $input['bank_code']
					];

					$headers = [
						"Authorization: Bearer ". PAYSTACK_SECRET_KEY,
						"Content-Type: application/json"
					];

					$response = $this->coreModel->curlRequest("https://api.paystack.co/dedicated_account/assign", "POST", $params, $headers);

					return ["status" => $response['response']['status'], "message" => $response['response']['message'] ?? 'Verification in progress'];


				} catch (Exception $e) {
                    $this->db->rollback();
                    return [
                        'status' => false,
                        'message' => $e->getMessage()
                    ];

                }
			}

			public function paymentWebhook(){

				if (!array_key_exists('HTTP_X_PAYSTACK_SIGNATURE', $_SERVER)) 
      			exit();
				$payload = @file_get_contents("php://input");
			 	if($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] !== hash_hmac('sha512', $payload, PAYSTACK_SECRET_KEY))
      			exit();

				
				$data = json_decode($payload, true);
				$event = $data['event'];

				if($event=='customeridentification.failed'){

					$reason = $event['data']['reason'];
					$email = $event['data']['email'];
					$username = $this->coreModel->fetchuserinfo($email)['username'];

					$stmt = $this->db->prepare("UPDATE virtual_accounts SET reason = ? WHERE email = ?");
                    $stmt->bind_param("ss", $reason,$email);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to insert user: " . $stmt->error);
                    }
					$stmt->close();

					$this->coreModel->sendCustomNotifications([
						[
							'username' => $username, // Upper Upline
							'title' => 'Notification Alert',
							'body' => 'Hi ' . $username . ', '. $reason,
							'url' => MAIN_URL . "/kyc"
						],
					]);

				}

				if($event=='dedicatedaccount.assign.failed'){

					$reason = "Virtual dedicated account creation failed, please check your details and try again";
					$email = $event['data']['customer']['email'];
					$fetchusername = $this->coreModel->fetchuserinfo($email)['username'];

					$stmt = $this->db->prepare("UPDATE virtual_accounts SET reason = ? WHERE email = ?");
                    $stmt->bind_param("ss", $reason,$email);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to insert user: " . $stmt->error);
                    }
					$stmt->close();

					$this->coreModel->sendCustomNotifications([
						[
							'username' => $fetchusername, // Upper Upline
							'title' => 'Notification Alert',
							'body' => 'Hi ' . $fetchusername . ', '. $reason,
							'url' => MAIN_URL . "/kyc"
						],
					]);

				}

				if($event=='dedicatedaccount.assign.success'){

					$reason = "Virtual dedicated account creation was successful";
					$email = $event['data']['customer']['email'];
					$customer_code = $event['data']['customer']['customer_code'];
					$bank = $event['data']['dedicated_account']['bank']['name'];
					$acct_name = $event['data']['account_name'];
					$acct_num = $event['data']['account_number'];
					$customer_code = $event['data']['customer']['email'];
					$fetchusername = $this->coreModel->fetchuserinfo($email)['username'];
					$status = true;

					$stmt = $this->db->prepare("UPDATE virtual_accounts SET reason = ?, customer_code = ?, acct_name = ?, acct_number = ?, bank_name = ?, status = ? WHERE email = ?");
                    $stmt->bind_param("sssssss", $reason, $customer_code, $acct_name, $acct_num, $bank, $status, $email);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to insert user: " . $stmt->error);
                    }
					$stmt->close();

					$this->coreModel->sendCustomNotifications([
						[
							'username' => $fetchusername, // Upper Upline
							'title' => 'Notification Alert',
							'body' => 'Hi ' . $fetchusername . ', '. $reason,
							'url' => MAIN_URL . "/fundwallet"
						],
					]);
				}

				if($event=='charge.success'){

					$email = $event['data']['customer']['email'];
					$username = $this->coreModel->fetchUserInfo($email)['username'];
					$reference = $event['data']['reference'];
					$amount = $event['data']['amount'] / 100;

					$this->coreModel->ProcessPayment($reference,$amount,$username);

				}

				if($event=='transfer.success'){

					$reference = $event['data']['reference'];
					$amount = $event['data']['amount'] / 100;
					$acct_number = $event['data']['recipient']['details']['account_number'];
					
					$email = $this->coreModel->fetchuservirtualwallet($acct_number)['email'];

					if(!$email){
						exit;
					}

					$username = $this->coreModel->fetchUserInfo($email)['username'];

					$stmt1 = $this->db->prepare("UPDATE members SET account = account + ?  where username = ?");
					$stmt1->bind_param("ds", $amount,$username);
					$stmt1->execute();
					$stmt1->close();
					
					$comment = "Your account has just been funded with the sum of $amount";
					$this->coreModel->insertHistory($username, $amount, "Fund Wallet", $comment, "successful", date("Y-m-d H:i:s"), 'Paystack', $this->coreModel->generateRandomString(8));
					
					return ["status" => true, "message" => "Transaction was successful"];

					$this->coreModel->sendCustomNotifications([
						[
							'username' => $username, // Upper Upline
							'title' => 'Notification Alert',
							'body' => 'Hi ' . $username . ', '. $comment,
							'url' => MAIN_URL . "/transactionhistory"
						],
					]);

				}

				if($event=='transfer.failed'){

					$reference = $event['data']['reference'];
					$amount = $event['data']['amount'] / 100;
					$acct_number = $event['data']['recipient']['details']['account_number'];
					
					$email = $this->coreModel->fetchuservirtualwallet($acct_number)['email'];

					if(!$email){
						exit;
					}

					$username = $this->coreModel->fetchUserInfo($email)['username'];
					
					$comment = "Your account has just been funded with the sum of $amount";
					$this->coreModel->insertHistory($username, $amount, "Fund Wallet", $comment, "failed", date("Y-m-d H:i:s"), 'Paystack', $this->coreModel->generateRandomString(8));
					
					return ["status" => true, "message" => "Transaction was successful"];

					$this->coreModel->sendCustomNotifications([
						[
							'username' => $username, // Upper Upline
							'title' => 'Notification Alert',
							'body' => 'Hi ' . $username . ', '. $comment,
							'url' => MAIN_URL . "/transactionhistory"
						],
					]);
				}

			}

			public function fetchBankLists(){
				$data = array();
				$headers = [
					"Authorization: Bearer ". PAYSTACK_SECRET_KEY,
					"Content-Type: application/json"
				];
				$response = $this->coreModel->curlRequest("https://api.paystack.co/bank", "GET", [], $headers);
				foreach($response['response']['data'] ?? [] as $d){
					$data[] = array(
						"value" => $d['id'],
						"code" => $d['code'],
						"name" => $d['name']
					);
				}
				
				if($response['response']['status']===true){

					return ["status" => true, "message" => $data];

				}
			}

			public function saveSubscription(){

				$username = $this->coreModel->decryptCookie($this->coreModel->sanitizeInput($_POST['username'] ?? ''));

				$checkusername = $this->db->prepare("select username from push_subscriptions where username = ?");
                $checkusername->bind_param("s", $username);
                $checkusername->execute();
                $result = $checkusername->get_result();
                if ($result->num_rows > 0) {
                    return;
                }

                    $subscriptionJson = $_POST['subscription'] ?? '';
    				$subscription = json_decode($subscriptionJson, true);
					$date = date('Y-m-d H:i:s');

					if (!$subscription) {
						echo json_encode(["status" => false, "message" => "Invalid subscription"]);
						return;
					}

                    // Store in database
                    $stmt = $this->db->prepare("INSERT INTO push_subscriptions  (endpoint, public_key, auth_token, username, created_at)  VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $subscription['endpoint'],
                        $subscription['keys']['p256dh'],
                        $subscription['keys']['auth'],
                        $username,
						$date
                    ]);

					return ["status" => true ];

            }

			





		}
		// End of class

?>