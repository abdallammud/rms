<?php 
$GLOBALS['userAllowance']  	= 10;
$GLOBALS['msgAllowance']  	= "on";
$GLOBALS['msgProvider']  	= "hormuud";
$GLOBALS['sms_username'] 	= "SOSTEC1";
$GLOBALS['sms_password']  	= "0iLUAuy/emJYYyHytvOF8g==";
// $GLOBALS['sms_password']  	= "0iLUAuy/emJYYyHytvOF8g";
$GLOBALS['sms_sid'] 	 	= "SOSTEC"; // no space {prefer number} https://1s2u.com/sms/API-V2.0.pdf
$GLOBALS['sms_signature']  	= "SOSTEC";
$GLOBALS['sender_name']  	= "SOSTEC TECHNOLOGIES";

$GLOBALS['sms_subject']  	= "SMS Subject";

class SMSManager {
    private $dbConn;

    public function __construct($dbConn) {
        $this->dbConn = $dbConn;
        // $this->createTableIfNotExists();
    }

    public function sendSMS($phoneNumber, $msg) {
        $balance 	= 0;
        $senderName = $GLOBALS['sender_name'];
        $senderName = substr($senderName, 0, 11);
        $errors 	= "";
        $phoneNo 	= "";

        $user = $GLOBALS['sms_username'];
        $password = $GLOBALS['sms_password'];
        $phoneNumber = str_replace([" ", "+"], "", $phoneNumber);

        if (is_numeric($phoneNumber) && $errors != 'unknown phone') {
            $phoneNo = $phoneNumber;

            // Get access token
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, 'https://smsapi.hormuud.com/token');
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(['Username' => $user, 'Password' => $password, 'grant_type' => 'password']));
            $response = curl_exec($curl);
            $character = json_decode($response);
            curl_close($curl);

            // Send SMS
            if (isset($character->access_token)) {
                $headers = ["Content-Type: application/json; charset=utf-8", "Authorization: Bearer " . $character->access_token];
                $data = [
                    "mobile" => $phoneNo,
                    "message" => $msg,
                    "senderid" => $senderName
                ];
                $postdata = json_encode($data);

                $ch = curl_init('https://smsapi.hormuud.com/api/SendSMS');
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $result = curl_exec($ch);
                curl_close($ch);
                

                $get_result = explode("\"", explode(",", $result)[0])[3];
                if ($get_result == '200') {
                    $errors = 'sent';
                } else {
                    $errors = 'message error : ' . $result;
                }

            } else {
                $errors = 'Failed to get access token';
            }
        } else {
            $errors = 'Invalid phone number';
        }

        return $errors;
    }
    
}

$SMSManager 	= new SMSManager($GLOBALS['conn']);













