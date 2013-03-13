<?php
namespace Rocker\Utils\Security;


/**
 * Class with utility functions used when working with user input
 *
 * @package Rocker\Utils
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class Utils {

    /**
     * Tells whether or not string contains entities
     * @param string $str
     * @return bool
     */
    public static function containsEntities($str)
    {
        return preg_match('/\&([a-zA-Z]*)\;/', $str) > 0;
    }

    /**
     * Tells whether or not string contains non valid UTF-8 byte ...
     * @static
     * @author javalc6 at gmail dot com (modified)
     * @see http://www.php.net/manual/en/function.mb-check-encoding.php#95289
     * @param string $str
     * @return bool
     */
    public static function containsNonValidUTF8($str)
    {
        if(preg_replace("[a-zA-Z0-9\S]", '', $str) == '')
            return false;

        $len = strlen($str);
        for($i = 0; $i < $len; $i++){
            $c = ord($str[$i]);
            if($c > 128) {
                if(($c > 247))
                    return true;
                elseif ($c > 239)
                    $bytes = 4;
                elseif ($c > 223)
                    $bytes = 3;
                elseif ($c > 191)
                    $bytes = 2;
                else
                    return true;
                if(($i + $bytes) > $len)
                    return true;
                while ($bytes > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191)
                        return true;
                    $bytes--;
                }
            }
        }
        return false;
    }


    /**
     * Creates a crypted string with salt out of the given string
     * @see String::validatedCryptedString
     * @param string $str
     * @return string
     */
    public static function toCryptedString($str)
    {
        return crypt($str, self::makeSalt());
    }

    /**
     * @static
     * @see util\Security\Util::toCryptedString
     * @param string $str
     * @param string $hash
     * @return bool
     */
    public static function validateCryptedString($str, $hash)
    {
        return $hash == crypt($str, $hash);
    }

    /**
     * @ignore
     * @return string
     */
    private static function makeSalt()
    {
        $seed = "./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        $algo = '$2a';
        $strength = '$08';
        $salt = '$';
        for ($i = 0; $i < 22; $i++)
            $salt .= substr($seed, mt_rand(0, 63), 1);
        return $algo . $strength . $salt;
    }

}
