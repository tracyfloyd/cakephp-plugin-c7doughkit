<?php
class AddressVerificationServiceCode extends C7DoughKitAppModel
{

	public $useTable = false;

	public function startup(&$controller)
	{
	}


	/** ============================================================================================================
	 * Address Verification System Response Code Details
	 * @param  [string] $code - A Valid AVS code
	 * @return [string] The code's description
	 */
	public function getCodeDescription($code)
	{
		// AVS Response (Address Verification Service response code - A, B, E, G, N, P, R, S, U, W, X, Y, Z)
		// Authorize.net (Use your Authorize.net Merchant Interface to change these settings)
		//    Default confirmation codes: A, P, W, X, Y, Z
		//    Default rejection codes: B, E, G, N, R, S, U


		$codes = array(
			'A' => 'Street address matches, but 5-digit and 9-digit postal code do not match.', // Standard domestic
			'B' => 'Street address matches, but postal code not verified.', // Standard international
			'C' => 'Street address and postal code do not match.', // Standard international
			'D' => 'Street address and postal code match.', // Code "M" is equivalent.	Standard international
			'E' => 'AVS data is invalid or AVS is not allowed for this card type.', // Standard domestic
			'F' => 'Card member\'s name does not match, but billing postal code matches.', // American Express only
			'G' => 'Non-U.S. issuing bank does not support AVS.', // Standard international
			'H' => 'Card member\'s name does not match. Street address and postal code match.', // American Express only
			'I' => 'Address not verified.', // Standard international
			'J' => 'Card member\'s name, billing address, and postal code match.', // American Express only
			'K' => 'Card member\'s name matches but billing address and billing postal code do not match.', // American Express only
			'L' => 'Card member\'s name and billing postal code match, but billing address does not match.', // American Express only
			'M' => 'Street address and postal code match.', // Code "D" is equivalent.	Standard international
			'N' => 'Street address and postal code do not match.', // Standard domestic
			'O' => 'Card member\'s name and billing address match, but billing postal code does not match.', // American Express only
			'P' => 'Postal code matches, but street address not verified.', // Standard international
			'Q' => 'Card member\'s name, billing address, and postal code match.', // American Express only
			'R' => 'System unavailable.', // Standard domestic
			'S' => 'Bank does not support AVS.', // Standard domestic
			'T' => 'Card member\'s name does not match, but street address matches.', // American Express only
			'U' => 'Address information unavailable.', // Returned if the U.S. bank does not support non-U.S. AVS or if the AVS in a U.S. bank is not public functioning properly.	Standard domestic
			'V' => 'Card member\'s name, billing address, and billing postal code match.', // American Express only
			'W' => 'Street address does not match, but 9-digit postal code matches.', // Standard domestic
			'X' => 'Street address and 9-digit postal code match.', // Standard domestic
			'Y' => 'Street address and 5-digit postal code match.', // Standard domestic
			'Z' => 'Street address does not match, but 5-digit postal code matches.' // Standard domestic
		);

		if (array_key_exists($code, $codes)) {
			return $codes[$code];
		}


		return '';
	}






} # End class AVSCode