<?php
$postfields = array('apikey'=>'e2785a70c667ce60f0d02d6bbdb4e487', 'service'=>'Instagram');
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://onlinesim.ru/api/getNum.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
$response = curl_exec($ch);
print_r($response); exit();
?>