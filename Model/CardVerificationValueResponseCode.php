<?php
class CardVerificationValueResponseCode extends C7DoughKitAppModel
{

	public $useTable = false;

	public function startup(&$controller)
	{
	}


	/** ============================================================================================================
	 * Card Verification Value System (CVV2) Response Code Details
	 * @param  [string] $code - A Valid AVS code
	 * @return [string] The code's description
	 */
	public function getCodeDescription($code)
	{
		$codes = array(
			'M' => 'CVV2 Match',
			'N' => 'CVV2 No Match',
			'P' => 'Not Processed',
			'S' => 'Issuer indicates that CVV2 data should be present on the card, but the merchant has indicated data is not present on the card',
			'U' => 'Issuer has not certified for CVV2 or Issuer has not provided Visa with the CVV2 encryption keys'
		);

		if (array_key_exists($code, $codes)) {
			return $codes[$code];
		}

		return '';
	}




} # End class CardVerificationValueResponseCode