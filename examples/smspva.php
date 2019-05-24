<?php

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/../vendor/autoload.php';

/////// CONFIG ///////

// Instagram User Data
$username = 'Sharyn_Knyzewski_23C6C';
$password = 'DE396EE83A';

// Proxy Data
$proxyIPPort = '51.15.251.43:32341';
$proxyUser = 'comet2_l2n93';
$proxyPass = 'fQPN6GpP';

// Instagram User Verification Method
$verification_method = 0; // sms:0, email:1


// SMSPVA Data

$apiKey = 'EjiXp1B8OA0PT5ZPctJct3E2ZdSWt2CXbKLqB';
$balanceMethod = 'get_balance';
$countMethod = 'get_count';
$getNumberMethod = 'get_number';
$banMethod = 'ban';
$getSMSMethod = 'get_sms';
$baseUrl = 'http://smspva.com/priemnik.php';


// Temporary Used Data

$phonenumber = '';
$verificationCode = '';
$loginResponse = null;
$challengeRequiredUserId = null;
$challengeIdentifier = null;


// Instagram API Data

$debug = true;
$truncatedDebug = false;

class ExtendedInstagram extends \InstagramAPI\Instagram {
	public function changeUser($username, $password) {
		$this->_setUser($username, $password);
	}
}

function smspva_init($baseUrl, $apiKey, $balanceMethod, $countMethod) {
	// Get balance of smspva.com
	$curl = curl_init();
	$requestUrl = $baseUrl.'?method='.$balanceMethod.'&service=opt16&apikey='.$apiKey;
	curl_setopt($curl, CURLOPT_URL, $requestUrl);
	$response = curl_exec($curl);
	$currentBalance;
	$currentAvailableNumber;

	curl_close($curl);
	if ($response == 1) {
		$result = file_get_contents($requestUrl);
		$data = json_decode($result, true);
		$currentBalance = $data['balance'];
	}

	// Get free count of new phone number

	$requestUrl = $baseUrl.'?method='.$countMethod.'&service=opt16&apikey='.$apiKey.'&service_id=instagram';
	$result = file_get_contents($requestUrl);
	$start = strpos($result, '{');
	$end = strpos($result, '}');
	$str = substr($result, $start, $end+1);
	$data = json_decode($str);
	$currentAvailableNumber = $data->{"counts Instagram"};
	if ($currentAvailableNumber <= 0) {
		echo "You have no free phone number";
		exit();
	}
}

function smspva_getNumber($baseUrl, $apiKey, $getNumberMethod) {
	$gotNewNumber = 0;
	while ($gotNewNumber == 0) {
		$requestUrl = $baseUrl.'?method='.$getNumberMethod.'&service=opt16&apikey='.$apiKey;
		$result = file_get_contents($requestUrl);
		$data = json_decode($result);
		$cnt = 0;
		while ($data->response != 1) {											// Get phone number if $data->response == 1 got new one
			sleep(1);
			$cnt++;
			echo $cnt."\n";														// Count number to show it's in progress
			$result = file_get_contents($requestUrl);
			$data = json_decode($result);
		}

		$phonenumber = $data->CountryCode.$data->number;						// Got new phone number
		$id = $data->id;

		//$phonenumber = '+17788682074';

		$checkApiPath = substr( $e->getResponse()->getChallenge()->getApiPath(), 1);
		$customResponse = $ig->request($checkApiPath)							// Check if this phone number is banned or not
									->setNeedsAuth(false)
									->addPost('choice', $verification_method)
									->addPost('_uuid', $ig->uuid)
									->addPost('guid', $ig->uuid)
									->addPost('device_id', $ig->device_id)
									->addPost('_uid', $ig->account_id)
									->addPost('_csrftoken', $ig->client->getToken())
									->addPost('phone_number', $phonenumber)
									->getDecodedResponse();
		print_r($customResponse);
		$challengeRequiredUserId = $customResponse['user_id'];
		$challengeIdentifier = $customResponse['nonce_code'];
		$gotNewNumber = 1;
	}
}

function smspva_getCode($baseUrl, $apiKey, $getSMSMethod, $id) {
	$verified = 0;
	while ($verified == 0) {													// Get verification code
		$requestUrl = $baseUrl.'?method='.$getSMSMethod.'&service=opt16&id='.$id.'&apikey='.$apiKey;
		$result = file_get_contents($requestUrl);
		if ($result) {
			$data = json_decode($result);
			if ($data->response == 1) {
				$verificationCode = $data->sms;
				$verified = 1;
			}
		}
	}
}

$ig = new ExtendedInstagram($debug, $truncatedDebug); // Create NewInstagramAccount Handler

try {
	#$ig->setProxy( 'http://' . $proxyUser . ':' . $proxyPass . '@' . $proxyIPPort );
    $loginResponse = $ig->login($username, $password);	// Try Login
} catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {
	echo 'Something went wrong: '.$e->getMessage()."\n";
	exit();
} catch (\InstagramAPI\Exception\ChallengeRequiredException $e) {
	if ($e->getResponse()->getErrorType() === 'checkpoint_challenge_required') {	// It means it requires phone verification

		smspva_init($baseUrl, $apiKey, $balanceMethod, $countMethod);				// Check if the balance is more than $5 and have free phone number
		smspva_getNumber($baseUrl, $apiKey, $getNumberMethod);
		smspva_getCode($baseUrl, $apiKey, $getSMSMethod, $id);

		try {
			$customResponse = $ig->request("challenge/".$challengeRequiredUserId."/".$challengeIdentifier."/")	// Put verification code to verify phone number
				->setNeedsAuth(false)
				->addPost("security_code", $verificationCode)
				->getDecodedResponse();
			if (!is_array($customResponse)) {
				echo "customResponse is not array";
			}

			if ($customResponse['status'] == "ok") {
				echo "successfully verified";
			}
		} catch (Exception $e) {

		}
	}
} catch (Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit();
}


?>
