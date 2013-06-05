<?php
namespace Rocker\Utils\Security;

/**
 * RC4 symmetric cipher encryption/decryption
 *
 * @package rocker/server
 * @author Ali Farhadi (http://farhadi.ir/)
 * @license Gnu Public License, see the GPL for details.
 */
class RC4Cipher {

    /**
     * Encrypt given plain text using the key with RC4 algorithm.
     * All parameters and return value are in binary format.
     *
     * @static
     * @param string $key - secret key for encryption
     * @param string $pt - plain text to be encrypted
     * @return string
     */
    public static function encrypt($key, $pt)
    {
        return self::doEncrypt($key, $pt);
       # return base64_encode(self::doEncrypt($key, $pt));
    }

    /**
     * Decrypt given cipher text using the key with RC4 algorithm.
     * All parameters and return value are in binary format.
     *
     * @param string $key - secret key for decryption
     * @param string $ct - cipher text to be decrypted
     * @return string
    */
    static function decrypt($key, $pt)
    {
        return self::doDecrypt($key, $pt);
        #return self::doDecrypt($key, base64_decode($pt));
        # Strangely enough but you cant call base64_encode/decode inside theses two functions
        # it just wont work, but it works when doing the same thing outside these functions...
    }

    /**
     * @static
     * @param $key
     * @param $pt
     * @return string
     */
    private static function doEncrypt($key, $pt)
    {
    	$s = array();
    	for ($i=0; $i<256; $i++) {
    		$s[$i] = $i;
    	}
    	$j = 0;
    	$x = null;
    	for ($i=0; $i<256; $i++) {
    		$j = ($j + $s[$i] + ord($key[$i % strlen($key)])) % 256;
    		$x = $s[$i];
    		$s[$i] = $s[$j];
    		$s[$j] = $x;
    	}
    	$i = 0;
    	$j = 0;
    	$ct = '';
    	$y = null;
    	for ($y=0; $y<strlen($pt); $y++) {
    		$i = ($i + 1) % 256;
    		$j = ($j + $s[$i]) % 256;
    		$x = $s[$i];
    		$s[$i] = $s[$j];
    		$s[$j] = $x;
    		$ct .= $pt[$y] ^ chr($s[($s[$i] + $s[$j]) % 256]);
    	}
    	return $ct;
    }

    /**
     * @ignore
     * @static
     * @param $key
     * @param $ct
     * @return string
     */
    private static function doDecrypt($key, $ct)
    {
    	return self::encrypt($key, $ct);
    }
}