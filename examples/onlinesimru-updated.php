<?php

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/../vendor/autoload.php';

/////// CONFIG ///////

//$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug); // Create NewInstagramAccount Handler

class ExtendedInstagram extends \InstagramAPI\Instagram {
  public function changeUser( $username, $password ) {
    $this->_setUser( $username, $password );
  }
}
$ig = new ExtendedInstagram();

// Instagram Phone Verification Method
$verification_method = 0; // sms:0, email:1

// Onlinesim Reference

$baseUrl = 'https://onlinesim.ru/api/';
$apiKey = 'e2785a70c667ce60f0d02d6bbdb4e487';
$getNumMethod = 'getNum';
$getStateMethod = 'getState';
$setOperationOkMethod = 'setOperationOk';
$setOperationReviseMethod = 'setOperationRevise';

$service_Instagram = "Instagram";

$error_num = 0; // 0: Success
                // 1: Take Too Long
                // 2: Onlinesim.ru account issue
$error_description;
$error_takelong = 1;
$error_account = 2;

// Temporary Used Data

$phonenumber = '';
$loginResponse = null;
$user_id = null;
$challenge_id = null;
$tzid = null;
$is_challenge_array = false;

// Instagram API Data

$debug = true;
$truncatedDebug = false;

//////////////////////

function csvToArray() {
  $array = $fields = array(); $i = 0;
  $handle = @fopen("accounts.csv", "r");
  if ($handle) {
    while (($row = fgetcsv($handle, 4096)) !== false) {
      if (empty($fields)) {
        $fields = $row;
        continue;
      }
      foreach ($row as $k=>$value) {
        $array[$i][$fields[$k]] = $value;
      }
      $i++;
    }
    if (!feof($handle)) {
      echo "Error: unexpected fgets() fail\n";
    }
    fclose($handle);
  }

  return $array;
}

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

  if (!isset($data->response) || $data->response != 1) {
    echo "An error occured!\n";
    if (isset($data->response)) {
      echo $data->response;
      echo "\n";
    }
    return false;
  }
  global $tzid;
  $tzid = $data->tzid;

  $data = sendRequest(base_url($apiKey, $getStateMethod).'&tzid='.$tzid);

  // it's rare, but $data might not exist
  if (isset($data) && $data[0]->response == 'TZ_INPOOL') {
    while ($data[0]->response != 'TZ_NUM_WAIT') {
      $data = sendRequest(base_url($apiKey, $getStateMethod).'&tzid='.$tzid);
    }
    return $data[0]->number;
  }
  else {
    echo "Error:".$data[0]->response."\n";
    return false;
  }
}

function sendVerificationToNumber($ig, $e, $phonenumber, $verification_method) {
  global $user_id, $challenge_id, $is_challenge_array;

  $challenge = $e->getResponse()->getChallenge();

  sleep(3);

  //echo $challenge->isApiPath();

  //$checkApiPath = substr($challenge['url'], 1);
  //$checkApiPath = substr($challenge->getApiPath(), 1);
  //$checkApiPath = substr($challenge['api_path'], 1);

  if (is_array($challenge)) {
    echo "\n\nChallenge is an array.\n\n";
    $checkApiPath = substr($challenge['api_path'], 1);
    $is_challenge_array = true;
  } else {
    echo "\n\nChallenge is NOT an array.\n\n";
    $checkApiPath = substr($challenge->getApiPath(), 1);
    $is_challenge_array = false;
  }

  sleep(3);

  echo "\n\ncheckApiPath: " . $checkApiPath . "\n\n";

  // Check if this phone number is banned or not
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

  if ( is_array( $customResponse ) && ($is_challenge_array == false) ) {
    $user_id = $customResponse['user_id'];
    $challenge_id = $customResponse['nonce_code'];
  }

  return $customResponse;
}

function getVerificationCode($tzid, $apiKey, $getStateMethod) {
  global $error_num, $error_description;
  $startTime = time();
  $timeout = 540;   //timeout in seconds

  while (1) {
    if(time() > $startTime + $timeout) { // took too long..
      $error_num = 1;
      $error_description = "It took too long to get verification code";
      return null;
    }
    $data = sendRequest(base_url($apiKey, $getStateMethod).'&tzid='.$tzid);

    // it's rare, but $data might not exist
    if (isset($data) && $data[0]->response != 'TZ_NUM_WAIT') {
      if ($data[0]->response == 1 || $data[0]->response == 'TZ_NUM_ANSWER') {
        return $data[0]->msg;
      }
      else {
        $error_num = 1;
        $error_description = "error:".$data[0]->response."\n";
        return null;
      }
    }
  }
}

function rmrf($dir) {
  foreach (glob($dir) as $file) {
    if (is_dir($file)) { 
      rmrf("$file/*");
      rmdir($file);
    } else {
      unlink($file);
    }
  }
}

///////////////////////

$igAccounts = csvToArray();

