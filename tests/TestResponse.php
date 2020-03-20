<?php
namespace Antiquete\Eazypay\Tests;

use DateTime;

class TestResponse{
  
  private $POSTHeaders;
      
  // Success Response Template = array (
  // 'Response_Code' => 'E000',
  // 'Unique_Ref_Number' => '',
  // 'Service_Tax_Amount' => '',
  // 'Processing_Fee_Amount' => '',
  // 'Total_Amount' => '',
  // 'Transaction_Amount' => '',
  // 'Transaction_Date' => '',
  // 'Interchange_Value' => '',
  // 'TDR' => '',
  // 'Payment_Mode' => '',
  // 'SubMerchantId' => '',
  // 'ReferenceNo' => '',
  // 'ID' => '',
  // 'RS' => '',
  // 'TPS' => '',
  // 'mandatory_fields' => '',
  // 'optional_fields' => '',
  // 'RSV' => '',
  // );
  
  // Fail Response Template = array (
  // 'Response_Code' => '',
  // 'Interchange_Value' => '',
  // 'TDR' => '',
  // 'Payment_Mode' => '',
  // 'SubMerchantId' => '',
  // 'ReferenceNo' => '',
  // 'ID' => '',
  // 'RS' => '',
  // 'TPS' => '',
  // 'mandatory_fields' => '',
  // 'optional_fields' => '',
  // 'RSV' => '',
  // );
  
  
  
  public function __construct()
  {

  }
  
  /**
   * Generate POSTHeaders for success response
   *
   * @param [type] $mid
   * @param [type] $submid
   * @param [type] $transactionId
   * @param [type] $amount
   * @param [type] $key
   * @return void
   */
  public function genHeadersSuccess($mid, $submid, $transactionId, $amount, $key)
  {
    $this->POSTHeaders = array();
    $this->POSTHeaders['Response_Code'] = 'E000';
    $this->POSTHeaders['Unique_Ref_Number'] = mt_rand(1000000000000000, 9999999999999999);
    $this->POSTHeaders['Service_Tax_Amount'] = number_format($amount*0.10, 2);
    $this->POSTHeaders['Processing_Fee_Amount'] = number_format($amount*0.01, 2);
    $this->POSTHeaders['Total_Amount'] = number_format($amount + $this->POSTHeaders['Service_Tax_Amount'] + $this->POSTHeaders['Processing_Fee_Amount'], 2);
    $this->POSTHeaders['Transaction_Amount'] = number_format($amount, 2);
    $this->POSTHeaders['Transaction_Date'] = (new DateTime())->format("d-m-Y H:i:s");
    $this->POSTHeaders['Interchange_Value'] = '';
    $this->POSTHeaders['TDR'] = '';
    $this->POSTHeaders['Payment_Mode'] = 'NET_BANKING';
    $this->POSTHeaders['SubMerchantId'] = $submid;
    $this->POSTHeaders['ReferenceNo'] = $transactionId;
    $this->POSTHeaders['ID'] = $mid;
    $this->POSTHeaders['RS'] = $this->getRS($key);
    $this->POSTHeaders['TPS'] = 'Y';
    $this->POSTHeaders['mandatory_fields'] = $transactionId.'|'.$submid.'|'.$this->POSTHeaders['Transaction_Amount'];
    $this->POSTHeaders['optional_fields'] = 'null';
    $this->POSTHeaders['RSV'] = $this->getRSV();
  }
  
  public function genHeadersFail($mid, $submid, $transactionId, $responseCode, $amount, $key)
  {
    $this->POSTHeaders = array();
    $this->POSTHeaders['Response_Code'] = $responseCode;
    $this->POSTHeaders['Interchange_Value'] = '';
    $this->POSTHeaders['TDR'] = '';
    $this->POSTHeaders['Payment_Mode'] = '';
    $this->POSTHeaders['SubMerchantId'] = $submid;
    $this->POSTHeaders['ReferenceNo'] = $transactionId;
    $this->POSTHeaders['ID'] = $mid;
    $this->POSTHeaders['RS'] = $this->getRSV(); // Fill with 128 F.
    $this->POSTHeaders['TPS'] = 'null';
    $this->POSTHeaders['mandatory_fields'] = $transactionId.'|'.$submid.'|'.number_format($amount, 2);
    $this->POSTHeaders['optional_fields'] = 'null';
    $this->POSTHeaders['RSV'] = $this->getRSV();
  }
  
  /**
   * Generate form for POSTHeaders, run after genHeaders.
   *
   * @param [type] $returnUrl
   * @return void
   */
  public function generateForm($returnUrl)
  {
    ob_start();
    ?>
    <form action="<?php echo $returnUrl; ?>" method="post">
      <?php
      foreach ($this->POSTHeaders as $key => $value)
      {
        echo "<input type='hidden' name='$key' value='$value'>";
      }
      echo "<pre>".var_export($this->POSTHeaders, true)."</pre><br>";
      ?>
      <input type="submit" value="Send">
    </form>
    <?php
    return ob_get_clean();
  }
  
  private function getRS($key)
  {
    $rs = $this->POSTHeaders['ID']."|".
          $this->POSTHeaders['Response_Code']."|".
          $this->POSTHeaders['Unique_Ref_Number']."|".
          $this->POSTHeaders['Service_Tax_Amount']."|".
          $this->POSTHeaders['Processing_Fee_Amount']."|".
          $this->POSTHeaders['Total_Amount']."|".
          $this->POSTHeaders['Transaction_Amount']."|".
          $this->POSTHeaders['Transaction_Date']."|".
          $this->POSTHeaders['Interchange_Value']."|".
          $this->POSTHeaders['TDR']."|".
          $this->POSTHeaders['Payment_Mode']."|".
          $this->POSTHeaders['SubMerchantId']."|".
          $this->POSTHeaders['ReferenceNo']."|".
          $this->POSTHeaders['TPS']."|".
          $key;
    $hashed = hash("sha512", $rs);
    return $hashed;
  }
  
  private function getRSV()
  {
    // Non random placeholder RSV.
    $rsv = "";
    
    for($i=0; $i<128; $i++)
    {
      $rsv .= "f";
    }
    
    return $rsv;
  }
}
?>