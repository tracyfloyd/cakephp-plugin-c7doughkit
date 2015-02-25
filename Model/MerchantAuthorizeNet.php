<?php
class MerchantAuthorizeNet extends C7DoughKitAppModel
{

	public $useTable = false;

	### Based on code created By Graydon Stoner - www.getstonered.com ###

	// Authorize.Net Test Credit Card Numbers
	// 370000000000002 - American Express Test Card
	// 6011000000000012 - Discover Test Card
	// 5424000000000015 - MasterCard Test Card
	// 4007000000027 - Visa Test Card
	// 4012888818888 - Visa Test Card II
	// 3088000000000017 - JCB Test Card (Use expiration date 0905)
	// 38000000000006 - Diners Club/Carte Blanche Test (Use expiration date 0905)


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

		##	x_login				REQUIRED (Max length 20)
			// The API Login ID for the payment gateway account.
		$requestFields['x_login']			= AUTHORIZE_NET_API_LOGIN_ID;

		##	x_tran_key			REQUIRED (Max length 16)
			// The transaction key obtained from the merchant interface.
		$requestFields['x_tran_key']		= AUTHORIZE_NET_API_TRAN_KEY;

		##	x_version			OPTIONAL (Max length 3)
			// Indicates to the system the set of fields that will be included in the response. If no value is specified, the value located in the Transaction Version settings within the Merchant Interface will be used.
		$requestFields['x_version']			= '3.1';

		##	x_test_request		OPTIONAL (TRUE or FALSE)
			// Indicates whether the transaction should be processed as a test transaction.
		$requestFields['x_test_request']	= ($transactionDetails['mode'] != 'live') ? 'TRUE' : 'FALSE';


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
		$requestFields['x_delim_data']		= 'TRUE';

		##	x_delim_char		OPTIONAL - But DO NOT CHANGE
			// DO NOT CHANGE as this as it is used when parsing the response below!
			// The character that will be used to separate fields in the transaction response. The system will use the character passed in this field
			// or the value stored in the Merchant Interface if no value is passed. If this field is passed, and the value is null, it will override
			// the value stored in the Merchant Interface and there will be no delimiting character in the transaction response.
		$requestFields['x_delim_char']		= '|';

		##	x_encap_char		OPTIONAL - But DO NOT CHANGE
			// The character that is used to encapsulate the fields in the transaction response. This is only necessary if it is possible that your
			// delimiting character could be included in any field values.
		$requestFields['x_encap_char']		= '';

		##	x_relay_response	REQUIRED
			// Indicates whether a relay response is desired. As all AIM transactions are direct response, a value of FALSE is required.
			// The x_relay_response field is not technically an AIM feature; however, it is recommended that you submit this field on a
			// per-transaction basis with the value of FALSE as a best practice to further define the AIM transaction format.
		$requestFields['x_relay_response']	= 'FALSE';


		/* ************************************************************************
		* TRANSACTION DATA:
		* The following fields contain transaction-specific information such as amount, payment method, and transaction type
		*/

		##	x_type				REQUIRED (Valid values: AUTH_CAPTURE, AUTH_ONLY, CAPTURE_ONLY, CREDIT, VOID, PRIOR_AUTH_CAPTURE)
			// Indicates the type of transaction. If the value in the field does not match any of the values stated, the transaction will be rejected.
			// If no value is submitted in this field, the gateway will process the transaction as an AUTH_CAPTURE
		$requestFields['x_type']				= 'AUTH_CAPTURE';

		##	x_method			REQUIRED (Valid values: CC, ECHECK)
			// Indicates the method of payment for the transaction being sent to the system. If left blank, this value will default to CC.
		$requestFields['x_method']				= 'CC';

		##	x_card_num			CONDITIONAL - Only REQUIRED if x_method = CC (Max length 22)
			// Contains the numeric credit card number.
		$requestFields['x_card_num']			= preg_replace("/[^0-9 ]/", '', $transactionDetails['card_number']);

		##	x_exp_date			CONDITIONAL - REQUIRED if x_method = CC
			// Contains the date on which the credit card expires.
			// Valid date formats: MMYY , MM/YY , MM-YY , MMYYYY , MM/YYYY , MM-YYYY , YYYY-MM-DD, YYYY/MM/DD
		$requestFields['x_exp_date']			= $transactionDetails['card_exp_month'] . '/' . $transactionDetails['card_exp_year'];

		##	x_card_code			OPTIONAL
			// 3 or 4-digit number on the back of a credit card (on front for American Express).
			// Valid CVV2, CVC2 or CID value
		$requestFields['x_card_code']			= $transactionDetails['card_cvv_code'];

		##	x_tax				OPTIONAL
			// Contains the sales tax amount
			// OR delimited tax information including the sales tax name, description, and amount.
			// Any valid tax amount OR the following delimited values:
			// tax item name <|> tax description <|> tax amount
		$requestFields['x_tax']					= '';

		##	x_freight			OPTIONAL
			// Contains the freight (shipping) amount charged
			// OR delimited freight information including the freight name, description, and amount.
			// Any valid freight amount OR the following delimited values:
			// freight item name <|> freight description <|> freight amount
		$requestFields['x_freight']				= '';

