<?php

namespace thgs\Olographos;

class Olographos
{
    /*
    | Olographos class
    |-=-=-=-=-=-=-=-=-=-
    |
    | I wrote this a while ago, now went through it all wrapping it in a class.
    | Has some introspective comments :)
    |
    |
    | Limitations: max number is 999,999.99
    |
    | Version: 0.1b - Improved comments and whitespace :)
   */

    const STR_EURO = 'ΕΥΡΩ';
    const STR_AND = 'ΚΑΙ';
    const STR_CENTS = 'ΛΕΠΤΑ';
    const STR_THOUSANDS = 'ΧΙΛΙΑΔΕΣ';

    protected static $representations = [
        '0'    => '',
        '1'    => 'ΕΝΑ',
        '2'    => 'ΔΥΟ',
        '3'    => 'ΤΡΙΑ',
        '4'    => 'ΤΕΣΣΕΡΑ',
        '5'    => 'ΠΕΝΤΕ',
        '6'    => 'ΕΞΙ',
        '7'    => 'ΕΠΤΑ',
        '8'    => 'ΟΚΤΩ',
        '9'    => 'ΕΝΝΙΑ',
        '10'   => 'ΔΕΚΑ',
        '11'   => 'ΕΝΤΕΚΑ',
        '12'   => 'ΔΩΔΕΚΑ',
        '13'   => 'ΔΕΚΑ ΤΡΙΑ',
        '14'   => 'ΔΕΚΑ ΤΕΣΣΕΡΑ',
        '15'   => 'ΔΕΚΑ ΠΕΝΤΕ',
        '16'   => 'ΔΕΚΑ ΕΞΙ',
        '17'   => 'ΔΕΚΑ ΕΠΤΑ',
        '18'   => 'ΔΕΚΑ ΟΚΤΩ',
        '19'   => 'ΔΕΚΑ ΕΝΝΙΑ',
        '20'   => 'ΕΙΚΟΣΙ',
        '30'   => 'ΤΡΙΑΝΤΑ',
        '40'   => 'ΣΑΡΑΝΤΑ',
        '50'   => 'ΠΕΝΗΝΤΑ',
        '60'   => 'ΕΞΗΝΤΑ',
        '70'   => 'ΕΒΔΟΜΗΝΤΑ',
        '80'   => 'ΟΓΔΟΝΤΑ',
        '90'   => 'ΕΝΕΝΗΝΤΑ',
        '100'  => 'ΕΚΑΤΟ',
        '200'  => 'ΔΙΑΚΟΣΙΑ',
        '300'  => 'ΤΡΙΑΚΟΣΙΑ',
        '400'  => 'ΤΕΤΡΑΚΟΣΙΑ',
        '500'  => 'ΠΕΝΤΑΚΟΣΙΑ',
        '600'  => 'ΕΞΑΚΟΣΙΑ',
        '700'  => 'ΕΠΤΑΚΟΣΙΑ',
        '800'  => 'ΟΚΤΑΚΟΣΙΑ',
        '900'  => 'ΕΝΝΙΑΚΟΣΙΑ',
        '1000' => 'ΧΙΛΙΑ',
    ];

    protected static $thousand_corrections = [
        'ΔΙΑΚΟΣΙΑ'   => 'ΔΙΑΚΟΣΙΕΣ',
        'ΤΡΙΑΚΟΣΙΑ'  => 'ΤΡΙΑΚΟΣΙΕΣ',
        'ΤΕΤΡΑΚΟΣΙΑ' => 'ΤΕΤΡΑΚΟΣΙΕΣ',
        'ΠΕΝΤΑΚΟΣΙΑ' => 'ΠΕΝΤΑΚΟΣΙΕΣ',
        'ΕΞΑΚΟΣΙΑ'   => 'ΕΞΑΚΟΣΙΕΣ',
        'ΕΠΤΑΚΟΣΙΑ'  => 'ΕΠΤΑΚΟΣΙΕΣ',
        'ΟΚΤΑΚΟΣΙΑ'  => 'ΟΚΤΑΚΟΣΙΕΣ',
        'ΕΝΝΙΑΚΟΣΙΑ' => 'ΕΝΝΙΑΚΟΣΙΕΣ',
    ];

