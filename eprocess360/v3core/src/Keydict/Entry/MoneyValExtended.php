<?php

namespace eprocess360\v3core\Keydict\Entry;

/**
 * Class MoneyValExtended
 * @package eprocess360\v3core\Keydict\Entry
 * Entry type for money value with 16 digits and four decimal places
 */
class MoneyValExtended extends FixedNumber
{
    const TOTAL_LENGTH = 18;
    const DEC_LENGTH = 4;
}