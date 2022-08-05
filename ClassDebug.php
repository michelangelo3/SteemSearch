<?php

// *********************************************************************************
// Zur Ausgabe von Debug-Infos
// *********************************************************************************
class Debug {

  public static $lDebugMode = false; // .t. falls cAcc=test123 oder $_GET['Debug'] übergeben
  public static $cDebugTxt = ''; // 

  // *********************************************************************************
  // $lDebugMode auf true wenn $_GET['Debug'] übergeben wurde
  // *********************************************************************************
  public static function Init() {
    self::$lDebugMode = isset($_GET['Debug']);
  }

  // *********************************************************************************
  // Text hinzufügen
  // *********************************************************************************
  public static function AddDebugTxt($cTxt,$lMitBr = true) {
    self::$cDebugTxt .= $cTxt;
    if($lMitBr) {
      self::$cDebugTxt .= '<br>';
    }
  }
  
  // *********************************************************************************
  // Debugtext ausgeben.
  // *********************************************************************************
  public static function PrintDebugTxt() {
    if (self::$lDebugMode and !empty(self::$cDebugTxt)) {
      print('<hr>Debug: ');
      print(self::$cDebugTxt);
      print('<hr>');
    }
  }
  
} // Class Debug
