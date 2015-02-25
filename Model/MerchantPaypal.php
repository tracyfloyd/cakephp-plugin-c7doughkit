<?php
class MerchantPaypal extends C7DoughKitAppModel
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


		return $requestString;
	}



	/** ============================================================================================================
	 * Perfomrs a cURL call to the merchant and returns the response
	 * @param  string $requestString A url encoded string of fields to send to the merchant
	 * @return array $response
	 */
	public function sendTransactionRequest($requestString, $mode=null)
	{

		return $responseString;
	}



	/** ============================================================================================================
	 * Takes the response from Authorize.net and turns it into a more friendly format
	 * @param  string $response Response string from payment processor
	 * @return array $response
	 */
	public function normalizeResponse($responseString)
	{

		return $response;
	}



	/** ============================================================================================================
	 * Take the response string and break it up into an array for easier processing
	 * @param  string $responseString
	 * @return array $responseArray
	 */
	public function convertResponseStringToArray($responseString)
	{

		return $responseArray;
	}


} # End class