while (count($igAccounts) > 0) {

  // Remove session before starting new verification loop
  rmrf(__DIR__."/../sessions");

  foreach ($igAccounts as $igAccount) {
    // Instagram User Data

    sleep(10);

    $username = $igAccount['username'];
    $password = $igAccount['password'];

    $proxyIPPort = $igAccount['proxyIPPort'];
    $proxyUser = $igAccount['proxyUser'];
    $proxyPass = $igAccount['proxyPass'];

    echo "Account " . $username . "\n";

    try {

      // $ig->setProxy( 'http://' . $proxyUser . ':' . $proxyPass . '@' . $proxyIPPort );
      $loginResponse = $ig->login($username, $password);  // Try Login
      echo "Succesful Login!\n\n";

      // Remove verified account from array
      if (($key = array_search($igAccount, $igAccounts)) !== false) {
        unset($igAccounts[$key]);
      }
    }
    catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {
      echo 'Something went wrong: '.$e->getMessage()."\n";
      exit();
    }
    catch (\InstagramAPI\Exception\ChallengeRequiredException $e) {

      // It means it requires phone verification
      $phonenumber = getNewNumber($apiKey, $getNumMethod, $getStateMethod, $service_Instagram);
      while ($phonenumber == false) {
        $phonenumber = getNewNumber($apiKey, $getNumMethod, $getStateMethod, $service_Instagram);
      }

      // Send Verification Code to new phone number
      $response = sendVerificationToNumber($ig, $e, $phonenumber, $verification_method);

      // "Sorry, please choose a different phone number."
      while ($response['status'] == 'fail' && strpos( $response['message'], 'different phone number' ) !== false) {
        sendRequest(base_url($apiKey, $setOperationOkMethod).'&tzid='.$tzid);
        $phonenumber = getNewNumber($apiKey, $getNumMethod, $getStateMethod, $service_Instagram);echo $phonenumber."\n";
        $response = sendVerificationToNumber($ig, $e, $phonenumber, $verification_method);
      }

      // Get Verification Code from onlinesim.ru
      $verificationCode = getVerificationCode($tzid, $apiKey, $getStateMethod);
      if ($verificationCode == null) {
        if ($error_num == 1 || $error_num == 2) {
          sendRequest(base_url($apiKey, $setOperationOkMethod).'&tzid='.$tzid);
          // Do not remove unverified account in array
          continue;
        }
      }

      // from https://github.com/mgp25/Instagram-API/issues/2143
      // But this caused so many "This field is required" issues. So this code is commented.
      // $ig->changeUser( $username, $password );

      try {
        // Try Verification with verification code get from onlinesim.ru

        if ($is_challenge_array == true) {
          $curl = "challenge/";
        }
        else {
          $curl = "challenge/".$user_id."/".$challenge_id."/";
        }
        $customResponse = $ig->request($curl) // Put verification code to verify phone number
                            ->setNeedsAuth(false)
                            ->addPost("security_code", $verificationCode)
                            ->addPost('_uuid', $ig->uuid)
                            ->addPost('guid', $ig->uuid)
                            ->addPost('device_id', $ig->device_id)
                            ->addPost('_uid', $ig->account_id)
                            ->addPost('_csrftoken', $ig->client->getToken())
                            ->getDecodedResponse();

        if ($is_challenge_array == false) {
          if ($customResponse['status'] === 'ok' && (int) $customResponse['logged_in_user']['pk'] === (int) $user_id )
          {
            echo "successfully verified " . $username . "\n";

            // Remove verified account from Array
            if (($key = array_search($igAccount, $igAccounts)) !== false) {
              unset($igAccounts[$key]);
            }

            // Send Onlinesim.ru Operation Successfully completed
            sendRequest(base_url($apiKey, $setOperationOkMethod).'&tzid='.$tzid);
          }
          else {
            echo "Probably finished...\n";
            var_dump($customResponse);
            sendRequest(base_url($apiKey, $setOperationReviseMethod).'&tzid='.$tzid);
          }
        }
        else {
          if ($customResponse['status'] === 'ok')
          {
            echo "successfully verified " . $username . "\n";

            // Remove verified account from Array
            if (($key = array_search($igAccount, $igAccounts)) !== false) {
              unset($igAccounts[$key]);
            }

            // Send Onlinesim.ru Operation Successfully completed
            sendRequest(base_url($apiKey, $setOperationOkMethod).'&tzid='.$tzid);
          }
          else {
            echo "Probably finished...\n";
            var_dump($customResponse);
            sendRequest(base_url($apiKey, $setOperationReviseMethod).'&tzid='.$tzid);
          }
        }
      }
      catch (Exception $e) {
        echo 'Something went wrong: '.$e->getMessage()."\n";
        //exit();
      }

    }
    catch (Exception $e) {
      echo 'Something went wrong: '.$e->getMessage()."\n";

      // Remove verified account from Array
      if (($key = array_search($igAccount, $igAccounts)) !== false) {
        unset($igAccounts[$key]);
      }
    }
  }
}

echo "Successfully Finished!\n";

?>
