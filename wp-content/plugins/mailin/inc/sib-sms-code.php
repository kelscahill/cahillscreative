<?php
/**
 * Get SMS country prefix code
 *
 * @package SIB_SMS_Code
 */
if ( !class_exists( 'SIB_SMS_Code' ) ) {
    /**
     * Class SIB_SMS_Code
     */
    class SIB_SMS_Code {

        public $smsCode ;

        function __construct()
        {
            $this->smsCode = array(
                'DZ'=>array('name'=>'ALGERIA','code'=>'213'),
                'AD'=>array('name'=>'ANDORRA','code'=>'376'),
                'AR'=>array('name'=>'ARGENTINA','code'=>'54'),
                'AE'=>array('name'=>'UNITED ARAB EMIRATES','code'=>'971'),
                'AT'=>array('name'=>'AUSTRIA','code'=>'43'),
                'AU'=>array('name'=>'AUSTRALIA','code'=>'61'),
                'BA'=>array('name'=>'BOSNIA AND HERZEGOVINA','code'=>'387'),
                'BD'=>array('name'=>'BANGLADESH','code'=>'880'),
                'BE'=>array('name'=>'BELGIUM','code'=>'32'),
                'BG'=>array('name'=>'BULGARIA','code'=>'359'),
                'BH'=>array('name'=>'BAHRAIN','code'=>'973'),
                'BL'=>array('name'=>'SAINT BARTHELEMY','code'=>'590'),
                'BR'=>array('name'=>'BRAZIL','code'=>'55'),
                'CA'=>array('name'=>'CANADA','code'=>'1'),
                'CH'=>array('name'=>'SWITZERLAND','code'=>'41'),
                'CL'=>array('name'=>'CHILE','code'=>'56'),
                'CN'=>array('name'=>'CHINA','code'=>'86'),
                'CO'=>array('name'=>'COLOMBIA','code'=>'57'),
                'CZ'=>array('name'=>'CZECH REPUBLIC','code'=>'420'),
                'DE'=>array('name'=>'GERMANY','code'=>'49'),
                'DK'=>array('name'=>'DENMARK','code'=>'45'),
                'EC'=>array('name'=>'ECUADOR','code'=>'593'),
                'EE'=>array('name'=>'ESTONIA','code'=>'372'),
                'EG'=>array('name'=>'EGYPT','code'=>'20'),
                'ES'=>array('name'=>'SPAIN','code'=>'34'),
                'FI'=>array('name'=>'FINLAND','code'=>'358'),
                'FR'=>array('name'=>'FRANCE','code'=>'33'),
                'GB'=>array('name'=>'UNITED KINGDOM','code'=>'44'),
                'GE'=>array('name'=>'GEORGIA','code'=>'995'),
                'GR'=>array('name'=>'GREECE','code'=>'30'),
                'HK'=>array('name'=>'HONG KONG','code'=>'852'),
                'HR'=>array('name'=>'CROATIA','code'=>'385'),
                'HT'=>array('name'=>'HAITI','code'=>'509'),
                'HU'=>array('name'=>'HUNGARY','code'=>'36'),
                'ID'=>array('name'=>'INDONESIA','code'=>'62'),
                'IE'=>array('name'=>'IRELAND','code'=>'353'),
                'IL'=>array('name'=>'ISRAEL','code'=>'972'),
                'IN'=>array('name'=>'INDIA','code'=>'91'),
                'IR'=>array('name'=>'IRAN','code'=>'98'),
                'IT'=>array('name'=>'ITALY','code'=>'39'),
                'JM'=>array('name'=>'JAMAICA','code'=>'1876'),
                'JO'=>array('name'=>'JORDAN','code'=>'962'),
                'JP'=>array('name'=>'JAPAN','code'=>'81'),
                'KM'=>array('name'=>'COMOROS','code'=>'269'),
                'LB'=>array('name'=>'LEBANON','code'=>'961'),
                'LK'=>array('name'=>'SRI LANKA','code'=>'94'),
                'LT'=>array('name'=>'LITHUANIA','code'=>'370'),
                'LU'=>array('name'=>'LUXEMBOURG','code'=>'352'),
                'LV'=>array('name'=>'LATVIA','code'=>'371'),
                'MA'=>array('name'=>'MOROCCO','code'=>'212'),
                'MG'=>array('name'=>'MADAGASCAR','code'=>'261'),
                'MT'=>array('name'=>'MALTA','code'=>'356'),
                'MU'=>array('name'=>'MAURITIUS','code'=>'230'),
                'MX'=>array('name'=>'MEXICO','code'=>'52'),
                'MY'=>array('name'=>'MALAYSIA','code'=>'60'),
                'NC'=>array('name'=>'NEW CALEDONIA','code'=>'687'),
                'NG'=>array('name'=>'NIGERIA','code'=>'234'),
                'NI'=>array('name'=>'NICARAGUA','code'=>'505'),
                'NL'=>array('name'=>'NETHERLANDS','code'=>'31'),
                'NO'=>array('name'=>'NORWAY','code'=>'47'),
                'NP'=>array('name'=>'NEPAL','code'=>'977'),
                'NZ'=>array('name'=>'NEW ZEALAND','code'=>'64'),
                'PA'=>array('name'=>'PANAMA','code'=>'507'),
                'PE'=>array('name'=>'PERU','code'=>'51'),
                'PF'=>array('name'=>'FRENCH POLYNESIA','code'=>'689'),
                'PG'=>array('name'=>'PAPUA NEW GUINEA','code'=>'675'),
                'PH'=>array('name'=>'PHILIPPINES','code'=>'63'),
                'PK'=>array('name'=>'PAKISTAN','code'=>'92'),
                'PL'=>array('name'=>'POLAND','code'=>'48'),
                'PM'=>array('name'=>'SAINT PIERRE AND MIQUELON','code'=>'508'),
                'PR'=>array('name'=>'PUERTO RICO','code'=>'1787'),
                'PT'=>array('name'=>'PORTUGAL','code'=>'351'),
                'PY'=>array('name'=>'PARAGUAY','code'=>'595'),
                'QA'=>array('name'=>'QATAR','code'=>'974'),
                'RO'=>array('name'=>'ROMANIA','code'=>'40'),
                'RU'=>array('name'=>'RUSSIA','code'=>'7'),
                'SE'=>array('name'=>'SWEDEN','code'=>'46'),
                'SG'=>array('name'=>'SINGAPORE','code'=>'65'),
                'SI'=>array('name'=>'SLOVENIA','code'=>'386'),
                'SK'=>array('name'=>'SLOVAKIA','code'=>'421'),
                'TH'=>array('name'=>'THAILAND','code'=>'66'),
                'TN'=>array('name'=>'TUNISIA','code'=>'216'),
                'TR'=>array('name'=>'TURKEY','code'=>'90'),
                'TW'=>array('name'=>'TAIWAN','code'=>'886'),
                'UA'=>array('name'=>'UKRAINE','code'=>'380'),
                'UG'=>array('name'=>'UGANDA','code'=>'256'),
                'US'=>array('name'=>'UNITED STATES','code'=>'1'),
                'UY'=>array('name'=>'URUGUAY','code'=>'598'),
                'VE'=>array('name'=>'VENEZUELA','code'=>'58'),
                'VN'=>array('name'=>'VIET NAM','code'=>'84'),
                'WF'=>array('name'=>'WALLIS AND FUTUNA','code'=>'681'),
                'YT'=>array('name'=>'MAYOTTE','code'=>'262'),
                'ZA'=>array('name'=>'SOUTH AFRICA','code'=>'27'),
            );
        }

        /**
         * Get sms code lists.
         * @return array
         */
        public function get_sms_code_list(){
            return $this->smsCode;
        }

        /**
         * Get country code by dial code.
         * @return string
         */
        public function get_country_code_from_dial_code($dialCode) {
            if(empty($dialCode)) {
                return false;
            }

            foreach ($this->smsCode as $countryCode => $array) {
                if ($array['code'] == $dialCode) {
                    return $countryCode;
                }
            }
            return false;
        }
    }
}