		##	x_amount			REQUIRED (Max length 15)
			// Total amount of the transaction. This must include both tax and shipping if applicable.
		$requestFields['x_amount']				= $transactionDetails['amount_to_charge'];

		##	x_recurring_billing	OPTIONAL
			// NOTE: This DOES NOT schedule a recurring transaction
			// This field is used by merchants who host their own recurring billing system and wish to convey to their merchant account
			// provider that the transaction originated from their recurring billing system. It does not denote anything in relation to
			// recurring billing in the Authorize.Net system. If you are submitting a transaction through your Authorize.Net account which
			// is not originating from your own recurring billing system you should not be setting the recurring billing field to true.
		$requestFields['x_recurring_billing']	= 'FALSE';


		/* ************************************************************************
		* CUSTOMER NAME AND BILLING ADDRESS:
		* The customer billing address fields listed below contain information on the customer billing address associated with each transaction.
		*/

		##	x_first_name		OPTIONAL (Max length 50)
			// Contains the first name of the customer associated with the billing address for the transaction.
		$requestFields['x_first_name']		= '';

		##	x_last_name			OPTIONAL (Max length 50)
			// Contains the last name of the customer associated with the billing address for the transaction.
		$requestFields['x_last_name']		= '';

		##	x_company			OPTIONAL (Max length 50)
			// Contains the company name associated with the billing address for the transaction.
		$requestFields['x_company']			= '';

		##	x_address			OPTIONAL (Max length 60)
			// Contains the address of the customer associated with the billing address for the transaction.
		$requestFields['x_address']			= '';

		##	x_city				OPTIONAL (Max length 40)
			// Contains the city of the customer associated with the billing address for the transaction.
		$requestFields['x_city']			= '';

		##	x_state				OPTIONAL (Max length 40)
			// Contains the state of the customer associated with the billing address for the transaction.
		$requestFields['x_state']			= '';

		##	x_zip				OPTIONAL (Max length 20)
			// Contains the zip code of the customer associated with the billing address for the transaction.
		$requestFields['x_zip']				= '';

		##	x_country			OPTIONAL - But if passed, the value will be verified. (Max length 60)
			// Contains the country of the customer associated with the billing address for the transaction.
			// Valid values: Any valid two-digit country code or full country name (in English)
		$requestFields['x_country']			= '';

		##	x_phone				OPTIONAL (Max length 25)
			// Contains the phone number of the customer associated with the billing address for the transaction.
		$requestFields['x_phone']			= '';

		##	x_fax				OPTIONAL (Max length 25)
			// Contains the fax number of the customer associated with the billing address for the transaction.
		$requestFields['x_fax']				= '';

		##	x_email				OPTIONAL (Max length 255)
			// Email address to which the customer's copy of the Authorize.net confirmation email is sent.
			// No email will be sent to the customer if the email address does not meet standard email format checks.
		$requestFields['x_email']			= '';

		##	x_customer_ip		CONDITIONAL - Only REQUIRED when using the Fraud DetectionSuiteIP Address Blocking tool. (Max length 15)
			// IP address of the customer initiating the transaction.
			// Required format is 255.255.255.255. If this value is not passed, it will default to 255.255.255.255
		$requestFields['x_customer_ip']		= '';

		##	x_customer_tax_id	OPTIONAL (Max length 9 - numbers only)
			// Tax ID or SSN of the customer initiating the transaction.
		$requestFields['x_customer_tax_id']	= '';


		/* ************************************************************************
		* INVOICE INFORMATION
		* Based on their respective requirements, merchants may submit invoice information with a transaction.
		* Two invoice fields are provided in the gateway API.
		*/

		##	x_invoice_num		OPTIONAL (Max length 20)
			// Merchant-assigned invoice number. Here we default to a time-based string if one is not provided
		$requestFields['x_invoice_num']		= (isset($requestFields['order_id']) && !empty($requestFields['order_id'])) ? $requestFields['order_id'] : date('YmdHis');

		##	x_description		OPTIONAL (Max length 255)
			// Description of the transaction. Here we default to "Website Order" if one is not provided
		$requestFields['x_description']		= (isset($requestFields['order_description']) && !empty($requestFields['order_description'])) ? $requestFields['order_description'] : 'Website Order';

		/* ************************************************************************
		* ITEMIZED ORDER INFORMATION:
		* Based on their respective requirements, merchants may submit itemized order information with a transaction. Itemized
		* order information is not submitted to the processor and is not returned with the transaction response. This information
		* is displayed on the Transaction Detail page in the Merchant Interface.
		*/

		// TODO...


		/* ************************************************************************
		* CUSTOMER SHIPPING ADDRESS:
		* The following fields describe the customer shipping information that may be submitted with each transaction.
		*/

		##	x_ship_to_first_name	OPTIONAL (Max length 50)
			// Contains the customer shipping first name.
		$requestFields['x_ship_to_first_name']	= '';

		##	x_ship_to_last_name		OPTIONAL (Max length 50)
			// Contains the customer shipping last name.
		$requestFields['x_ship_to_last_name']	= '';

