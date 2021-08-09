<?php

ini_set('precision', 10);
ini_set('serialize_precision', 10);

class Payout {
    
    /*  Script for DataAutomation developer test that calculates the total number of payouts for employees.
     *  Implements 3 method. 
     *  get_payouts_data makes a GET request to Airtable database to retrieve employee records.
     *  post_payouts_data POST a request to dataAutomation database.
     *  get_total_pay calculates the total payout recieved by an employee.
     */
 
	private $api_key;
    private $base;
    private $table;
    private $username;
    private $password;
 
    /*
     *  Constructor method
     */
	function __construct( $api_key, $base, $table, $client_server_username, $client_server_password ) {
		$this->api_key = $api_key;
		$this->base = $base;
		$this->table = $table;
		$this->username = $client_server_username;
		$this->password = $client_server_password;
	}
 
    /*
     *  get_payouts_data method
     */
	function get_payouts_data() {
        // API URL
        $url = 'https://api.airtable.com/v0/' . $this->base . '/' . $this->table;
        
        // header for API authentication
        $headers = array(
            'Authorization: Bearer ' . $this->api_key
        );
        
        // Create a new cURL resource
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        $entries = curl_exec($ch);
        
        //handle errors from APIcall
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }
        curl_close($ch);
        if (isset($error_msg)) {
            $airtable_response = $error_msg;
        } else {
            $airtable_response = json_decode($entries, TRUE);
        }
        return $airtable_response;
	}
    
    public function post_payouts_data($data) {
        // API URL
        $url = 'https://auth.da-dev.us/devtest1';

        // Create a new cURL resource
        $ch = curl_init($url);

        $payload = json_encode($data, true);
        
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic '. base64_encode("$this->username:$this->password")
        );

        // Attach encoded JSON string to the POST fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        // Set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Return response instead of outputting
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the POST request
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // Close cURL resource
        curl_close($ch);
        
        return $result;
    }
    
    function get_total_pay() {
        $airtable_data = $this->get_payouts_data();
        $results = [];
        if(!empty($airtable_data && is_array($airtable_data))){
            foreach($airtable_data['records'] as $key=>$record) {
              $name = $record['fields']['Name'];
              $amount = $record['fields']['Amount'];
              if (!isset($results[$name])){
                $results[$name] = $amount;
              } else {
                $results[$name] += $amount;
              }
          }
        }
        
        //post payload of total payout of all employees
        $res = $this->post_payouts_data($results);
        return $res;
    }
}

$payoutClass = new Payout('my_airtable_api_key', 'my_airtable_base_id', 'my_airtable_table', 'devtest_username','devtest_password');
$data = $payoutClass->get_total_pay();
print_r($data);

?>