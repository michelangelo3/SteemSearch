<?php

class Sds {
  public static $cSdsUrl = 'https://sds.steemworld.org/';
  public static $lAbfrageOk = true;
  public static $nSteemPerShare = 0.00055141; // wird in GetSteemPerShare() belegt
  private static $lPerShareOk = false; // true wenn $nSteemPerShare korrekt belegt ist
 
  // *********************************************************************************
  // Vests in SP umrechnen
  // *********************************************************************************
  public static function VestsToSp($nVests,$nDecimals = 0) {
    if(!self::$lPerShareOk) {
      self::GetSteemPerShare();
    }
    return round($nVests * self::$nSteemPerShare, $nDecimals);
  }
  // *********************************************************************************
  // self::$nSteemPerShare korrekt belegen
  // *********************************************************************************
  private static function GetSteemPerShare() {
    $aResult = self::GetUrlArray('steem_requests_api/getSteemProps');
    if(isset($aResult['result']['steem_per_share'])) {
      self::$nSteemPerShare = floatval($aResult['result']['steem_per_share']);
      self::$lPerShareOk = true;
    }
  }
  
  // *********************************************************************************
  // Letzer RootPost --> cLinuxTimeStamp oder ''
  // *********************************************************************************
  public static function GetLastRootPost($cAcc) {
  	return self::AccountsApi($cAcc, 'last_root_post');
  }
  
  // *********************************************************************************
  // Letzer RootPost --> cLinuxTimeStamp oder ''
  // *********************************************************************************
  private static function AccountsApi($cAcc,$cFeld) {
    $cRet = '';

    $aResult = self::GetUrlArray('accounts_api/getAccount/'.$cAcc.'/'.$cFeld);
    if(isset($aResult['result'][$cFeld])) {
      $cRet = $aResult['result'][$cFeld];
    }
    return $cRet;
  }

  // *********************************************************************************
  // Prüfen ob ein Account vorhanden ist => lVorhanden
  // *********************************************************************************
  public static function AccVorhanden($cAccount) {
    $lRet = false;
    $cUrl = 'accounts_api/getAccount/'.$cAccount.'/name';

    $aResult = self::GetUrlArray($cUrl,false);
    $lRet = (isset($aResult['code']) and $aResult['code'] == 0 and isset($aResult['result']['name']));

    return $lRet;
  }

  // *********************************************************************************
  // Inhalt von cUrl auslesen --> Json-Array vom Inhalt
  // *********************************************************************************
  public static function GetUrlArray($cUrl,$lPrintErr = true) {
	  $cResult = file_get_contents(self::$cSdsUrl.$cUrl);
	  $aResult = json_decode($cResult, true);
	  
	  self::$lAbfrageOk = (isset($aResult['code']) and $aResult['code'] == 0);
	  if(!self::$lAbfrageOk and $lPrintErr) {
      // Fehler bei Abfrage ausgeben
      if (isset($aResult['code'])) {
        print ' | Error: ';
        print_r($aResult['error']);
        print '<br>';
      } else {
        print 'SDS not accessible';
      }
	  }
	  return $aResult;
  }
  
} // class Sds
