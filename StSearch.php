<?php
// *********************************************************************************
// Suchfunktion für Steem
// *********************************************************************************

// ************************************
// Includes und Requires
// ************************************
require_once 'ClassSds.php';
require_once 'ClassDebug.php';

// *********************************************************************************
// Klasse für Form1 (Haupteingabemaske)
// *********************************************************************************
class Form1 {

  public static $cSuchText = '';
  public static $lTitleOnly = false; // Suche nur im Titel
  public static $lCommentsOnly = false; // Suche nur in den Kommentaren
  public static $lExact = false; // Suchbegiffe werden in "" gestellt
  public static $cSearchIn = 'Post and Title';

  // *********************************************************************************
  // Per Get übergebene Variablen belegen
  // *********************************************************************************
  public static function Init() {
  	self::$lExact = isset($_GET['exact']);

    if (isset($_GET['SuchText'])) {
      self::$cSuchText = $_GET['SuchText'];
    }
    
    if (isset($_GET['SearchIn'])) {
      self::$cSearchIn = $_GET['SearchIn'];
      self::$lTitleOnly = (self::$cSearchIn == 'Title');
      self::$lCommentsOnly = (self::$cSearchIn == 'Comments');
    }
    
  }

  // *********************************************************************************
  // Form ausgeben
  // *********************************************************************************
  public static function PrintForm1() {
    // Daten eines gesendeten Formulars in die Eingabefelder übernehmen
    $cValueSuchText = '';
    if (!empty(self::$cSuchText)) {
      $cValueSuchText = ' value="' . self::$cSuchText . '"';
    }
    
    $cExact = '';
    if (self::$lExact) {
      $cExact = ' checked';
    }

    print('<form id="SuchForm" role="form" action="' . get_permalink() . '" method="get">');
    print('<label for="SuchText"></label>');
    print('<input type="text" name="SuchText" size="40" placeholder="Search for"' . $cValueSuchText . '>');

    print('<div style="margin-top:8px;"><label for="SearchIn">Search in: </label>');
    print('<select name="SearchIn">');
    self::PrintOptionTag('Post and Title');
    self::PrintOptionTag('Title');
    self::PrintOptionTag('Comments');
    print('</select>');
    print('</div>');

    print('<input type="checkbox" id="exact" name="exact"' . $cExact . '>');
    print('<label for="exact"> exact match</label>');

    print('<p></p>');

    print('<button type="submit" class="btn-green">Search</button>');
    print('</form>');

    if (empty($cValueSuchText)) {
      print('<br><p id="SuchHinweis"></p>');
    } else {
      print('<br><p id="SuchHinweis">' . SuchHinweisTags() . '</p>');
    }
  }

  private static function PrintOptionTag($cOptionName) {
  	$cSelected = (self::$cSearchIn == $cOptionName) ? ' selected' : '';
  	print('<option' . $cSelected . '>' . $cOptionName . '</option>');
  }
  
} // Class Form1

// Bei direktem Aufruf außerhalb Wordpress
if(!function_exists('get_permalink')) {
  function get_permalink() {
    return 'StSearch.php';
  }
}

// Such-GIF und Text anzeigen, wird vom JavaScript ganz unten ausgeblendet
function SuchHinweisTags() {
  $cSuchHinweis = "Searching... <img src=https://steem.uber.space/wp-content/uploads/2021/05/loader.gif> <small>may take a few minutes</small>";
  return $cSuchHinweis;
}

// *****************************************************************
// Hier geht's los...
// *****************************************************************

// Globale Defines
define("MARK_BEG", '<span style="background:#ffff9d">'); // Farbdefinition für die Markierung eines gefundenen Suchtextes
define("MARK_END", '</span>');

Debug::Init();
// Debug::$lDebugMode = true;

Form1::Init();
Form1::PrintForm1();

if (!empty(Form1::$cSuchText)) {
  // Suche starten
  SuchTxt();
  print '<br>';
}

Debug::PrintDebugTxt();

