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

- onFail - What to do on payment fail?

```php
function onFail($refTransactionId) { /* ... */ }
```

- onDeny - What to do on payment data mismatch. '\$reason' will show the reason why payment was rejected?

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

To create and store a transaction (Steps 1 & 2),

```php
use Antiquete\Eazypay\Transaction;

function createTransaction($amount)
{
    $transaction = new Transaction($amount);
    if($database->existsTransaction($transaction->getId()))
    {
        delete $transaction;
        return createTransaction($amount);
    }
    else
    {
        // Insert into database
        return $transaction;
    }
}
```

To generate payment link (Step 3),

```php
use Antiquete\Eazypay\Eazypay;

$eazypay = new Eazypay("Merchant ID", "Merchant Reference", "Sub Merchant Id", "Return URL", "Merchant Key");
$link = $eazypay->getLink($transaction);
```

To handle response (Step 4 & 5),

```php
use Antiquete\Eazypay\Eazypay;

function matchTransactionId($refTransactionId) { /* ... */ }
function matchTransactionAmount($refTransactionId, $refAmount) { /* ... */ }
function onSuccess($refTransactionId) { /* ... */ }
function onFail($refTransactionId) { /* ... */ }
function onDeny($refTransactionId, $reason) { /* ... */ }

$eazypay->handlePayment(matchTransactionId, matchTransactionAmount, onSuccess, onFail, onDeny);
```
