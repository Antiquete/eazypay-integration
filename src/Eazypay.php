<?php
// Copyright (C) 2020 Hari Saksena <hari.mail@protonmail.ch>
// 
// eazypay_integration is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// eazypay_integration is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
// 
// You should have received a copy of the GNU Lesser General Public License
// along with eazypay_integration. If not, see <http://www.gnu.org/licenses/>.

namespace Antiquete\Eazypay;

use Antiquete\Eazypay\Transaction;
use Exception;

define("EAZYPAY_URL", "https://eazypay.icicibank.com/EazyPG?");

class Eazypay{  
  private $MID;       // Merchant ID
  private $MRef;      // Merchant Reference No.
  private $SubMID;    // Sub Merchant ID
  private $Paymode;   // Paymode
  private $ReturnURL; // Return URL
    
  private $Key;       //Eazypay Encryption Key
  
  /**
   * Eazypay()
   *
   * @param String $mid
   * @param String $mref
   * @param String $submid
   * @param String $rurl
   * @param String $ekey
   */
  public function __construct($mid, $mref, $submid, $rurl, $ekey){
    $this->MID = $mid;
    $this->MRef = $mref;
    $this->SubMID = $submid;
    $this->Paymode = '9';             // TODO: Make paymode dynamic.
    $this->ReturnURL = $rurl;
    $this->Key = $ekey;
  }
  
  /**
   * Returns link for a transaction.
   *
   * @param Transaction $transaction
   * @return string $link
   */
  public function getLink(Transaction $transaction)
  {
    $enc_ref      = $this->encrypt($transaction->id());
    $enc_submid   = $this->encrypt($this->SubMID);
    $enc_amount   = $this->encrypt($transaction->amount());
    $enc_rurl     = $this->encrypt($this->ReturnURL);
    $enc_paymode  = $this->encrypt($this->Paymode);
    $enc_mfields  = $this->encrypt($transaction->id()."|".$this->SubMID."|".$transaction->amount());

    $encUrl = EAZYPAY_URL."merchantid=".$this->MID."&mandatory fields=$enc_mfields&optional fields=&returnurl=$enc_rurl&Reference No=$enc_ref&submerchantid=$enc_submid&transaction amount=$enc_amount&paymode=$enc_paymode";

    return $encUrl;
  }
  
  
  /**
   * Generates aes128 encrypted message using merchant key for the string provided.
   *
   * Uses mcrypt_compat for encrypting.
   *
   * @param string $plainText
   * @return string $cipherText
   */
  private function encrypt($plainText)
  {
    $block = mcrypt_get_block_size('rijndael-128', 'ecb');
    $pad = $block - (strlen($plainText) % $block);
    $plainText .= str_repeat(chr($pad), $pad);
    $cipherText = base64_encode(@mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->Key, $plainText, MCRYPT_MODE_ECB));
    return $cipherText;
  }
  
  // -- Payment Response Handling Functions 
  
  /**
   * Handle payment response.
   *
   * Pass callables to functions implemented on site.
   *
   * @param callable $matchTransactionId - function matchTransactionId($refTransactionId) { return exists($refTransactionId); }
   * @param callable $matchTransactionAmount - function matchTransactionAmount($refTransactionId, $refAmount) { $myTransaction = fetchTransaction($refTransactionId); return ($myTransaction->amount == $refAmount); }
   * @param callable $onSuccess - function onSuccess($refTransactionId);
   * @param callable $onFail - function onFail($refTransactionId);
   * @param callable $onDeny - function onDeny($refTransactionId, $reason);
   * @return void
   */
  public function handlePayment(callable $matchTransactionId,
                                callable $matchTransactionAmount,
                                callable $onSuccess,
                                callable $onFail,
                                callable $onDeny)
  {
    try
    {
      if($this->detectPaymentResponse())
      {
        // Match ReferenceNo
        if(call_user_func($matchTransactionId, array($_POST['ReferenceNo'])))
        {
          // Match Response Code
          if($this->matchResponseCode())
          {
            // Match Amount
            if(call_user_func($matchTransactionAmount, array($_POST['ReferenceNo'], $_POST['Transaction_Amount'])))
            {
              // Match RS
              if($this->matchRS())
              {
                call_user_func($onSuccess, array($_POST['ReferenceNo']));
              }
              else
              {
                throw new Exception("RS didn't match.");
              }
            }
            else
            {
              throw new Exception("Amount didn't match.");
            }
          }
          else
          {
            call_user_func($onFail, array($_POST['ReferenceNo']));
          }
        }
        else
        {
          throw new Exception("No such transaction.");
        }
      }
    } 
    catch (Exception $e)
    {
      call_user_func($onDeny, array($_POST['ReferenceNo'], $e->getMessage()));
    }
  }
  
  /**
   * Check for a payment response.
   *
   * @return bool - True if response exists otherwise false.
   */
  private function detectPaymentResponse()
  {
    return (isset($_POST['ReferenceNo']) && isset($_POST['Response_Code']));
  }
  
  /**
   * Match Response Code.
   *
   * E000 = success, otherwise fail.
   *
   * @return bool
   */
  private function matchResponseCode()
  {
    return ($_POST['Response_Code'] == "E000");
  }
  
  /**
   * Match RS (Signature).
   *
   * Needed to confirm that data was not tempered.
   * Only match this after everything else matches since it depends on POST headers.
   *
   * @return bool
   */
  private function matchRS()
  {
    // Find hashed RS.
    $rs = $_POST['ID']."|".
          $_POST['Response_Code']."|".
          $_POST['Unique_Ref_Number']."|".
          $_POST['Service_Tax_Amount']."|".
          $_POST['Processing_Fee_Amount']."|".
          $_POST['Total_Amount']."|".
          $_POST['Transaction_Amount']."|".
          $_POST['Transaction_Date']."|".
          $_POST['Interchange_Value']."|".
          $_POST['TDR']."|".
          $_POST['Payment_Mode']."|".
          $_POST['ReferenceNo']."|".
          $_POST['SubMerchantId']."|".
          $_POST['TPS']."|".
          $this->Key;
    $hashed = hash("sha512", $rs);
    
    // Match to RS in POST header
    return($_POST['RS'] === $hashed);
  }
}

?>