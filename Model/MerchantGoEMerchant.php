<?php
class MerchantGoEMerchant extends C7DoughKitAppModel
{

	public $useTable = false;


	public function startup(&$controller)
	{
	}


	/** ============================================================================================================
	 * Take the money from over there, and put it over here
	 * @param  array $transactionDetails 	An array of transaction details
	 * @return array $response 				A nice, clean array of the response!
	 */
	public function chargeIt($transactionDetails)
	{
		$requestString = $this->buildRequestString($transactionDetails);
		$responseString = $this->sendTransactionRequest($requestString, $transactionDetails['mode']);
		$response = $this->normalizeResponse($responseString);

		return $response;
	}



	/** ============================================================================================================
	 * Take the money from over there, and put it over here
	 * @param  array $transactionDetails	An array of transaction details
	 * @return array $response 				An array of the response!
	 */
	public function buildRequestString($transactionDetails)
	{
		$requestFields = array();

		/* ************************************************************************
		* MERCHANT ACCOUNT INFORMATION:
		* These fields in the API allow the system to identify the merchant submitting
		* the transaction and the state of the merchant's account on the gateway.
		*/

		##	merchant				REQUIRED
		$requestFields['merchant']			= GOEMERCHANT_API_MERCHANT_ID;

		##	password				REQUIRED
		$requestFields['password']			= GOEMERCHANT_API_MERCHANT_PASSWORD;

		##	gateway_id				OPTIONAL??
		$requestFields['gateway_id']		= GOEMERCHANT_API_MERCHANT_GATEWAY_ID;

		##	x_version			OPTIONAL (Max length 3)
			// Indicates to the system the set of fields that will be included in the response. If no value is specified, the value located in the Transaction Version settings within the Merchant Interface will be used.
		// $requestFields['x_version']			= '3.1';

		##	x_test_request		OPTIONAL (TRUE or FALSE)
			// Indicates whether the transaction should be processed as a test transaction.
		// $requestFields['x_test_request']	= 'FALSE';


		/* ************************************************************************
		* GATEWAY RESPONSE CONFIGURATION:
		* The following fields determine how a transaction response will be returned once a transaction is submitted to the system.
		* The merchant has the option of sending in the configuration of the response on a per-transaction basis or configuring
		* the response through the Merchant Interface. Submitting values in these fields on a per-transaction basis overrides the
		* configuration in the Merchant Interface for that transaction. It is recommended that the values be set in the Merchant
		* Interface for these fields and not submitted on a per-transaction basis.
		*/

		##	x_delim_data		REQUIRED (TRUE or FALSE)
			// In order to receive a delimited response from the gateway, this field has to be submitted with a value
			// of TRUE or the merchant has to configure a delimited response through the Merchant Interface.
		// $requestFields['x_delim_data']		= 'TRUE';

		##	x_delim_char		OPTIONAL - But DO NOT CHANGE
			// DO NOT CHANGE as this as it is used when parsing the response below!
			// The character that will be used to separate fields in the transaction response. The system will use the character passed in this field
			// or the value stored in the Merchant Interface if no value is passed. If this field is passed, and the value is null, it will override
			// the value stored in the Merchant Interface and there will be no delimiting character in the transaction response.
		// $requestFields['x_delim_char']		= '|';

		##	x_encap_char		OPTIONAL - But DO NOT CHANGE
			// The character that is used to encapsulate the fields in the transaction response. This is only necessary if it is possible that your
			// delimiting character could be included in any field values.
		// $requestFields['x_encap_char']		= "'";

		##	x_relay_response	REQUIRED
			// Indicates whether a relay response is desired. As all AIM transactions are direct response, a value of FALSE is required.
			// The x_relay_response field is not technically an AIM feature; however, it is recommended that you submit this field on a
			// per-transaction basis with the value of FALSE as a best practice to further define the AIM transaction format.
		// $requestFields['x_relay_response']	= 'FALSE';


		/* ************************************************************************
		* TRANSACTION DATA:
		* The following fields contain transaction-specific information such as amount, payment method, and transaction type
		*/

		##	operation_type					REQUIRED
			// Valid values: auth, sale
			// 		auth - just gets the card authorized it does not settle the transaction.
			// 		sale - authorizes the card and settles the transaction.
		$requestFields['operation_type']	= 'sale';

		##	x_method			REQUIRED (Valid values: CC, ECHECK)
			// Indicates the method of payment for the transaction being sent to the system. If left blank, this value will default to CC.
		$requestFields['x_method']			= 'CC';

		##	card_name
			// Valid values: Visa, Amex, Discover or MasterCard
		$requestFields['card_name']			= '';

		##	card_number						REQUIRED
			// Contains the numeric credit card number.
		$requestFields['card_number']		= preg_replace("/[^0-9 ]/", '', $transactionDetails['card_number']);

		##	card_exp						REQUIRED
			// Valid date formats: MMYY
			// Contains the date on which the credit card expires.
		$requestFields['card_exp']			= $transactionDetails['card_exp_month'] . '/' . $transactionDetails['card_exp_year'];

		##	cvv2			OPTIONAL
			// 3 or 4-digit number on the back of a credit card (on front for American Express).
			// Valid CVV2, CVC2 or CID value
		$requestFields['cvv2']			= $transactionDetails['card_cvv_code'];

		##	total			REQUIRED (Max length 15)
			// Total amount of the transaction. This must include both tax and shipping if applicable.
		$requestFields['total']				= $transactionDetails['amount_to_charge'];


		/* ************************************************************************
		* RECURRING TRANSACTION:
		* If is a recurring transaction, set appropriate fields
		*/

		if ( (isset($transaction_details['is_recurring'])) && ($transaction_details['is_recurring']) ) {

			##	recurring
			$requestFields['recurring']			= $transactionDetails['recurring'];

			##	recurring_type
				// Valid values: daily, weekly, biweekly, monthly, quarterly, semiannually, annually
			$requestFields['recurring_type']	= $transactionDetails['recurring_type'];

		}

		/* ************************************************************************
		* CUSTOMER NAME AND BILLING ADDRESS:
		* The customer billing address fields listed below contain information on the customer billing address associated with each transaction.
		*/

		##	owner_name
		$requestFields['owner_name']		= '';

		##	x_company
		$requestFields['x_company']			= '';

		##	owner_street
		$requestFields['owner_street']		= '';

		##	owner_city
		$requestFields['owner_city']		= '';

		##	owner_state
		$requestFields['owner_state']		= '';

		##	owner_zip
		$requestFields['owner_zip']			= '';

		##	owner_country
		$requestFields['owner_country']		= '';

		##	owner_phone
		$requestFields['owner_phone']		= '';

		##	owner_email
		$requestFields['owner_email']		= '';

		##	remote_ip_address
		$requestFields['remote_ip_address']	= '';

		// die(debug($requestFields));

		// Build an XML string for upcoming cURL call
		$requestString = '<?xml version="1.0" encoding="UTF-8"?><TRANSACTION><FIELDS>';
		foreach ( $requestFields as $key => $value ) {
			if (!empty($value)) {
				$requestString = '<FIELD KEY="' . $key . '">' . $value . '</FIELD>';
			}
		}
		$requestString .= '</FIELDS></TRANSACTION>';
		// die(debug($requestString));

		return $requestString;
	}



