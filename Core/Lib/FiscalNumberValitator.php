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

            case 'cif':

            
        }
    }

    /**
     * Validate a Spanish identification number.
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