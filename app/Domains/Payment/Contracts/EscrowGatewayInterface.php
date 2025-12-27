<?php

namespace App\Domains\Payment\Contracts;

use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;

interface EscrowGatewayInterface extends PaymentGatewayInterface
{
    /**
     * Record a ledger entry for an escrowed payment.
     * Called after successful payment to credit provider's virtual balance.
     */
    public function recordToLedger(Payment $payment): LedgerEntry;
}
