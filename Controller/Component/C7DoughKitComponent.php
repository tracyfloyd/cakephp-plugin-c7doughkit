<?php
include_once (APP . 'Plugin' . DS . 'C7DoughKit' . DS . 'Config' . DS . 'settings.php');
class C7DoughKitComponent extends Component
{

	public $transactionDetails = array(
		// Required fields
		'mode'				=> 'dev',
		'amount_to_charge' 	=> '',
		'card_number' 		=> '', //
		'card_exp_month' 	=> '', // 2 digit month
		'card_exp_year' 	=> '', // 4 digit year

		// Optional fields
		'name_on_card' 		=> '',
		'card_cvv_code'		=> '',
		'postal_code' 		=> '',
	);



	function startup(Controller $controller)
	{
	}


	/** ============================================================================================================
	 * Take the money from over there, and put it over here
	 * @param  array $merchant 				CamelCased model name One of the merchant_ models in the DoughKit plugin (without "merchant_")
	 * @param  array $transactionDetails 	An array of transaction details
	 * @return array $response 				An array of the response!
	 */
	public function chargeIt($merchant = null, $transactionDetails = array())
	{
		if (!$merchant || empty($transactionDetails)) {
			return false;
		}

		$Merchant = ClassRegistry::init('C7DoughKit.Merchant' . $merchant);

		$this->transactionDetails = array_merge($this->transactionDetails, $transactionDetails);

		// Do some cleanup/validation on the $transactionDetails fields
		$this->transactionDetails['amount_to_charge'] = (is_numeric(money_format($this->transactionDetails['amount_to_charge'], 2))) ? money_format($this->transactionDetails['amount_to_charge'], 2) : 0;
		$this->transactionDetails['card_number'] = preg_replace("/[^0-9 ]/", '', $this->transactionDetails['card_number']);
		$this->transactionDetails['card_exp_month'] = preg_replace("/[^0-9 ]/", '', $this->transactionDetails['card_exp_month']);
		$this->transactionDetails['card_exp_year'] = preg_replace("/[^0-9 ]/", '', $this->transactionDetails['card_exp_year']);

		if ( ($this->transactionDetails['amount_to_charge'] > 0)
			 && (!empty($this->transactionDetails['card_number']))
			 && (!empty($this->transactionDetails['card_exp_month']))
			 && (!empty($this->transactionDetails['card_exp_year']))
		) {

			$response = $Merchant->chargeIt($this->transactionDetails);

			// Address Verification System Response Code Description
			$AddressVerificationServiceCode = ClassRegistry::init('C7DoughKit.AddressVerificationServiceCode');
			$response['avs_response_description'] 				= (isset($response['avs_response_code'] )) ? $AddressVerificationServiceCode->getCodeDescription($response['avs_response_code'] ) : '';

			// CVV2 Response Code Description
			$CardVerificationValueResponseCode = ClassRegistry::init('C7DoughKit.CardVerificationValueResponseCode');
			$response['cvv_verification_response_description'] 	= (isset($response['cvv_verification_response_code'])) ? $CardVerificationValueResponseCode->getCodeDescription($response['cvv_verification_response_code']) : '';

			// die(debug($response));

			return $response;
		}

		return false;
	}



	/** ============================================================================================================
	 * Based on creditCardNumber, returns the type of card
	 * @param  string $creditCardNumber 	Credit card number
	 * @return string  						Credit card type (Visa, MasterCard, Diners Club, Discover, JCB)
	 */
	public function determineCreditCardType($creditCardNumber)
	{
		if ( ereg("^4[0-9]{12}([0-9]{3})?$", $creditCardNumber) ) {
			return 'Visa';
		}
		if ( ereg("^5[1-5][0-9]{14}$", $creditCardNumber) ) {
			return 'MasterCard';
		}
		if ( ereg("^3[47][0-9]{13}$", $creditCardNumber) ) {
			return 'American Express';
		}
		if (ereg("^3(0[0-5]|[68][0-9])[0-9]{11}$", $creditCardNumber)) {
			return "Diners Club";
		}
		if (ereg("^6011[0-9]{12}$", $creditCardNumber)) {
			return "Discover";
		}
		if (ereg("^(3[0-9]{4}|2131|1800)[0-9]{11}$", $creditCardNumber)) {
			return "JCB";
		}

	}

}