<?php
    /**
     * Translate a number to a short alhanumeric version:
     *
     * Translated any number up to 9007199254740992
     * to a shorter version in letters e.g.:
     * 9007199254740989 --> PpQXn7COf
     *
     * specifiying the second argument true, it will
     * translate back e.g.:
     * PpQXn7COf --> 9007199254740989
     *
     * this function is based on any2dec && dec2any by
     * fragmer[at]mail[dot]ru
     * see: http://nl3.php.net/manual/en/function.base-convert.php#52450
     *
     * If you want the alphaID to be at least 3 letter long, use the
     * $pad_up = 3 argument
     *
     * In most cases this is better than totally random ID generators
     * because this can easily avoid duplicate ID's.
     * For example if you correlate the alpha ID to an auto incrementing ID
     * in your database, you're done.
     *
     * The reverse is done because it makes it slightly more cryptic,
     * but it also makes it easier to spread lots of IDs in different
     * directories on your filesystem. Example:
     * $part1 = substr($alpha_id,0,1);
     * $part2 = substr($alpha_id,1,1);
     * $part3 = substr($alpha_id,2,strlen($alpha_id));
     * $destindir = "/".$part1."/".$part2."/".$part3;
     * // by reversing, directories are more evenly spread out. The
     * // first 26 directories already occupy 26 main levels
     *
     * more info on limitation:
     * - http://blade.nagaokaut.ac.jp/cgi-bin/scat.rb/ruby/ruby-talk/165372
     *
     * if you really need this for bigger numbers you probably have to look
     * at things like: http://theserverpages.com/php/manual/en/ref.bc.php
     * or: http://theserverpages.com/php/manual/en/ref.gmp.php
     * but I haven't really dugg into this. If you have more info on those
     * matters feel free to leave a comment.
     * 
     * @param mixed   $in     String or long input to translate     
     * @param boolean $toNum  Reverses translation when true
     * @param mixed   $pad_up Number or boolean padds the result up to a specified length
     * 
     * @return unknown
     */
    function alphaID($in, $toNum=false, $pad_up=false){

        $index = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $base = strlen($index);

        if ($toNum){
            // digital number  <<--  alphabet letter code
            $in = strrev($in);
            $out = 0;
            $len = strlen( $in ) - 1;
            for ( $t = 0; $t <= $len; $t++ ) {
                $out = $out + strpos( $index, substr( $in, $t, 1 ) ) * bcpow ( $base, $len - $t );
            }

            if (is_numeric($pad_up)){
                $pad_up--;
                if($pad_up > 0){
                    $out -= pow($base, $pad_up);
                }
            }
        } else { 
            // digital number  -->>  alphabet letter code
            if (is_numeric($pad_up)){
                $pad_up--;
                if($pad_up > 0){
                    $in += pow($base, $pad_up);
                }
            }

            $out = "";
            for ( $t = floor( log10( $in ) / log10( $base ) ); $t >= 0; $t-- ) {
                $a = floor( $in / bcpow ( $base, $t ) );
                $out = $out . substr( $index, $a, 1 );
                $in = $in - ( $a * bcpow ( $base, $t ) );
            }
            $out = strrev($out); // reverse
        }

        return $out;
    }
?>