<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by WalletHelper / SysCoinHelper when a hold cannot be placed because
 * the locked, re-validated balance is below the required amount. Callers catch
 * this to roll back and return a clean "insufficient balance" response instead
 * of allowing a negative balance / double-spend.
 */
class InsufficientFundsException extends RuntimeException
{
    //
}
