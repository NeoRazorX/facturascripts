<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib;

/**
 * Verify numbers of fiscal identity
 *
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class FiscalNumberValitator
{

    /**
     * Check the number depend of type and return true if the number if valid.
     *
     * @param string $type
     * @param string $number
     * @return void
     */
    public static function validate($type = 'nif', $number)
    {
        $type = \strtolower($type);

        switch ($type) {
            case 'nif':
                return self::isValidNIF($number);
            case 'nie':
                return self::isValidNIE($number);
            case 'cif':
                return self::isValidCIF($number);
        }
    }

    /**
     * This function validates a Spanish identification number
     * verifying its check digits.
     *
     * This function is intended to work with CIF numbers.
     *
     * @param string $docNumber
     * @return boolean
     */
    private static function isValidCIF($docNumber)
    {
        $isValid = FALSE;
        $fixedDocNumber = "";
        $correctDigit = "";
        $writtenDigit = "";
        $fixedDocNumber = strtoupper( $docNumber );
        $writtenDigit = substr( $fixedDocNumber, -1, 1 );
        if( self::isValidCIFFormat( $fixedDocNumber ) == 1 ) {
            $correctDigit = self::getCIFCheckDigit( $fixedDocNumber );
            if( $writtenDigit == $correctDigit ) {
                $isValid = TRUE;
            }
        }
        return $isValid;
    }

    /**
     * This function calculates the check digit for a corporate Spanish
     * identification number (CIF).
     * 
     * Return the correct check digit if provided string had a correct CIF structure or an empty string otherwise
     *
     * @param string $docNumber
     * @return string
     */
    private static function getCIFCheckDigit($docNumber)
    {
        $fixedDocNumber = "";
        $centralChars = "";
        $firstChar = "";
        $evenSum = 0;
        $oddSum = 0;
        $totalSum = 0;
        $lastDigitTotalSum = 0;
        $correctDigit = "";
        $fixedDocNumber = strtoupper( $docNumber );
        if( self::isValidCIFFormat( $fixedDocNumber ) ) {
            $firstChar = substr( $fixedDocNumber, 0, 1 );
            $centralChars = substr( $fixedDocNumber, 1, 7 );
            $evenSum =
                substr( $centralChars, 1, 1 ) +
                substr( $centralChars, 3, 1 ) +
                substr( $centralChars, 5, 1 );
            $oddSum =
                self::sumDigits( substr( $centralChars, 0, 1 ) * 2 ) +
                self::sumDigits( substr( $centralChars, 2, 1 ) * 2 ) +
                self::sumDigits( substr( $centralChars, 4, 1 ) * 2 ) +
                self::sumDigits( substr( $centralChars, 6, 1 ) * 2 );
            $totalSum = $evenSum + $oddSum;
            $lastDigitTotalSum = substr( $totalSum, -1 );
            if( $lastDigitTotalSum > 0 ) {
                $correctDigit = 10 - ( $lastDigitTotalSum % 10 );
            } else {
                $correctDigit = 0;
            }
        }
        
        if( preg_match( '/[PQSNWR]/', $firstChar ) ) {
            $correctDigit = substr( "JABCDEFGHI", $correctDigit, 1 );
        }
        return $correctDigit;
    }

    /**
     * This function performs the sum, one by one, of the digits in a given quantity
     *
     * @param string $digits
     * @return int
     */
    private static function sumDigits($digits)
    {
        $total = 0;
        $i = 1;
        while( $i <= strlen( $digits ) ) {
            $thisNumber = substr( $digits, $i - 1, 1 );
            $total += $thisNumber;
            $i++;
        }
        return $total;
    }

    /**
     * This function validates the format of a given string in order to
     * see if it fits with CIF format.
     *
     * @param string $docNumber
     * @return boolean
     */
    private static function isValidCIFFormat($docNumber)
    {
        return
            self::respectsDocPattern(
                $docNumber,
                '/^[PQSNWR][0-9][0-9][0-9][0-9][0-9][0-9][0-9][A-Z0-9]/' )
        ||
            self::respectsDocPattern(
                $docNumber,
                '/^[ABCDEFGHJUV][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]/' );
    }

    /**
     * This function validates a Spanish identification number
     * verifying its check digits.
     * 
     * This function is intended to work with NIE numbers.
     *
     * @param string $docNumber
     * @return boolean
     */
    private static function isValidNIE($docNumber)
    {
        $isValid = FALSE;
        $fixedDocNumber = "";
        if( !preg_match( "/^[A-Z]+$/i", substr( $fixedDocNumber, 1, 1 ) ) ) {
            $fixedDocNumber = strtoupper( substr( "000000000" . $docNumber, -9 ) );
        } else {
            $fixedDocNumber = strtoupper( $docNumber );
        }
        if( self::isValidNIEFormat( $fixedDocNumber ) ) {
            if( substr( $fixedDocNumber, 1, 1 ) == "T" ) {
                $isValid = TRUE;
            } else {
                $numberWithoutLast = substr( $fixedDocNumber, 0, strlen($fixedDocNumber)-1 );
                $lastDigit = substr( $fixedDocNumber, strlen($fixedDocNumber)-1, strlen($fixedDocNumber) );
                $numberWithoutLast = str_replace('Y', '1', $numberWithoutLast);
                $numberWithoutLast = str_replace('X', '0', $numberWithoutLast);
                $numberWithoutLast = str_replace('Z', '2', $numberWithoutLast);
                $fixedDocNumber = $numberWithoutLast . $lastDigit;
                $isValid = self::isValidNIF( $fixedDocNumber );
            }
        }
        return $isValid;
    }

    /**
     * This function validates the format of a given string in order to
     * see if it fits with NIE format. Practically, it performs a validation
     * over a NIE, except this function does not check the check digit.
     *
     * @param string $docNumber
     * @return boolean
     */
    private static function isValidNIEFormat($docNumber)
    {
        return self::respectsDocPattern(
            $docNumber,
            '/^[XYZT][0-9][0-9][0-9][0-9][0-9][0-9][0-9][A-Z0-9]/' );
    }

    /**
     * Validate a Spanish identification number.
     * 
     * This function is intended to work with NIF numbers.
     *
     * @param string $docNumber
     * @return boolean
     */
    private static function isValidNIF($docNumber)
    {
        $isValid = FALSE;
        $fixedDocNumber = "";
        $correctDigit = "";
        $writtenDigit = "";
        if( !preg_match( "/^[A-Z]+$/i", substr( $fixedDocNumber, 1, 1 ) ) ) {
            $fixedDocNumber = strtoupper( substr( "000000000" . $docNumber, -9 ) );
        } else {
            $fixedDocNumber = strtoupper( $docNumber );
        }
        $writtenDigit = strtoupper(substr( $docNumber, -1, 1 ));
        if( self::isValidNIFFormat( $fixedDocNumber ) ) {
            $correctDigit = self::getNIFCheckDigit( $fixedDocNumber );
            if( $writtenDigit == $correctDigit ) {
                $isValid = TRUE;
            }
        }
        return $isValid;
    }

    /**
     * This function calculates the check digit for an individual Spanish
     * identification number (NIF).
     * 
     * eturns check digit if provided string had a correct NIF structure and empty string otherwise
     *
     * @param string $docNumber
     * @return string
     */
    private static function getNIFCheckDigit($docNumber)
    {
        $keyString = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $fixedDocNumber = "";
        $position = 0;
        $writtenLetter = "";
        $correctLetter = "";
        if( !preg_match( "/^[A-Z]+$/i", substr( $fixedDocNumber, 1, 1 ) ) ) {
            $fixedDocNumber = strtoupper( substr( "000000000" . $docNumber, -9 ) );
        } else {
            $fixedDocNumber = strtoupper( $docNumber );
        }
        $isValidNIFFormat = self::isValidNIFFormat( $fixedDocNumber );
        if( $isValidNIFFormat ) {
            $writtenLetter = substr( $fixedDocNumber, -1 );
            if( $isValidNIFFormat ) {
                $fixedDocNumber = str_replace( 'K', '0', $fixedDocNumber );
                $fixedDocNumber = str_replace( 'L', '0', $fixedDocNumber );
                $fixedDocNumber = str_replace( 'M', '0', $fixedDocNumber );
                $position = substr( $fixedDocNumber, 0, 8 ) % 23;
                $correctLetter = substr( $keyString, $position, 1 );
            }
        }
        return $correctLetter;
    }

    /**
     * This function validates the format of a given string in order to
     * see if it fits with NIF format. Practically, it performs a validation
     * over a NIF, except this function does not check the check digit.
     *
     * @param string $docNumber
     * @return boolean
     */
    private static function isValidNIFFormat($docNumber)
    {
        return self::respectsDocPattern(
            $docNumber,
            '/^[KLM0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][a-zA-Z0-9]/' );
    }

    /**
     * This function validates the format of a given string in order to
     * see if it fits a regexp pattern.
     *
     * @param string $givenString
     * @param Pattern $pattern
     * @return bool
     */
    private static function respectsDocPattern($givenString, $pattern)
    {
        $isValid = FALSE;
        $fixedString = strtoupper( $givenString );
        if( is_int( substr( $fixedString, 0, 1 ) ) ) {
            $fixedString = substr( "000000000" . $givenString , -9 );
        }
        if( preg_match( $pattern, $fixedString ) ) {
            $isValid = TRUE;
        }
        return $isValid;
    }
}