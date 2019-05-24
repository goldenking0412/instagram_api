<?php

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/../vendor/autoload.php';

/////// CONFIG ///////

// Instagram User Data

$username = 'Dia_Quattrini_9C382';
$password = '58ECA9E49';

// Instagram Phone Verification Method

$verification_method = 0; // sms:0, email:1

// Onlinesim Reference

$baseUrl = 'https://onlinesim.ru/api/';
$apiKey = 'e2785a70c667ce60f0d02d6bbdb4e487';
$getNumMethod = 'getNum';
$getStateMethod = 'getState';
$setOperationOkMethod = 'setOperationOk';

$service_Instagram = "Instagram";

// Temporary Used Data

$phonenumber = '';
$loginResponse = null;
$challengeRequiredUserId = null;
$challengeIdentifier = null;
$tzid = null;

// Instagram API Data

$debug = true;
$truncatedDebug = false;

//////////////////////

function base_url($apiKey, $method) {
	return 'https://onlinesim.ru/api/'.$method.'.php?apikey='.$apiKey;
}

function sendRequest($curl) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $curl);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);
	return json_decode($response);
}

function getNewNumber($apiKey, $getNumMethod, $getStateMethod, $service) {
	$data = sendRequest(base_url($apiKey, $getNumMethod).'&service='.$service);

	if ($data->response != 1) {
		echo "An error occured!\n";
		echo $data->response;
		echo "\n";
		exit();
	}
	global $tzid;
	$tzid = $data->tzid;

	$data = sendRequest(base_url($apiKey, $getStateMethod).'&tzid='.$tzid);
	return $data[0]->number;
}

function sendVerificationToNumber($ig, $e, $phonenumber, $verification_method) {
	global $challengeRequiredUserId, $challengeIdentifier;
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

	echo "sent!";
	echo "\n";
}

function getVerificationCode($tzid, $apiKey, $getStateMethod) {
	while (1) {
		$data = sendRequest(base_url($apiKey, $getStateMethod).'&tzid='.$tzid);
		if ($data[0]->response != 'TZ_NUM_WAIT') {
			return $data[0]->msg;
		}
	}
}

///////////////////////

$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug); // Create NewInstagramAccount Handler

try {
    $loginResponse = $ig->login($username, $password);	// Try Login
	echo "Succesful Login!";
} catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {
	echo 'Something went wrong: '.$e->getMessage()."\n";
	exit();
} catch (\InstagramAPI\Exception\ChallengeRequiredException $e) {

	// It means it requires phone verification
	$phonenumber = getNewNumber($apiKey, $getNumMethod, $getStateMethod, $service_Instagram);

	// Send Verification Code to new phone number
	sendVerificationToNumber($ig, $e, $phonenumber, $verification_method);

	// Get Verification Code from onlinesim.ru
	$verificationCode = getVerificationCode($tzid, $apiKey, $getStateMethod);

	try {
		// Try Verification with verification code get from onlinesim.ru
		$curl = "challenge/".$challengeRequiredUserId."/".$challengeIdentifier."/";
		$customResponse = $ig->request($curl)	// Put verification code to verify phone number
								->setNeedsAuth(false)
								->addPost("security_code", $verificationCode)
								->getDecodedResponse();

		if ($customResponse['status'] == "ok") {
			# code...
			echo "successfully verified\n";
			sendRequest(base_url($apiKey, $setOperationOkMethod).'&tzid='.$tzid);
		}
	} catch (Exception $e) {
	    echo 'Something went wrong: '.$e->getMessage()."\n";
	    exit();
	}
} catch (Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit();
}

?>