	/** ============================================================================================================
	 * Perfomrs a cURL call to the merchant and returns the response
	 * @param  string $requestString A url encoded string of fields to send to the merchant
	 * @return array $response
	 */
	public function sendTransactionRequest($requestString, $mode=null)
	{
		$url = "https://secure.goemerchant.com/secure/gateway/xmlgateway.aspx";
		if ($mode == 'live') {
			$url = "https://secure.goemerchant.com/secure/gateway/xmlgateway.aspx";
		}
		debug($url);

		// Post the transaction (see the code for specific information)
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
		### Go Daddy Specific CURL Options
		// curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
		//     	curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		//    		curl_setopt($ch, CURLOPT_PROXY, 'http://proxy.shr.secureserver.net:3128');
		// curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		### End Go Daddy Specific CURL Options

		$responseString = curl_exec($ch);

		curl_close ($ch);

		debug($responseString);

		return $responseString;
	}



	/** ============================================================================================================
	 * Takes the response from Authorize.net and turns it into a more friendly format
	 * @param  string $response Response string from payment processor
	 * @return array $response
	 */
	public function normalizeResponse($responseString)
	{
		$responseString='<RESPONSE>
		  <FIELDS>
		    <FIELD KEY="status">2</FIELD>
		    <FIELD KEY="auth_code">051665</FIELD>
		    <FIELD KEY="auth_response">Failed - AVS/CVV2 Rejected</FIELD>
		    <FIELD KEY="avs_code">N</FIELD>
		    <FIELD KEY="cvv2_code">P</FIELD>
		    <FIELD KEY="order_id">SHILOH-WEB-20130127042503</FIELD>
		    <FIELD KEY="reference_number">14103761</FIELD>
		    <FIELD KEY="error" />
		    <FIELD KEY="available_balance" />
		    <FIELD KEY="is_partial">0</FIELD>
		    <FIELD KEY="partial_amount">0</FIELD>
		    <FIELD KEY="partial_id" />
		    <FIELD KEY="original_full_amount" />
		    <FIELD KEY="outstanding_balance">0</FIELD>
		  </FIELDS>
		</RESPONSE>';

		$responseObject = simplexml_load_string($responseString);
		$responseObject = $responseObject->FIELDS;
		echo $responseObject->FIELD[2];
		die(debug($responseObject));

		// Response Code
		// 0-error 1-success 2-declined
		switch ($responseObject->FIELD[2]) {
			case 'APPROVED':
				$response['status'] = 'approved';
				break;
			case 2:
				$response['status'] = 'declined';
				break;
			case 3:
				$response['status'] = 'error';
				break;
			case 4:
				$response['status'] = 'held for review';
				break;
			default:
				$response['status'] = 'declined';
				break;
		}

		$response['subcode'] 								= (isset($responseArray[2])) ? $responseArray[2] : ''; // Response Subcode (Code used for Internal Transaction Details)
		$response['description_code'] 						= (isset($responseArray[3])) ? $responseArray[3] : ''; // Response Reason Code (Code detailing response code)
		$response['description_text'] 						= (isset($responseArray[4])) ? $responseArray[4] : ''; // Response Reason Text (Text detailing response code and response reason code)

		$response['transaction_id'] 						= strval($responseObject->FIELDS->FIELD[5]); // Transaction ID (Gateway assigned id number for the transaction)
		// $response['md5_hash'] 								= (isset($responseArray[38])) ? $responseArray[38] : ''; // MD5 Hash (Gateway generated MD5 has used to authenticate transaction response)

		// Address Verification System
		$response['avs_response_code'] 						= (isset($responseArray[5])) ? $responseArray[5] : '';
		$response['avs_response_description'] 				= (isset($responseArray[5])) ? $this->getAVSCodeDescription($responseArray[5]) : '';

		// CVV2
		$response['cvv_verification_response_code']			= (isset($responseArray[39])) ? $responseArray[39] : ''; // CCV Card Code Verification response code
		$response['cvv_verification_response_description'] 	= (isset($responseArray[39])) ? $this->getCVV2Description($responseArray[39]) : '';

		return $response;
	}


} # End class