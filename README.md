<!-- @format -->

# Eazypay Integration

PHP 7 Integration for ICICI Eazypay API.

## Installation

```bash
composer require antiquete/eazypay-integration
```

## Requirements

### Database

A table to hold transactions. Should have a 10 char long unique field for transaction_id.
Something that matches following should be sufficient.

```sql
CREATE TABLE `transactions` (
 `transaction_id` varchar(10) NOT NULL COMMENT 'Unique 10 Chars long transaction id. Neccessary!',
 `amount` decimal(19,4) NOT NULL COMMENT 'Amount of transaction.',
 `start_time` datetime NOT NULL COMMENT 'DATETIME of when transaction got created.',
 `end_time` datetime DEFAULT NULL COMMENT 'DATETIME of whne transaction ended (Completed/Failed).',
 `response_code` varchar(6) DEFAULT NULL COMMENT 'Response Code as received from Eazypay server. This should be used to verify whether payment succeded',
 PRIMARY KEY (`transaction_id`)
)
```

### Callbacks (Optional)

These are only needed if handling response through library.

- matchTransactionId - This should match 'transaction_id' stored in database with '\$refTransactionId' and return true or false depending on result.

```php
function matchTransactionId($refTransactionId) { /* ... */ }
```

- matchTransactionAmount - This should match '\$refAmount' with amount stored in database for transaction with '\$refTransactionId' and return 'true' or 'false' depending on result.

```php
function matchTransactionAmount($refTransactionId, $refAmount) { /* ... */ }
```

- onSuccess - What to do on payment success?

```php
function onSuccess($refTransactionId) { /* ... */ }
```

- onFail - What to do on payment fail? '\$refResponseCode' will be response code of payment response received, store it along with transaction.

```php
function onFail($refTransactionId, $refResponseCode) { /* ... */ }
```

- onDeny - What to do on payment data mismatch? '\$reason' will show the reason why payment was rejected?

```php
function onDeny($refTransactionId, $reason) { /* ... */ }
```

## Usage

Two classes exist

- Eazypay - To handle link generation and response handling.
- Transaction - To handle 'transaction_id' generation.

Workflow to follow,

- 1. Create a transaction.
- 2. Store transaction with 'transaction_id' in database.
- 3. Generate payment link using that transaction.
- 4. (Optional) 'handleResponse' on Return URL page.
- 5. (Optional) Update transcation in database on payment 'onSuccess' or 'onFail'.

### Examples
To create and store a transaction with unique checking (Steps 1 & 2),

```php
use Antiquete\Eazypay\Transaction;
$transaction = new Transaction($amount);
while($database->existsTransaction($transaction->id())) // Check is a entry with transaction_id exists in database
{
    $transaction->refreshId();  // Keep refreshing ids until a unique id is found.
}
// Insert transaction with $transaction->id() in database here.
```

To generate payment link (Step 3),

```php
use Antiquete\Eazypay\Eazypay;
$eazypay = new Eazypay(EAZYPAY_MERCHANT_ID,
                       EAZYPAY_MERCHANT_REFERENCE,
                       EAZYPAY_SUBMERCHANT_ID,
                       EAZYPAY_RETURN_URL,
                       EAZYPAY_KEY);
$link = $eazypay->getLink($transaction);
```

To handle response (Step 4 & 5),

```php
use Antiquete\Eazypay\Eazypay;

function matchTransactionId($refTransactionId) { /* ... */ }
function matchTransactionAmount($refTransactionId, $refAmount) { /* ... */ }
function onSuccess($refTransactionId) { /* ... */ }
function onFail($refTransactionId, $refResponseCode) { /* ... */ }
function onDeny($refTransactionId, $reason) { /* ... */ }

$eazypay->handlePayment('matchTransactionId', 'matchTransactionAmount', 'onSuccess', 'onFail', 'onDeny');   // Return value is a bool representing if payment response was received.
```

## License
This is an open source library licensed under the LGPLv3.