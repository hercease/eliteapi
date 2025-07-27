<?php
// Allow from any origin (update for production)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once('config/config.php');
require_once('controllers/controllers.php');
require_once('controllers/database_controller.php');
require_once('models/allmodels.php');
$db = (new Database())->connect();
$controller = new Controllers($db);
$baseDir = '/eliteapi';  // Base directory where your app is located
$url = str_replace($baseDir, '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

date_default_timezone_set("Africa/lagos");

// Validate request method and content type
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse(405, 'Method Not Allowed');
}

switch ($url) {
	
	case '/fetchdataplanlist':
        $datalist = $controller->fetchDataPlans();
        sendSuccessResponse($datalist); // sends as JSON
		break;
	case '/fetchcableplanlist':
        $cablelist = $controller->fetchCablePlanList();
        sendSuccessResponse($cablelist); // sends as JSON
		break;
	case '/fetchelectricityplans':
        $fetchelectricity = $controller->fetchElectricity();
        sendSuccessResponse($fetchelectricity); // sends as JSON
		break;
	case '/datapay':
        $datapay = $controller->dataPay();
        sendSuccessResponse($datapay); // sends as JSON
		break;
    case '/cablepay':
        $cablepay = $controller->cablePay();
        sendSuccessResponse($cablepay); // sends as JSON
        break;
    case '/airtimepay':
        $airtimepay = $controller->airtimePay();
        sendSuccessResponse($airtimepay); // sends as JSON
		break;
	case '/fetchallusers':
        $fetchallusers = $controller->fetchAllUsers();
        sendSuccessResponse($fetchallusers); // sends as JSON
		break;
	case '/fetchalltransactions':
        $fetchalltransaction = $controller->fetchAllTransactions();
        sendSuccessResponse($fetchalltransaction); // sends as JSON
		break;
	case '/fetchusertransactions':
        $fetchalltransaction = $controller->fetchUserTransactions($_POST['username'] ?? '');
        sendSuccessResponse($fetchalltransaction); // sends as JSON
		break;
	case '/runlogin':
        $runlogin = $controller->RunLogin();
        sendSuccessResponse($runlogin); // sends as JSON
		break;
	case '/runregistration':
        $runregistration = $controller->registerUser();
        sendSuccessResponse($runregistration); // sends as JSON
		break;
	case '/fetchdashboardstats':
        $stats = $controller->fetchStat();
        sendSuccessResponse($stats); // sends as JSON
		break;
	case '/fundtransfer':
        $fundtransfer = $controller->fundTransfer();
        sendSuccessResponse($fundtransfer); // sends as JSON
		break;
	case '/fetchprofileinfo':
        $profileinfo = $controller->fetchProfileInfo();
        sendSuccessResponse($profileinfo); // sends as JSON
		break;
	case '/changepassword':
        $changepassword = $controller->changePassword();
        sendSuccessResponse($changepassword); // sends as JSON
		break;
	case '/createvirtualwallet':
        $createvirtualwallet = $controller->createVirtualWallet();
        sendSuccessResponse($createvirtualwallet); // sends as JSON
		break;
	case '/fetchbanklist':
        $fetchbanklist = $controller->fetchBankLists();
        sendSuccessResponse($fetchbanklist); // sends as JSON
		break;
	case '/paymentwebhook':
        $paymentwebhook = $controller->paymentWebhook();
		break;
	case '/validatemeterno':
        $validatemeterno = $controller->ValidateElectricityCard();
        sendSuccessResponse($validatemeterno); // sends as JSON
		break;
	case '/electricitypay':
        $electricitypay = $controller->electricityPay();
        sendSuccessResponse($electricitypay); // sends as JSON
		break;
	case '/fetchmyreferrals':
        $fetchmyreferrals = $controller->fetchMyReferrals();
        sendSuccessResponse($fetchmyreferrals); // sends as JSON
		break;
	case '/registersubscription':
        $registersubscription = $controller->saveSubscription();
        sendSuccessResponse($registersubscription); // sends as JSON
		break;
	case '/confirmaccount':
        $confirmaccount = $controller->accountConfirmation();
        sendSuccessResponse($confirmaccount); // sends as JSON
		break;
       
	default:
        sendErrorResponse(400, 'Invalid request type');
}

/**
 * Send a JSON success response.
 *
 * @param array $data Response data
 * @return void
 */
function sendSuccessResponse(array $data): void
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Send a JSON error response.
 *
 * @param int $statusCode HTTP status code
 * @param string $message Error message
 * @return void
 */
function sendErrorResponse(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($message);
    exit;
}


?>