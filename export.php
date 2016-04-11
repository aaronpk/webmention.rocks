<?php
include('vendor/autoload.php');

foreach(Rocks\TestData::data() as $num=>$data) {
  $responses = Rocks\Redis::getResponsesForTest($num);
  if($responses) {
    foreach($responses as $responseID) {
      $response = Rocks\Redis::getResponse($responseID);
      if($response && $response->_data) {
        print_r($response);
        Rocks\Redis::setResponseData($responseID, $response->_data);
        echo "\n";
      }
    }
  }
}
