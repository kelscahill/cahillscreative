<?php

class SendinblueAccount
{
    private static $sendinblueAccountObj = null;
    private $sendinblueAccountData;
    private $lastResponseCode;

    /**
     * SendinblueAccount private constructor.
     */
    private function __construct()
    {

    }

    /**
     * Getter function for account data
     */
    public function getSendinblueAccountData()
    {
        return $this->sendinblueAccountData;
    }

    /**
     * Setter function for account data
     */
    public function setSendinblueAccountData($sendinblueAccountData)
    {
        $this->sendinblueAccountData = $sendinblueAccountData;

        // update Marketing Automation API key.
        if ( isset( $sendinblueAccountData['marketingAutomation']['enabled'] ) && true == $sendinblueAccountData['marketingAutomation']['enabled'] ) {
            $ma_key = $sendinblueAccountData['marketingAutomation']['key'];
        } else {
            $ma_key = '';
        }
        $general_settings = get_option( SIB_Manager::MAIN_OPTION_NAME, array() );
        $general_settings['ma_key'] = $ma_key;
        update_option( SIB_Manager::MAIN_OPTION_NAME, $general_settings );
    }

    /**
     * Getter function for last response code
     */
    public function getLastResponseCode()
    {
        return $this->lastResponseCode;
    }

    /**
     * Setter function for last response code
     */
    public function setLastResponseCode($lastResponseCode)
    {
        $this->lastResponseCode = $lastResponseCode;
    }

    /**
     * Static function to create a new instance or return an existing instance.
     */
    public static function getInstance()
    {
        if( null == self::$sendinblueAccountObj )
        {
            self::$sendinblueAccountObj = new SendinblueAccount();
        }  
        
        return self::$sendinblueAccountObj;
    }
}
