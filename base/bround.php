<?php
/*
    Banker's Rounding v1.01, 2006-08-15
    Copyright 2006 Michael Boone
    mike@Xboonedocks.net (remove the X)
    http://boonedocks.net/

	Provided under the a BSD-style License
	A GPL licensed version is available at:
	http://boonedocks.net/code/bround.inc.phps
    Contact me for use outside the bounds of these licenses
	
	---------------------------------------------------------------
	Copyright (c) 2006 Michael Boone

	All rights reserved.

	Redistribution and use in source and binary forms, with or
	without modification, are permitted provided that the following
	conditions are met:
		* Redistributions of source code must retain the above
		  copyright notice, this list of conditions and the
		  following disclaimer.
		* Redistributions in binary form must reproduce the above
		  copyright notice, this list of conditions and the
		  following disclaimer in the documentation and/or other
		  materials provided with the distribution.
		* Neither the name of boonedocks.net nor the name of
		  Michael Boone may be used to endorse or promote products
		  derived from this software without specific prior
		  written permission.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
	"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
	LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
	A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
	CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
	EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
	PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
	PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
	LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
	NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	---------------------------------------------------------------
	
    Release History:
    2006-01-05: v1.00: Initial Release
    2006-08-15: v1.01: Updated with faster even/odd test
    
*/

/**
 * Redondeo bancario
 * @staticvar real $dFuzz
 * @param type $dVal
 * @param type $iDec
 * @return type
 */
function bround($dVal, $iDec=2)
{
   // banker's style rounding or round-half-even
   // (round down when even number is left of 5, otherwise round up)
   // $dVal is value to round
   // $iDec specifies number of decimal places to retain
   static $dFuzz = 0.00001; // to deal with floating-point precision loss
   $iRoundup = 0; // amount to round up by
   
   $iSign = ($dVal != 0.0) ? intval($dVal / abs($dVal)) : 1;
   $dVal = abs($dVal);
   
   // get decimal digit in question and amount to right of it as a fraction
   $dWorking = $dVal*pow(10.0,$iDec+1)-floor($dVal*pow(10.0,$iDec))*10.0;
   $iEvenOddDigit = floor($dVal*pow(10.0,$iDec))-floor($dVal*pow(10.0,$iDec-1))*10.0;
   
   if( abs($dWorking - 5.0) < $dFuzz )
      $iRoundup = ($iEvenOddDigit & 1) ? 1 : 0;
   else
      $iRoundup = ($dWorking>5.0) ? 1 : 0;
   
   return $iSign*((floor($dVal*pow(10.0,$iDec))+$iRoundup)/pow(10.0,$iDec));
}

?>