// *********************************************************************************
// Suche
// *********************************************************************************
function SuchTxt() {
  $cMaybeTxt = ''; // Titelzeilen von Posts die nicht exakt übereinstimmen

  $cSearchIn = 'any';
  if (Form1::$lTitleOnly) {
    $cSearchIn = 'title';
  }
  $cGetBy = 'getPostsByText';
  if (Form1::$lCommentsOnly) {
    $cGetBy = 'getCommentsByText';
  }

  // BodyLenght 0, Limit 30, urlencode notwendig wg. Umlaute
  $cSearchTxt = urlencode(Form1::$cSuchText);
  $cSearchTxt = str_replace('+', ' ', $cSearchTxt); // + mit Leerzeichen ersetzen
  if(Form1::$lExact) {
    $cSearchTxt = '"'.$cSearchTxt.'"';
  }
    
  $cUrl = 'content_search_api/'.$cGetBy.'/' . $cSearchTxt . '/' . $cSearchIn . '/null/0/time/DESC/30';
  $aResult = Sds::GetUrlArray($cUrl);
  Debug::AddDebugTxt($cUrl);
  
  // Formatierung DIV-Container für einen gefundenen Beitrag
  $cBoxFormat = '<div style="border: 1px solid #dddddd;padding:10px;margin-bottom:10px;border-radius:4px;background-color: #ffffff;">';

  if (Sds::$lAbfrageOk) {
    if (isset($aResult['result']['cols']['author'])) {
      // Positionen für $aRow in der Foreach-Schleife merken
      $nPosAuthor = $aResult['result']['cols']['author'];
      $nPosTitle = $aResult['result']['cols']['title'];
      $nPosBody = $aResult['result']['cols']['body'];
      $nPosPermlink = $aResult['result']['cols']['permlink'];
      $nPosCreated = $aResult['result']['cols']['created'];

      foreach ($aResult['result']['rows'] as $nRow => $aRow) {
        $cAutor = $aRow[$nPosAuthor];
        $cPermLink = $aRow[$nPosPermlink];
        $cPostTitle = $aRow[$nPosTitle];

        $lImTitel = false;
        if(Form1::$lCommentsOnly) {
          // Bei Kommenaren ist $cPostTitle leer
          $cPostTitle = 'Comment';
        } else {
          // Im Titel suchen
          $nPosFound = stripos($cPostTitle, Form1::$cSuchText);
          if ($nPosFound !== false) {
            // Suchtext im Titel gefunden
            $lImTitel = true;
            $cPostTitle = MarkFoundText($cPostTitle, $nPosFound, strlen(Form1::$cSuchText));
            print($cBoxFormat);
            print TitelZeile($cPostTitle, $aRow[$nPosCreated], $cAutor, $cPermLink);
          }
        }

        // $cBody = htmlspecialchars($aRow[$nPosBody]) wäre der Text aus der Suchfunktion,
        // dieser ist aber bei längeren Posts nicht komplett.
        // Daher den kompletten Text des Posts/Kommentars holen
        $aResultPost = Sds::GetUrlArray('posts_api/getPost/' . $cAutor . '/' . $cPermLink . '/false/body');
        $cBody = htmlspecialchars($aResultPost['result']['body']);

        $nPosFound = stripos($cBody, Form1::$cSuchText);
        if ($nPosFound !== false) {
          if (!$lImTitel) {
            print($cBoxFormat);
            print TitelZeile($cPostTitle, $aRow[$nPosCreated], $cAutor, $cPermLink);
          }

          $nBodyLen = 200;
          $nVorlauf = 30;
          $nPosFound -= $nVorlauf;
          $nPosFound = max($nPosFound, 0); // Sicherstellen, dass $nPosFound >= 0
          $cBody = substr($cBody, $nPosFound, $nBodyLen); // Der Body-Text ab Beginn der Fundstelle minus 20 Zeichen
          if ($nPosFound > 0) {
            $nBeginn = 0;
            while ($nBeginn <= $nVorlauf) {
              if (preg_match("/[A-Za-z]/", substr($cBody, $nBeginn, 1))) {
                // Buchstabe, ein Zeichen weiter
                $nBeginn++;
              } else {
                // Leerzeichen o.Ä.
                $cBody = substr($cBody, $nBeginn); // Body-Text mit ganzem Wort am Beginn
                break;
              }
            }
          }

          $nPosFound = stripos($cBody, Form1::$cSuchText); // neu suchen, das sich $cBody evtl. geändert hat
          $cBody = MarkFoundText($cBody, $nPosFound, strlen(Form1::$cSuchText));
          if ($nPosFound > 0) {
            print('...');
          }
          // print '<small>'.strip_tags($cBody).'...</small><br><br>';
          print '<small>' . $cBody . '...</small>';
          print ('</div>');
        } else {
          if ($lImTitel) {
            // Zeilenumbruch wenn im Titel gefunden
            print('</div>');
          } else {
            $cMaybeTxt .= $cBoxFormat.TitelZeile($cPostTitle, $aRow[$nPosCreated], $cAutor, $cPermLink) . '</div>';
          }
        }
      }

      if (!empty($cMaybeTxt)) {
        print('<hr>');
        print('Maybe relevant:<br>');
        print($cMaybeTxt);
      }
    } else {
      print 'Nothing found!<br>';
    }
  }
}

// Gefundene Textstelle markieren
function MarkFoundText($cText, $nPosFound, $nLenFound) {
  // Original Text der gefunden wurde verwenden, damit groß/klein im markierten Text nicht vertauscht wird
  $cOrgSuchTxt = substr($cText, $nPosFound, $nLenFound);
  return str_ireplace($cOrgSuchTxt, MARK_BEG . $cOrgSuchTxt . MARK_END, $cText);
}

// Titelzeile der Fundstelle ausgeben
function TitelZeile($cPostTitle, $tCreated, $cAutor, $cPermLink) {
  $cRet = date("d.m.Y", $tCreated);
  $cRet .= ' <a href="https://steemit.com/@' . $cAutor . '/' . $cPermLink . '" target="_blank" rel="noopener">' . $cPostTitle . '</a>';
  $cRet .= ' by @' . $cAutor;
  $cRet .= '<br>';
  return $cRet;
}
?>

<script>
  jQuery(document).ready(function ($) {

    // Wenn Dokument geladen, evtl. Hinweis "Searching..." ausblenden
    $('#SuchHinweis').html('');

    // Wenn Formular abgesendet, Hinweis "Searching..." anzeigen
    $('#SuchForm').submit(function (e) {
      $('#SuchHinweis').html('<?php print(SuchHinweisTags()); ?>');
    });

  });

</script>
