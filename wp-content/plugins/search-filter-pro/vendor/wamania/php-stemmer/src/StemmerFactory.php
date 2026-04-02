<?php

namespace Search_Filter_Pro\Vendor\Wamania\Snowball;

use Search_Filter_Pro\Vendor\Joomla\String\StringHelper;
use Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\Catalan;
use Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\Danish;
use Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\Dutch;
use Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\English;
use Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\Finnish;
use Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\French;
use Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\German;
use Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\Italian;
use Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\Norwegian;
use Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\Portuguese;
use Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\Romanian;
use Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\Russian;
use Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\Spanish;
use Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\Stemmer;
use Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\Swedish;
class StemmerFactory
{
    const LANGS = [Catalan::class => ['ca', 'cat', 'catalan'], Danish::class => ['da', 'dan', 'danish'], Dutch::class => ['nl', 'dut', 'nld', 'dutch'], English::class => ['en', 'eng', 'english'], Finnish::class => ['fi', 'fin', 'finnish'], French::class => ['fr', 'fre', 'fra', 'french'], German::class => ['de', 'deu', 'ger', 'german'], Italian::class => ['it', 'ita', 'italian'], Norwegian::class => ['no', 'nor', 'norwegian'], Portuguese::class => ['pt', 'por', 'portuguese'], Romanian::class => ['ro', 'rum', 'ron', 'romanian'], Russian::class => ['ru', 'rus', 'russian'], Spanish::class => ['es', 'spa', 'spanish'], Swedish::class => ['sv', 'swe', 'swedish']];
    /**
     * @throws NotFoundException
     */
    public static function create(string $code): Stemmer
    {
        $code = StringHelper::strtolower($code);
        foreach (self::LANGS as $classname => $isoCodes) {
            if (in_array($code, $isoCodes)) {
                return new $classname();
            }
        }
        throw new NotFoundException(sprintf('Stemmer not found for %s', $code));
    }
}
