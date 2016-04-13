<?php

class EmojiRecognizer
{

    private static function isEmojiChar($intvalue)
    {
        return (
           ($intvalue >= 0x1F300 && $intvalue <= 0x1F5FF ) //supplemental symbols and pictographs
        || ($intvalue >= 0x1F600 && $intvalue <= 0x1F64F) //emoticons
        || ($intvalue >= 0x1F680 && $intvalue <= 0x1F6FF) //transport map symbols
        || ($intvalue >= 0x2600  && $intvalue <= 0x26FF ) // misc symbols
        || ($intvalue >= 0x2700  && $intvalue <= 0x27BF ) //dingbats
        || ($intvalue >= 0xE000  && $intvalue <= 0xF8FF ) //private use area??
        );
    }

    private static function isNonSpacingMark($intvalue)
    {
         return (
           ($intvalue >= 0x0300 && $intvalue <= 0x036F )
        || ($intvalue >= 0x1AB0 && $intvalue <= 0x1ABD )
        || ($intvalue >= 0x1DC0 && $intvalue <= 0x1DFF )
        || ($intvalue >= 0x20D0 && $intvalue <= 0x20F0 )
        || ($intvalue >= 0x2CEF && $intvalue <= 0x2CF1 )
        || ($intvalue >= 0xFE20 && $intvalue <= 0xFE2F )
        || ($intvalue >= 0x1DA01 && $intvalue <= 0x1DA6C )  // signwriting?
        || ($intvalue == 0x1DA75) // signwriting?
        || ($intvalue == 0x1DA84) // signwriting?
        || ($intvalue >= 0x1DA9B && $intvalue <= 0x1DAAF )  // signwriting?
         );
    }

    private static function isModifier($intvalue)
    {
        return (
           ($intvalue == 0x005E)
        || ($intvalue == 0x0060)
        || ($intvalue == 0x00A8)
        || ($intvalue == 0x00AF)
        || ($intvalue >= 0x02C2 && $intvalue <= 0x02C5)
        || ($intvalue >= 0x02D2 && $intvalue <= 0x02DF)
        || ($intvalue >= 0x02E5 && $intvalue <= 0x02FF)
        || ($intvalue >= 0x1FBD && $intvalue <= 0x1FC1)
        || ($intvalue >= 0x1FCD && $intvalue <= 0x1FCF)
        || ($intvalue >= 0x1FDD && $intvalue <= 0x1FDF)
        || ($intvalue >= 0x1FED && $intvalue <= 0x1FEF)
        || ($intvalue >= 0x1FFD && $intvalue <= 0x1FFE)
        || ($intvalue >= 0x309B && $intvalue <= 0x309C)
        || ($intvalue >= 0xA700 && $intvalue <= 0xA721)
        || ($intvalue >= 0xA789 && $intvalue <= 0xA78A)
        || ($intvalue == 0xAB5B)
        || ($intvalue >= 0xFBB2 && $intvalue <= 0xFBC1)
        || ($intvalue == 0xFF3E)
        || ($intvalue == 0xFF40)
        || ($intvalue == 0xFFE3)
        || ($intvalue >= 0x1F3FB && $intvalue <= 0x1F3FF)
        );
    }

    private static function isVariationSelector($intvalue)
    {
        return (
           ($intvalue >= 0xFE00  && $intvalue <= 0xFE0F  ) // variation selectors 1-16
        || ($intvalue >= 0xE0100 && $intvalue <= 0xE01EF ) // variation selectors 17-256
        );
    }

    private static function isFlagChar($intvalue)
    {
         return ($intvalue >= 0x1F1E6  && $intvalue <= 0x1F1FF);
    }

    private static function isZWJ($intvalue)
    {
        return ($intvalue == 0x200D);
    }

// much of this is based on what i could understand from
// http://www.unicode.org/reports/tr51/index.html#def_emoji_modifier_base