		##	x_ship_to_company		OPTIONAL (Max length 50)
			// Contains the customer shipping company name.
		$requestFields['x_ship_to_company']		= '';

		##	x_ship_to_address		OPTIONAL (Max length 60)
			// Contains the customer shipping street address.
		$requestFields['x_ship_to_address']		= '';

		##	x_ship_to_city			OPTIONAL (Max length 40)
			// Contains the customer shipping city.
		$requestFields['x_ship_to_city']		= '';

		##	x_ship_to_state			OPTIONAL - If passed, the value will be verified. (Max length 40)
			// Contains the customer shipping state.
		$requestFields['x_ship_to_state']		= '';

		##	x_ship_to_zip			OPTIONAL (Max length 20)
			// Contains the customer shipping zip code.
		$requestFields['x_ship_to_zip']			= '';

		##	x_ship_to_country		OPTIONAL - But if passed, the value will be verified. (Max length 60)
			// Contains the customer shipping country.
			// Valid values: Any valid two-digit country code or full country name (in English)
		$requestFields['x_ship_to_country']		= '';

		// debug($requestFields);

		// Remove empty fields and build a urlencoded string for upcoming cURL call
		foreach ( $requestFields as $key => $value ) {
			if (empty($value)) {
				unset($requestFields[$key]);
			}
		}
		$requestString = http_build_query($requestFields);
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
		$url = "https://test.authorize.net/gateway/transact.dll";
		if ($mode == 'live') {
			$url = "https://secure.authorize.net/gateway/transact.dll";
		}
		// debug($url);

		// Post the transaction (see the code for specific information)
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim($requestString, '& '));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
		### Go Daddy Specific CURL Options
		// curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
		//     	curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		//    		curl_setopt($ch, CURLOPT_PROXY, 'http://proxy.shr.secureserver.net:3128');
		// curl_setopt($ch, CURLOPT_TIMEOUT, 120);
			### End Go Daddy Specific CURL Options
		$responseString = curl_exec($ch);
		curl_close ($ch);
		// debug($responseString);

		return $responseString;
	}



	/** ============================================================================================================
	 * Takes the response from Authorize.net and turns it into a more friendly format
	 * @param  string $response Response string from payment processor
	 * @return array $response
	 */
	public function normalizeResponse($responseString)
	{
		/*
		Important Response Values
		$response[1] = Response Code (1 = Approved, 2 = Declined, 3 = Error, 4 = Held for Review)
		$response[2] = Response Subcode (Code used for Internal Transaction Details)
		$response[3] = Response Reason Code (Code detailing response code)
		$response[4] = Response Reason Text (Text detailing response code and response reason code)
		$response[5] = Authorization Code (Authorization or approval code - 6 characters)
		$response[6] = AVS Response (Address Verification Service response code - A, B, E, G, N, P, R, S, U, W, X, Y, Z)
						(A, P, W, X, Y, Z are default AVS confirmation settings - Use your Authorize.net Merchant Interface to change these settings)
						(B, E, G, N, R, S, U are default AVS rejection settings - Use your Authorize.net Merchant Interface to change these settings)
		$response[7] = Transaction ID (Gateway assigned id number for the transaction)
		$response[38] = MD5 Hash (Gateway generated MD5 has used to authenticate transaction response)
		$response[39] = Card Code Response (CCV Card Code Verification response code - M = Match, N = No Match, P = No Processed, S = Should have been present, U = Issuer unable to process request)

		For more information about the Authorize.net AIM response consult their AIM Implementation Guide at
		http://developer.authorize.net/guides/AIM/ and go to Section Four : Fields in the Payment Gateway Response for more details.
		*/


		$responseArray = $this->convertResponseStringToArray($responseString);
		// die(debug($responseArray));

		$response['charge_approved'] = false;

		// Response Code
		switch ($responseArray[1]) {
			case 1:
				$response['status'] = 'approved';
				$response['charge_approved'] = true;
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

		$response['transaction_id'] 						= (isset($responseArray[7])) ? $responseArray[7] : ''; // Transaction ID (Gateway assigned id number for the transaction)
		$response['md5_hash'] 								= (isset($responseArray[38])) ? $responseArray[38] : ''; // MD5 Hash (Gateway generated MD5 has used to authenticate transaction response)

		// Address Verification System
		$response['avs_response_code'] 						= (isset($responseArray[5])) ? $responseArray[5] : '';
		$response['avs_response_description'] 				= '';

		// CVV2
		$response['cvv_verification_response_code']			= (isset($responseArray[39])) ? $responseArray[39] : ''; // CCV Card Code Verification response code
		$response['cvv_verification_response_description'] 	= '';

		return $response;
	}



	/** ============================================================================================================
	 * Take the response string and break it up into an array for easier processing
	 * @param  string $responseString
	 * @return array $responseArray
	 */
	public function convertResponseStringToArray($responseString)
	{
		// Rekey array starting with 1 so we can match up to Authorize.net's response field numbers
		foreach (explode('|', $responseString) as $k=>$v) {
			$responseArray[$k+1] = $v;
		}
		// debug($responseArray);

		return $responseArray;
	}


} # End class