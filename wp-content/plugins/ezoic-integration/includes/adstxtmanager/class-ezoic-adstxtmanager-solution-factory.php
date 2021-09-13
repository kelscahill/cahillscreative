<?php

namespace Ezoic_Namespace;


interface iAdsTxtManager_Solution {
    public function SetupSolution();
    public function TearDownSolution();
}


/**
 * Class Ezoic_AdsTxtManager_Solution_Factory
 * @package Ezoic_Namespace
 */
class Ezoic_AdsTxtManager_Solution_Factory {

    public function GetBestSolution() {

        $emptyModifier = new Ezoic_AdsTxtManager_Empty_Solution();
        //Do we have a ads txt manager id?
        $adstxtmanager_id = Ezoic_AdsTxtManager::ezoic_adstxtmanager_id(true);
        if( !is_int($adstxtmanager_id ) || $adstxtmanager_id == 0 ) {
            //we don't have an adstxtmanager_id, lets return the empty solution
            return $emptyModifier;
        }

        //If we have apache, lets modify the sites htaccess file
        if( strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache') !== false ) {
            //return htaccess solution
            $htaccessModifier = new Ezoic_AdstxtManager_HTACCESS_Modifier();
            return $htaccessModifier;
        } else {
            //return file modification solution
            $fileModifier = new Ezoic_AdsTxtManager_File_Modifier();
            return $fileModifier;
        }
    }
}