    private static function parseEmojiCoreSequence($input, $position)
    {
        //remove leading char
        if (self::isEmojiChar($input[$position]) ) {
            $position++;
            if (count($input) == $position) {
                return true;
            }
            //modifiers and variation selectors come after characters
            if (self::isVariationSelector($input[$position])) {
                $position++;
                if (count($input) == $position) {
                    return true;
                }
            }
            while (self::isNonSpacingMark($input[$position])) {
                $position++;
                if (count($input) == $position) {
                    return true;
                }
            }
            if (self::isModifier($input[$position])) {
                $position++;
                if (count($input) == $position) {
                    return true;
                }
            }
        } elseif ( self::isFlagChar($input[$position]) && self::isFlagChar($input[$position + 1])) {
            $position = $position + 2;
        }

        if (count($input) == $position ) {
            return true;
        }

        if ( self::isZWJ($input[$position])) {
            $position++;
            return self::parseEmojiCoreSequence($input, $position);
        }

        return false;

    }

    private static function parseEmojiSequence($input)
    {
        if (empty($input)) {
            return false;
        }
        if (!is_array($input)) {
            return self::isEmojiChar($input);
        }
        $position = 0;

        //allowed to start with one of these types of characters i think
        //if so, we skip it
        if (self::isModifier($input[$position]) || self::isVariationSelector($input[$position]) || self::isZWJ($input[$position]) ) {
            $position++;
        }

        if (self::isEmojiChar($input[$position])  || self::isFlagChar($input[$position])) {
            return self::parseEmojiCoreSequence($input, $position);
        }
        return false;
    }


    public static function isSingleEmoji($text)
    {
        $text = trim($text);

        if(self::isSingleEmojiByURLEncode($text)) {
            return true;
        }
        //disabling this as the test following this one is actually working better
        //if(self::isSingleEmojiHTML(htmlentities($text))) {
            //return true;
        //}
        if(self::isSingleEmojiByURLEncode(html_entity_decode($text))) {
            return true;
        }

        return false;

    }

    public static function isSingleEmojiHTML($text)
    {
        $text = trim($text);
        //includes amp; un case of double encoding... why not
        preg_match_all('/^(\&(amp;)*\#\d+;)+$/', $text, $matches);

        if (empty($matches) || !isset($matches[0]) || !isset($matches[0][0])) {
        //preg_match_all('/^(\&(amp;)?x[\da-fA-F]+;)+$/', $text, $matches);
            return false;
        }

        $matched = ($matches[0][0]);
        $a = preg_match_all('/\&(amp;)*\#(\d+);/', $matched, $matches);

        $integer_equivs = array();
        foreach ($matches[2] as $str) {
            $integer_equivs[] = intval($str);
        }

        return self::parseEmojiSequence($integer_equivs);
    }

    public static function isSingleEmojiByUrlEncode($text)
    {
        $text = urlencode($text);
        $integer_equivs = self::urlEncToIntArray($text);

        return self::parseEmojiSequence($integer_equivs);
    }

    public static function urlEncToIntArray($urlencodedString)
    {
        $urlencstr = trim($urlencodedString);
        //make sure our string is only utf8 encoded data otherwise we are done
        if(! preg_match('/^(%[0-9A-F][0-9A-F])+$/i', $urlencstr)){
            return array();
        }

        $result = array();

        preg_match_all('/%([0-9A-F][0-9A-F])/i', $urlencstr, $matches);
        if(!empty($matches[1])) {
            for($i = 0; $i < count($matches[1]); $i++) {
                $intval = hexdec($matches[1][$i]);
                if($intval >= 0xF0) { 
                    //4 bytes
                    //todo: test length of remaining string
                    $result[] = 
                        ($intval & 7) * pow(2,18) + 
                        (hexdec($matches[1][$i+1]) & 63) * pow(2,12) +
                        (hexdec($matches[1][$i+2]) & 63) * pow(2,6) +
                        (hexdec($matches[1][$i+3]) & 63) ;
                    $i = $i+3;
                } elseif($intval >= 0xE0) { 
                    //3 bytes
                    //todo: test length of remaining string
                    $result[] = 
                        ($intval & 15) * pow(2,12) + 
                        (hexdec($matches[1][$i+1]) & 63) * pow(2,6) +
                        (hexdec($matches[1][$i+2]) & 63);
                    $i = $i+2;
                } elseif($intval >= 0xC0) { 
                    //2 bytes
                    //todo: test length of remaining string
                    $result[] = 
                        ($intval & 31) * pow(2,6) + 
                        (hexdec($matches[1][$i+1]) & 63);
                    $i = $i+1;
                } elseif($intval < 0x80) { 
                    //1 byte
                    $result[] = $intval;

                } else { 
                    // not the start of a utf-8 char ??
                    return array();
                }
            }
        }
        

        return $result;
    }


}




