<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 7:16 PM
 */

namespace eprocess360\v3core\Model;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\Email;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FixedString256;
use eprocess360\v3core\Keydict\Entry\FixedString32;
use eprocess360\v3core\Keydict\Entry\Flag;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Entry\JSONArrayFixed128;
use eprocess360\v3core\Keydict\Entry\Password;
use eprocess360\v3core\Keydict\Entry\PhoneNumber;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String64;
use eprocess360\v3core\Keydict\Entry\String8;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;

/**
 * @package eprocess360\v3core\Model
 */
class Users extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idUser', 'User ID'),
            Email::build('email', 'E-mail Address'),
            Email::build('alternateEmail', 'Alternate E-mail'),
            Password::build('password', 'Password')->setMeta('ignore'),
            String8::build('resetCode', 'Reset Code'),
            String64::build('firstName', 'First Name'),
            String64::build('lastName', 'Last Name'),
            PhoneNumber::build('phone', 'Phone Number'),
            Bits8::make(
                'status',
                Bit::build(0, 'isActive', 'Account Active'),
                Bit::build(1, 'isSystem', 'System Account'),
                Bit::build(2, 'canLogin', 'Can Login'),
                Bit::build(3, 'isAway', 'Currently Away'),
                Bit::build(4, 'mustChangePassword', 'Password Change Required'),
                Bit::build(5, 'hasAcceptedEULA', 'Accepted EULA')
            )
        )->setName('Users')->setLabel('Users');
    }
}