    protected static $grammar_corrections = [
        'ΕΝΑ ΧΙΛΙΑΔΕΣ'     => 'ΧΙΛΙΑ',
        ' ΕΝΑ ΧΙΛΙΑΔΕΣ'    => ' ΜΙΑ ΧΙΛΙΑΔΕΣ',
        'ΕΚΑΤΟ '           => 'ΕΚΑΤΟΝ ',
        'ΤΡΙΑ ΧΙΛΙΑΔΕΣ'    => 'ΤΡΕΙΣ ΧΙΛΙΑΔΕΣ',
        'ΤΕΣΣΕΡΑ ΧΙΛΙΑΔΕΣ' => 'ΤΕΣΣΕΡΙΣ ΧΙΛΙΑΔΕΣ',
    ];

    public static function nt_prim($n, $append = false)
    {
        // if $n is not a number return false and exit function
        if (!is_numeric($n)) {
            return false;
        }

        // case $n contains thousands
        if ($n > 1000) {
            // process thousands recursively and correct any mistakes in representation
            // due to thousands word in greek (xiliades)
            $tnum = (int) ($n / 1000);
            $pretext = strtr(
                self::number_text($tnum).' '.self::STR_THOUSANDS.' ',
                self::$thousand_corrections
            );

            // remove thousands from number
            // 0.1-old code: while ($n >= 1000) $n -= 1000;
            $n = $n % 1000;
        }

        // case $n is exactly 1000
        if ($n == 1000) {
            $pretext = self::$representations['1000'];
            $n -= 1000;                                                             // not sure if we need this line
            // why not return here ?
        }

        $text = (isset($pretext)) ? $pretext : '';

        // look for the closest representation, performing one iteration
        // over all representations
        $plimit = 0;
        foreach (self::$representations as $limit => $desc) {
            $ilimit = (int) $limit;

            if ($ilimit <= $n) {
                // store current limit, to be used as last found representation
                $plimit = $limit;
                continue;
            } else {
                // store last found representation
                $text .= self::$representations[$plimit];

                // subtract the amount of last used representation from the number
                $n -= $plimit;
                break;
            }
        }

        // return
        return [$n, $text];                   // that is a weird return value, regarding $n, which should be 0 ??
    }

    /**
     * Returns a textual representation for a number $n in Greek.
     *
     * @param float $n
     * @returns     string
     */
    public static function number_text($n)
    {
        // dup $n to start getting textual representations
        $new = $n;

        // get all textual representations
        do {
            list($new, $txt) = self::nt_prim($new);
            $text[] = $txt;
        } while ($new > 0);

        // store into one string
        $ret = implode(' ', $text);

        // final grammar corrections
        $ret = strtr($ret, self::$grammar_corrections);

        // remove STR_AND from the end of the string, if there is there
        $length = (2 + strlen(self::STR_AND)) * (-1);

        if (substr($ret, $length) == ' '.self::STR_AND.' ') {
            $ret = substr($ret, 0, $length);
        }

        // return textual representation of $n
        return $ret;
    }

    /**
     * Returns the textual representation of an amount in Greek.
     *
     * @param float $amount
     * @returns     string
     */
    public static function str_greek_amount($amount)
    {
        // explode decimal part
        list($int, $dec) = explode('.', number_format($amount, 2, '.', ''));

        // get textual representation of integer part
        $txt = self::number_text($int).' '.self::STR_EURO;

        // add cents part if there is any
        if ($dec > 0) {
            $txt .= ' '.self::STR_AND.' '
                .self::number_text($dec).' '.self::STR_CENTS;
        }

        // return
        return $txt;
    }
}
