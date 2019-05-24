<?php

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/../vendor/autoload.php';

/////// CONFIG ///////

// Instagram User Data

$username = 'Paris_Dilliner_EB666';
$password = 'DE9F067BA1';

// Instagram User Verification Method

$verification_method = 0; // sms:0, email:1

// Proxy Data

$proxy = '51.15.251.43:49941';
$usr = 'comet2_6rvgo';
$pass = 'Yhy25d6f';

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

// Cookie Data

$cookie_mid = 'W8Qc3QALAAFB3ndNb0FwKib84up4';
$cookie_mcd = '3';
$cookie_csrftoken = 'GGr1peEWaNciI4gfZbqQ22a0tefjztf9';
$cookie_sessionid = 'IGSCdfc8e53650ac9f8f302b1aa83a3adf75dea5febde1b7b28a23299370c65bebbf%3AmUmOu8NA8fmsRaLORdDAEp6CQDpfpFCf%3A%7B%22_auth_user_id%22%3A8712588487%2C%22_auth_user_backend%22%3A%22accounts.backends.CaseInsensitiveModelBackend%22%2C%22_auth_user_hash%22%3A%22%22%2C%22_platform%22%3A4%2C%22_token_ver%22%3A2%2C%22_token%22%3A%228712588487%3AnRnfbK4TEdJ4DHOyCfcM19VnCPqntucU%3A0dbda762b3122c103347b1d87e2a9f3a586a2426e477d88c567c82b3253a7e35%22%2C%22last_refreshed%22%3A1539579142.4151201248%7D';
$cookie_ds_user_id = '8712588487';
$cookie_rur = 'FRC';

//////////////////////

class ExtendedInstagram extends \InstagramAPI\Instagram {
	public function changeUser($username, $password) {
		$this->_setUser($username, $password);
	}
}

function readln( $prompt ) {
	if ( PHP_OS === 'WINNT' ) {
		echo "$prompt";
		return trim((string)stream_get_line(STDIN, 6, "\n"));
	}
	return trim((string)readline("$prompt "));
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

///////////////////////

// Set Cookie

// setcookie('mid', $cookie_mid, time() + (86400*30));
// setcookie('mcd', $cookie_mcd, time() + (86400*30));
// setcookie('csrftoken', $cookie_csrftoken, time() + (86400*30));
// setcookie('sessionid', $cookie_sessionid, time() + (86400*30));
// setcookie('ds_user_id', $cookie_ds_user_id, time() + (86400*30));
// setcookie('rur', $cookie_rur, time() + (86400*30));

// Set Proxy

// $ig->setProxy( 'http://' . $usr . ':' . $pass . '@' . $proxy );

$ig = new ExtendedInstagram($debug, $truncatedDebug);

try {
    
    $loginResponse = $ig->login($username, $password);

} catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {
	echo 'Something went wrong: '.$e->getMessage()."\n";
	exit();
} catch (\InstagramAPI\Exception\ChallengeRequiredException $e) {
	if ($e->getResponse()->getErrorType() === 'checkpoint_challenge_required') {

		smspva_init($baseUrl, $apiKey, $balanceMethod, $countMethod);

		// Get phone number

		$gotNewNumber = 0;
		while ($gotNewNumber == 0) {
			$requestUrl = $baseUrl.'?method='.$getNumberMethod.'&service=opt16&apikey='.$apiKey;
			$result = file_get_contents($requestUrl);
			$data = json_decode($result);
			$cnt = 0;
			while ($data->id == -1 && $data->response == 2) {
				sleep(1);
				$cnt++;
				echo $cnt."\n";
				$result = file_get_contents($requestUrl);
				$data = json_decode($result);
			}

			$phonenumber = $data->CountryCode.$data->number;
			$id = $data->id;

			// $phonenumber = '+19842010349';

			$checkApiPath = substr( $e->getResponse()->getChallenge()->getApiPath(), 1);
			$customResponse = $ig->request($checkApiPath)
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

		// Verify with new Phone number

		$verified = 0;
		while ($verified == 0) {
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
		$verificationCode = readln( 'Code that you received via ' . ( $verification_method ? 'email' : 'sms' ) . ':' );

		try {
			$customResponse = $ig->request("challenge/".$challengeRequiredUserId."/".$challengeIdentifier."/")
										->setNeedsAuth(false)
										->addPost("security_code", $verificationCode)
										->getDecodedResponse();
			if (!is_array($customResponse)) {
				# code...
			}

			if ($customResponse['status'] == "ok" && (int)$customResponse['logged_in_user']['pk'] === (int)$challengeRequiredUserId) {
				# code...
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
