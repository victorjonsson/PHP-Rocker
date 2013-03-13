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

    /**
     * Tells whether or not given string is an e-mail address
     * @param string $str
     * @return bool
     */
    public static function isEmail($str)
    {
        $valid = filter_var($str, FILTER_VALIDATE_EMAIL);
        if($valid !== false) {
            $host = explode('@', $str);
            if(empty($host[1]) || strlen($host[0]) > 320)
                $valid = false;
            else {
                $valid = self::isDomainName($host[1]);
            }
        }

        return $valid !== false; // $valid can be what ever, return true as long as its not false
    }


    /**
     * Tells whether or not given string is a valid domain name
     * @param string $str
     * @return bool
     */
    public static function isDomainName($str)
    {
        // filter_var('http://google-se', FILTER_VALIDATE_URL) does not return false for some reason?

        $str = str_replace(array('http://', 'www.'), '', rtrim($str, '/'));
        $top_domains = 	array(
            '.com','.net','.org','.biz','.coop','.info','.museum','.name',
            '.pro','.edu','.gov','.int','.mil','.ac','.ad','.ae','.af','.ag',
            '.ai','.al','.am','.an','.ao','.aq','.ar','.as','.at','.au','.aw',
            '.az','.ba','.bb','.bd','.be','.bf','.bg','.bh','.bi','.bj','.bm',
            '.bn','.bo','.br','.bs','.bt','.bv','.bw','.by','.bz','.ca','.cc',
            '.cd','.cf','.cg','.ch','.ci','.ck','.cl','.cm','.cn','.co','.cr',
            '.cu','.cv','.cx','.cy','.cz','.de','.dj','.dk','.dm','.do','.dz',
            '.ec','.ee','.eg','.eh','.er','.es','.et','.fi','.fj','.fk','.fm',
            '.fo','.fr','.ga','.gd','.ge','.gf','.gg','.gh','.gi','.gl','.gm',
            '.gn','.gp','.gq','.gr','.gs','.gt','.gu','.gv','.gy','.hk','.hm',
            '.hn','.hr','.ht','.hu','.id','.ie','.il','.im','.in','.io','.iq',
            '.ir','.is','.it','.je','.jm','.jo','.jp','.ke','.kg','.kh','.ki',
            '.km','.kn','.kp','.kr','.kw','.ky','.kz','.la','.lb','.lc','.li',
            '.lk','.lr','.ls','.lt','.lu','.lv','.ly','.ma','.mc','.md','.mg',
            '.mh','.mk','.ml','.mm','.mn','.mo','.mp','.mq','.mr','.ms','.mt',
            '.mu','.mv','.mw','.mx','.my','.mz','.na','.nc','.ne','.nf','.ng',
            '.ni','.nl','.no','.np','.nr','.nu','.nz','.om','.pa','.pe','.pf',
            '.pg','.ph','.pk','.pl','.pm','.pn','.pr','.ps','.pt','.pw','.py',
            '.qa','.re','.ro','.rw','.ru','.sa','.sb','.sc','.sd','.se','.sg',
            '.sh','.si','.sj','.sk','.sl','.sm','.sn','.so','.sr','.st','.sv',
            '.sy','.sz','.tc','.td','.tf','.tg','.th','.tj','.tk','.tm','.tn',
            '.to','.tp','.tr','.tt','.tv','.tw','.tz','.ua','.ug','.uk','.um',
            '.us','.uy','.uz','.va','.vc','.ve','.vg','.vi','.vn','.vu','.ws',
            '.wf','.ye','.yt','.yu','.za','.zm','.zw', '.name', '.mobi', '.xxx',
            '.eu'
        );

        $last_dot_position = strrpos($str, '.');
        if(!$last_dot_position)
            return false;

        $domain = substr($str, 0, $last_dot_position);

        if(!in_array(substr($str, $last_dot_position, strlen($str)), $top_domains))
            return false;
        if($last_dot_position < 2 || $last_dot_position > 57)
            return false;

        $first_char = substr($domain, 0, 1);
        $domain_strlen = strlen($domain);
        $last_char = substr($domain, $domain_strlen - 1, $domain_strlen);

        if($first_char == '-' || $first_char == '.' || $last_char == '-' || $last_char == '.')
            return false;

        if(count(explode('.', $domain)) > 3 || count(explode('..', $domain)) > 1)
            return false;

        if(preg_replace('/[0-9a-z\.\-]/', '', $domain) != '')
            return false;

        return true;
    }

}
