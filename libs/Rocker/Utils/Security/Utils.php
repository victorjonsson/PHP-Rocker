<?php
namespace Rocker\Utils\Security;


/**
 * Class with utility functions used when working with user input
 *
 * @package rocker/server
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

    /**
     * Converts a string to an url friendly string. "Åskan slog ner i en moské" converts
     * to "askan-slog-ner-i-en-moske"
     * @static
     * @param string $str
     * @param string $hyphen
     * @param string $slash
     * @return string
     */
    public static function toUrlFriendlyString($str, $hyphen = '-', $slash = '') {

        if(self::containsNonValidUTF8($str))
            $str = mb_convert_encoding($str, 'UTF-8');

        if(self::containsEntities($str))
            $str = html_entity_decode($str, ENT_NOQUOTES, 'UTF-8');

        $find = array(' ', '/', '\\', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
        $replace = array($hyphen, $slash, $slash, 'A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
        $str = str_replace($find, $replace, $str);

        $str = htmlentities($str, ENT_NOQUOTES, 'UTF-8');
        $str = html_entity_decode(preg_replace('/\&([a-z])acute;/', "$1", $str), ENT_NOQUOTES, 'UTF-8');

        return trim(preg_replace('/[^a-z0-9\-]/', '', strtolower($str)), '-');
    }
}
