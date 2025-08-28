<?php

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer; 
use PHPMailer\PHPMailer\Exception;

class CoreModels {

	private $db;
	private string $vapidPublicKey;
    private string $vapidPrivateKey;

	public function __construct($db){
		$this->db = $db;
		$this->vapidPublicKey = VAPID_PUBLIC_KEY;
        $this->vapidPrivateKey = VAPID_PRIVATE_KEY;
	}

	public function calculateIncreasedPrice($price){
		$increasePercentage = 0; // Initialize increase percentage

		// Determine increase percentage based on the price
		if ($price < 100) {
			$increasePercentage = 0.2; // 20%
		} elseif ($price < 150) {
			$increasePercentage = 0.15; // 15%
		} elseif ($price < 200) {
			$increasePercentage = 0.1; // 10%
		}  elseif ($price < 1000) {
			$increasePercentage = 0.05; // 5%
		} else {
			$increasePercentage = 0.035; // 3.5%
		}

		// Calculate the increased price
		$increasedPrice = $price + ($price * $increasePercentage);

		return $increasedPrice;
	}

	public function sanitizeInput($data) {
        if (is_array($data)) {
            // Loop through each element of the array and sanitize recursively
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeInput($value);
            }
        } else {
            // If it's not an array, sanitize the string
            $data = trim($data); // Remove unnecessary spaces
            $data = stripslashes($data); // Remove backslashes
            $data = htmlspecialchars($data); // Convert special characters to HTML entities
        }
        return $data;
    }

	function extractFirstWords($text, $count = 1) {
		// Trim and remove extra spaces
		$text = trim(preg_replace('/\s+/', ' ', $text));
		$words = explode(' ', $text);

		// Return first $count words
		return implode(' ', array_slice($words, 0, $count));
	}


	public function generateRandomString($length = 16) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$charactersLength = strlen($characters);
		$randomString = '';

		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[random_int(0, $charactersLength - 1)];
		}

		return $randomString;
	}

	public function insertHistory($username, $amount, $description, $comment, $status, $date, $api_type, $reference){
		$stmt = $this->db->prepare("INSERT IGNORE INTO transhistory (username, amount, description, comment, status, date, api_type, reference) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bind_param("ssssssss",$username, $amount, $description, $comment, $status, $date, $api_type,$reference);
		$stmt->execute();
		$stmt->close();
	}

	public function insertProfit($type, $amount, $date, $network){
		$stmt = $this->db->prepare("INSERT IGNORE INTO sales_profits (type, amount, date, network) VALUES (?, ?, ?, ?)");
		$stmt->bind_param("ssss",$type, $amount, $date, $network);
		$stmt->execute();
		$stmt->close();
	}

	public function filterArrayByProperty($arr, $property, $value) {
		return array_filter($arr, function($obj) use ($property, $value) {
			return $obj[$property] == $value;
		});
	}

	public function deductWallet($amount,$username){
		$stmt1 = $this->db->prepare("UPDATE members SET account = account - ?  where username = ?");
		$stmt1->bind_param("ds", $amount,$username);
		$stmt1->execute();
		$stmt1->close();
	}

	public function creditWallet($amount,$username){
		$stmt1 = $this->db->prepare("UPDATE members SET account = account + ?  where username = ?");
		$stmt1->bind_param("ds", $amount,$username);
		$stmt1->execute();
		$stmt1->close();
	}

	public function fetchuserinfo($username) {

		$sql = "SELECT * FROM members WHERE username = ? OR email = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->bind_param("ss", $username,$username);
		$stmt->execute();
		
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();

		$stmt->close();
		return $row;

	}

	public function updatepassword($email, $password) {
        $stmt = $this->db->prepare("UPDATE members SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $password, $email);
        $stmt->execute();
        return $stmt->insert_id;
    }

	public function fetchuservirtualwallet($email) {

		$sql = "SELECT * FROM virtual_accounts WHERE email = ? OR acct_number = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->bind_param("ss", $email,$email);
		$stmt->execute();
		
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();

		$stmt->close();
		return $row;

	}

	public function calculatePercentage($amount, $percentage) {
		return ($amount * $percentage) / 100;
	}

	function creditbonus($username,$amount){
		$stmt = $this->db->prepare("UPDATE members SET account = account + ? where username = ?");
		$stmt->bind_param("ds", $amount, $username);
		$stmt->execute();
		$stmt->close();
	}

	public function network_detection($network){
		$map = [
			'MTN'    => 'm',
			'AIRTEL' => 'a',
			'GLO'    => 'g',
			'9MOBILE'=> '9'
		];

		$network = strtoupper(trim($network)); // Normalize input

		return $map[$network] ?? null; // Return null if not found
	}

	public function ringo_airtime_commission($network){
		$map = [
			'MTN'    => 3.5,
			'AIRTEL' => 3.5,
			'GLO'    => 6.0,
			'9MOBILE'=> 7.0
		];

		$network = strtoupper(trim($network)); // Normalize input

		return $map[$network] ?? null; // Return null if not found
	}

	public function ringo_data_commission($network){
		$map = [
			'MTN'    => 1.5,
			'AIRTEL' => 1.5,
			'GLO'    => 2.5,
			'9MOBILE'=> 3
		];

		$network = strtoupper(trim($network)); // Normalize input

		return $map[$network] ?? null; // Return null if not found
	}

	public function planetf_airtime_commission($network){
		$map = [
			'MTN'    => 2.5,
			'AIRTEL' => 2.5,
			'GLO'    => 4.0,
			'9MOBILE'=> 3.5
		];

		$network = strtoupper(trim($network)); // Normalize input

		return $map[$network] ?? null; // Return null if not found
	}

	public function cashless_airtime_commission($network){
		$map = [
			'MTN'    => 3,
			'AIRTEL' => 3,
			'GLO'    => 6,
			'9MOBILE'=> 3
		];

		$network = strtoupper(trim($network)); // Normalize input

		return $map[$network] ?? null; // Return null if not found
	}

	public function ringo_cable_commission($network){
		$map = [
			'DSTV' => 1.6,
			'GOTV' => 1.0,
			'SHOWMAX' => 0.8,
			'STARTIMES' => 2.2
		];

		$network = strtoupper(trim($network)); // Normalize input

		return $map[$network] ?? null; // Return null if not found
	}

	public function planetf_cable_commission($network){
		$map = [
			'DSTV' => 1,
			'GOTV' => 1,
			'STARTIMES' => 0.5
		];

		$network = strtoupper(trim($network)); // Normalize input

		return $map[$network] ?? null; // Return null if not found
	}
	
	public function calculate20Percent($amount){
		// Calculate 20 percent of the given amount
		$twentyPercent = $amount * 0.20;
		return $twentyPercent;
	}
	
	public function filterArrayByPrice($arr, $property) {
	  return array_filter($arr, function($obj) use ($property) {
		return $obj[$property] > 0;
	  });
	}

	public function calculateTransaction(string $username, string $type): float 
	{
		// Validate inputs
		if (empty($username) || empty($type)) {
			return 0.00;
		}

		// Prepare SQL with LIKE for partial matching
		$sql = "
			SELECT COALESCE(SUM(amount), 0) AS total_amount
			FROM transhistory
			WHERE username = ? 
			AND description LIKE CONCAT('%', ?, '%')
			AND date >= DATE_FORMAT(NOW(), '%Y-%m-01') 
			AND date < DATE_FORMAT(NOW() + INTERVAL 1 MONTH, '%Y-%m-01')
		";

		$stmt = $this->db->prepare($sql);
		if (!$stmt) {
			error_log("Prepare failed: " . $this->db->error);
			return 0.00;
		}

		// Bind parameters (note: type is now used in LIKE)
		$stmt->bind_param('ss', $username, $type);
		
		if (!$stmt->execute()) {
			error_log("Execute failed: " . $stmt->error);
			$stmt->close();
			return 0.00;
		}

		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$stmt->close();

		return (float)($row['total_amount'] ?? 0);
	}


	public function random_string($length){
		return substr(bin2hex(random_bytes($length)), 0, $length);
	}

	public function encryptCookie($value){

		$byte = $this->random_string(20);
		$key = hex2bin($byte);

		$cipher = "AES-256-CBC";
		$ivlen = openssl_cipher_iv_length($cipher);
		$iv = openssl_random_pseudo_bytes($ivlen);

		$ciphertext = openssl_encrypt($value, $cipher, $key, 0, $iv);

		return( base64_encode($ciphertext . '::' . $iv. '::' .$key) );
	}

	// Decrypt cookie
	function decryptCookie( $ciphertext ){
		$cipher = "AES-256-CBC";
		list($encrypted_data, $iv,$key) = explode('::', base64_decode($ciphertext));
		return openssl_decrypt($encrypted_data, $cipher, $key, 0, $iv);
	}
	
	public function curlRequest($url, $method = 'GET', $data = [], $headers = []) {
        // Initialize cURL
        $ch = curl_init();
    
        // Set common options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response instead of outputting it
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout after 30 seconds
    
        // Set method-specific options
        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Encode data as query string
        } elseif (strtoupper($method) === 'GET' && !empty($data)) {
            // Append query parameters for GET requests
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    
        // Set headers if provided
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
    
        // Execute the request and fetch response
    
        $response = curl_exec($ch);               // Execute the cURL request
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP response code
        $error = curl_error($ch);                // Check for errors
        curl_close($ch);

    
        // Return the response
        return [
            'response' => json_decode($response,TRUE),                // Response body
            'httpCode' => $httpCode,                // HTTP status code
            'error' => $error,                      // Any cURL errors
        ];
    }

	public function validateWithRingo($disco, $meterNo) {
		$params = [
			'serviceCode' => 'V-ELECT',
			'disco' => strtoupper($disco),
			'meterNo' => $meterNo,
			'type' => 'PREPAID'
		];

		$headers = [
			'Content-Type: application/json',
			'email: ' . RINGO_API_EMAIL, // Use defined constant
			'password: ' . RINGO_API_PASSWORD // Use defined constant
		];

		$response = $this->curlRequest(
			"https://www.api.ringo.ng/api/agent/p2",
			'POST',
			json_encode($params),
			$headers
		);

		if ($response['httpCode'] == 200 && isset($response['response']['status'])) {
			return [
				'status' => $response['response']['status'] == 200,
				'message' => $response['response']['status'] == 200 ? $response['response']['customerName'] : 'Unable to validate number'
			];
		}

		return [
			'status' => false,
			'message' => $response['response']['message'] ?? 'Error validating meter number'
		];
	}

	public function validateWithPlanetF($disco, $meterNo) {
		$params = [
			'service' => 'electricity',
			'coded' => $disco,
			'phone' => $meterNo,
			'type' => 'PREPAID'
		];

		$headers = [
			'Authorization: ' . PLANETF_API_KEY
		];

		$response = $this->curlRequest(
			"https://softconnet.com.ng/api/reseller/validate",
			'POST',
			$params,
			$headers
		);

		if ($response['httpCode'] == 200 && isset($response['response']['success'])) {
			return [
				'status' => $response['response']['success'] > 0,
				'message' => $response['response']['success'] > 0 ? $response['response']['data'] : $response['response']['message']
			];
		}

		return [
			'status' => false,
			'message' => $response['response']['message'] ?? 'Error validating meter number'
		];
	}

	public function sendCustomNotifications(array $notifications): string
    {
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => 'mailto:admin@yourdomain.com',
                'publicKey' => $this->vapidPublicKey,
                'privateKey' => $this->vapidPrivateKey,
            ],
        ]);

        $count = 0;
        foreach ($notifications as $notification) {
            $subs = $this->getSubscriptions([$notification['username']]);
            foreach ($subs as $sub) {
                $subscription = Subscription::create([
                    'endpoint' => $sub['endpoint'],
                    'publicKey' => $sub['public_key'],
                    'authToken' => $sub['auth_token'],
                ]);

                $payload = json_encode([
                    'title' => $notification['title'],
                    'body' => $notification['body'],
                    'icon' => MAIN_URL . '/icons/android/android-lauchericon-192x192.png',
                    'url' => $notification['url'],
                ]);

                $webPush->queueNotification($subscription, $payload);
                $count++;

                if ($count % 100 === 0) {
                    $this->flushReports($webPush);
                }
            }
        }

        $this->flushReports($webPush);

        return json_encode(['sent' => $count]);
    }

    private function getSubscriptions($usernames): array
    {
        if (!is_array($usernames)) {
            $usernames = [$usernames];
        }

        if (empty($usernames)) {
            $query = "SELECT endpoint, public_key, auth_token FROM push_subscriptions";
            $stmt = $this->db->prepare($query);
        } else {
            $placeholders = implode(',', array_fill(0, count($usernames), '?'));
            $query = "SELECT endpoint, public_key, auth_token FROM push_subscriptions WHERE username IN ($placeholders)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param(str_repeat('s', count($usernames)), ...$usernames);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $subscriptions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $subscriptions;
    }

    private function flushReports(WebPush $webPush): void
    {
        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                $endpoint = $this->db->real_escape_string($report->getEndpoint());
                $this->db->query("DELETE FROM push_subscriptions WHERE endpoint = '$endpoint'");
            }
        }
    }

	public function sendmail($email,$name,$body,$subject){

        require_once 'PHPMailer/src/Exception.php';
        require_once 'PHPMailer/src/PHPMailer.php';
        require_once 'PHPMailer/src/SMTP.php';

        $mail = new PHPMailer(true);
        
        try {
            
            $mail->isSMTP();                           
            $mail->Host       = SMTP_HOST;      
            $mail->SMTPAuth   = true;
            $mail->SMTPKeepAlive = true; //SMTP connection will not close after each email sent, reduces SMTP overhead	
            $mail->Username   = SMTP_USERNAME;    
            $mail->Password   = SMTP_PASSWORD;             
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;   
            $mail->Port       = 465;               
    
            //Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, 'Elite Global Network'); // Sender's email and name
            $mail->addAddress("$email", "$name"); 
            
            $mail->isHTML(true); 
            $mail->Subject = $subject;
            $mail->Body    = $body;
    
            $mail->send();
            $mail->clearAddresses();
            return true;
            
        } catch (Exception $e){
            return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

	public function ProcessPayment($ref,$amount,$username){

		try{

			//$username = $this->decryptCookie($this->sanitizeInput($username));

			$headers= [
				"Authorization: Bearer ". PAYSTACK_SECRET_KEY,
				"Cache-Control: no-cache",
			];

			$count = 0;

			$stmt = $this->db->prepare("SELECT COUNT(*) FROM transhistory WHERE reference = ?");
			$stmt->bind_param("s", $ref);
			$stmt->execute();
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->close();

			if ($count > 0) {
				exit;
			}

			$response = $this->curlRequest("https://api.paystack.co/transaction/verify/" . rawurlencode($ref), "GET", [], $headers);

			if(!$response['response']['status']){
				exit;
			}

			if('success' == $response['response']['data']['status']){

				$stmt1 = $this->db->prepare("UPDATE members SET account = account + ?  where username = ?");
				$stmt1->bind_param("ds", $amount,$username);
				$stmt1->execute();
				$stmt1->close();
				$comment = "Your account has just been funded with the sum of " . number_format($amount, 2);
				$this->insertHistory($username, $amount, "Fund Wallet", $comment, "successful", date("Y-m-d H:i:s"), 'Paystack', $ref);
				
				return ["status" => true, "message" => "Transaction was successful"];

				$this->sendCustomNotifications([
					[
						'username' => $username, // Upper Upline
						'title' => 'Notification Alert',
						'body' => 'Hi ' . $username . ', '. $comment,
						'url' => MAIN_URL . "/transactionhistory"
					],
				]);
			}

		} catch (Exception $e) {
			$this->db->rollback();
			return [
				'status' => false,
				'message' => $e->getMessage()
			];

		}
	}

	function calculateNetAmount($amount, $percent = 0.016)
	{
		if ($amount < 2500) {
			$amount = $amount - ($amount * $percent);
		} elseif ($amount >= 2500 && $amount < 10000) {
			$amount = $amount - 70;
		} else {
			$amount = $amount - 100;
		}

		return round($amount, 2); // Round to 2 decimal places (optional)
	}


}