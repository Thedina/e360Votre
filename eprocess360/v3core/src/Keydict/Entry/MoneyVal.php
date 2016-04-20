<?php

namespace eprocess360\v3core\Keydict\Entry;

/**
 * Class MoneyVal
 * @package eprocess360\v3core\Keydict\Entry\Bits
 * Entry type for money value with 16 digits and two decimal places
 */
class MoneyVal extends FixedNumber
{
    const TOTAL_LENGTH = 16;
    const DEC_LENGTH = 2;
}