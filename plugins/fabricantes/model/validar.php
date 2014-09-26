<?php
/*
   Plugin Fabricantes para FacturaSctipts
   (c) 2014 JHircano@gmail.com
   -----------------------------------------------------------------------------------------

 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Una clase genércia para validar datos
 */
class validar
{

	/**
	 * Delete unicode class from regular expression patterns
	 * @param string $pattern
	 * @return pattern
	 */
	public static function cleanNonUnicodeSupport($pattern)
	{
		// if (!defined('PREG_BAD_UTF8_OFFSET'))
		//	return $pattern;
		return preg_replace('/\\\[px]\{[a-z]\}{1,2}|(\/[a-z]*)u([a-z]*)$/i', "$1$2", $pattern);
	}

	public static function isAnything()
	{
		return true;
	}

	public static function isString($data)
	{
		return is_string($data);
	}

	public static function isDate($v)
	{
		return preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $v); /// Es una fecha
	}

	public static function isDateTime($v)
	{
		return preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})$/i', $v); /// es una fecha+hora
	}


	public static function isInt($value)
	{
		return ((string)(int)$value === (string)$value || $value === false);
	}

	public static function isUnsignedInt($value)
	{
		return (preg_match('#^[0-9]+$#', (string)$value) && $value < 4294967296 && $value >= 0);
	}

	public static function isName($name)
	{
		return preg_match(validar::cleanNonUnicodeSupport('/^[^0-9!<>,;?=+()@#"°{}_$%:]*$/u'), stripslashes($name));
	}

	public static function isAddress($address)
	{
		return empty($address) || preg_match('/^[^!<>?=+@{}_$%]*$/u', $address);
	}


	/**
	 * Check for product or category name validity
	 * Puede usarse para validar el nombre de cualquier entidad: Artículo, Proveedor, Cliente, Almacén, Familia, etc.
	 */
	public static function isCatalogName($name)
	{
		if ( strlen($name) > 0 )
			return preg_match('/^[^<>;=#{}]*$/u', $name);
		return FALSE;
	}

	/**
	 * Check for a message validity
	 */
	public static function isMessage($message)
	{
		return !preg_match('/[<>{}]/i', $message);
	}



	/**
	 * Check for search query validity
	 *
	 * @param string $search Query to validate
	 * @return boolean Validity is ok or not
	 */
	public static function isValidSearch($search)
	{
		return preg_match('/^[^<>;=#{}]{0,64}$/u', $search);
	}

	/**
	 * Check for standard name validity
	 *
	 * @param string $name Name to validate
	 */
	public static function isGenericName($name)
	{
		return empty($name) || preg_match('/^[^<>={}]*$/u', $name);
	}

	/**
	 * Check for HTML field validity (no XSS please !)
	 *
	 * @param string $html HTML field to validate
	 */
	public static function isCleanHtml($html, $allow_iframe = false)
	{
		$events = 'onmousedown|onmousemove|onmmouseup|onmouseover|onmouseout|onload|onunload|onfocus|onblur|onchange';
		$events .= '|onsubmit|ondblclick|onclick|onkeydown|onkeyup|onkeypress|onmouseenter|onmouseleave|onerror|onselect|onreset|onabort|ondragdrop|onresize|onactivate|onafterprint|onmoveend';
		$events .= '|onafterupdate|onbeforeactivate|onbeforecopy|onbeforecut|onbeforedeactivate|onbeforeeditfocus|onbeforepaste|onbeforeprint|onbeforeunload|onbeforeupdate|onmove';
		$events .= '|onbounce|oncellchange|oncontextmenu|oncontrolselect|oncopy|oncut|ondataavailable|ondatasetchanged|ondatasetcomplete|ondeactivate|ondrag|ondragend|ondragenter|onmousewheel';
		$events .= '|ondragleave|ondragover|ondragstart|ondrop|onerrorupdate|onfilterchange|onfinish|onfocusin|onfocusout|onhashchange|onhelp|oninput|onlosecapture|onmessage|onmouseup|onmovestart';
		$events .= '|onoffline|ononline|onpaste|onpropertychange|onreadystatechange|onresizeend|onresizestart|onrowenter|onrowexit|onrowsdelete|onrowsinserted|onscroll|onsearch|onselectionchange';
		$events .= '|onselectstart|onstart|onstop';

		if (preg_match('/<[\s]*script/ims', $html) || preg_match('/('.$events.')[\s]*=/ims', $html) || preg_match('/.*script\:/ims', $html))
			return false;

		if (!$allow_iframe && preg_match('/<[\s]*(i?frame|form|input|embed|object)/ims', $html))
			return false;

		return true;
	}

	/**
	 * Check for product reference validity
	 *
	 * @param string $reference Product reference to validate
	 */
	public static function isReference($reference)
	{
		return preg_match('/^[^<>;={}]*$/u', $reference);
	}


	public static function isBool($bool)
	{
		return $bool === null || is_bool($bool) || preg_match('/^0|1$/', $bool);
	}

	public static function isPhoneNumber($number)
	{
		return preg_match('/^[+0-9. ()-]*$/', $number);
	}

	/**
	 * Check for barcode validity (EAN-13)
	 *
	 * @param string $ean13 Barcode to validate
	 */
	public static function isEan13($ean13)
	{
		return !$ean13 || preg_match('/^[0-9]{0,13}$/', $ean13);
	}




	/**
	 * Check object validity
	 *
	 * @param object $object Object to validate
	 */
	public static function isLoadedObject($object, $id)
	{
		return is_object($object) && $object->{id};
	}




	public static function isEmail($email)
	{
		return !empty($email) && preg_match(Tools::cleanNonUnicodeSupport('/^[a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]+[.a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]*@[a-z\p{L}0-9]+(?:[.]?[_a-z\p{L}0-9-])*\.[a-z\p{L}0-9]+$/ui'), $email);
	}

	public static function isUrl($url)
	{
		return preg_match('/^[~:#,$%&_=\(\)\.\? \+\-@\/a-zA-Z0-9]+$/', $url);
	}

	public static function isAbsoluteUrl($url)
	{
		if (!empty($url))
			return preg_match('/^https?:\/\/[$~:;#,%&_=\(\)\[\]\.\? \+\-@\/a-zA-Z0-9]+$/', $url);
		return true;
	}

	public static function isSubDomainName($domain)
	{
		return preg_match('/^[a-zA-Z0-9-_]*$/', $domain);
	}


	public static function isMd5($md5)
	{
		return preg_match('/^[a-f0-9A-F]{32}$/', $md5);
	}

	public static function isSha1($sha1)
	{
		return preg_match('/^[a-fA-F0-9]{40}$/', $sha1);
	}

	public static function isFloat($float)
	{
		return strval((float)$float) == strval($float);
	}

	public static function isUnsignedFloat($float)
	{
		return strval((float)$float) == strval($float) && $float >= 0;
	}
	


	public static function isFileName($name)
	{
		return preg_match('/^[a-zA-Z0-9_.-]+$/', $name);
	}

	public static function isDirName($dir)
	{
		return (bool)preg_match('/^[a-zA-Z0-9_.-]*$/', $dir);
	}
}