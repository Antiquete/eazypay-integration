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

use DateTime;

class Transaction{
  private $TransactionID;
  private DateTime $StartTime;
  private DateTime $EndTime;
  private $Amount;
  
  public function __construct($amount)
  {
    $this->TransactionID = $this->genTransactionId();
    $this->StartTime = new DateTime();
    $this->EndTime = NULL;
    $this->Amount = $amount;
  }
  
  public function getId()
  {
    return $this->TransactionID;
  }
  public function setId()
  {
    $this->TransactionID = $this->genTransactionId();
  }
  
  public function getAmount()
  {
    return $this->Amount;
  }
  
  /** Generate 10 Char long Transaction ID */
  private function genTransactionId()
  {
    $lenght = 10;
    $bytes = random_bytes(ceil($lenght / 2));
    return substr(bin2hex($bytes), 0, $lenght);
  }
}
?>