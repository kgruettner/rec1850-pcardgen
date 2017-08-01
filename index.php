<?php

/**
 * Konfiguration 
 * 
 * Das Skript bitte in UTF-8 abspeichern (ohne BOM).
 */

// Welche Adresse soll als Absender angegeben werden?
// (Manche Hoster lassen diese Angabe vor dem Versenden der Mail ueberschreiben)
$absenderadresse = 'sekretaer@rotary-eclub1850.de';

// Welcher Absendername soll verwendet werden?
$absendername = 'Rotary E-Club of D-1850';

// Welchen Betreff-Prefix sollen die Mails erhalten?
$betreff = '[Rotary E-Club of D-1850]';

// Einstellungen zum Mailserver (hier in einer SMTP Konfiguration)
$mailServerName = 'smtp.mailserver.com'; 
$mailServerPort = 465;
$mailServerConnectionType = 'ssl';
$mailServerUsername = 'username';
$mailServerPassword = 'passwort';

/**
 * Ende Konfiguration
 */

require_once('FPDF/fpdf.php');
require_once('FPDI/fpdi.php');
require_once "Swift-5.1.0/lib/swift_required.php"; // Swift initialisieren
 
if ($_SERVER['REQUEST_METHOD'] === "POST") {
 
  /**
   * Generiere Präsenzkarte als pdf
   */
 
  // initiate FPDI
  $pdf = new FPDI();
  // add a page
  $pdf->AddPage();
  // set the source file
  $pdf->setSourceFile("template/RECofD1850_praesenzkarte_template.pdf");
  // import page 1
  $tplIdx = $pdf->importPage(1);
  // use the imported page and put it as "background" image
  $pdf->useTemplate($tplIdx, null, null, 0, 0, true);

  $pdftext_intro = "Hiermit bestätigen wir den Besuch von";
  $pdftext_meeting = "bei unserem Meeting am";

  $versender = 'Versender';
  $email = 'E-Mail';
  $vorname = 'Vorname';
  $nachname = 'Nachname';
  $email_empfaenger = "kim.gruettner@rotary-eclub1850.de";
  $email_sekretaer = "";
  $clubname = 'Rotary Club xyz';
  $datum = 'DD.MM.YYYY';
  $bemerkungen = 'Bemerkungen';

  foreach ($_POST as $name => $wert) {
    if($name == "Versender") $versender = $wert;
	if($name == "E-Mail") $email = $wert;
	if($name == "Vorname") $vorname = $wert;
    if($name == "Nachname") $nachname = $wert;
	if($name == "E-Mail-Empfaenger") $email_empfaenger = $wert;
	if($name == "E-Mail-Sekretaer") $email_sekretaer = $wert;
    if($name == "Clubname") $clubname = $wert;
    if($name == "Datum") $datum = $wert;
	if($name == "Bemerkungen") $bemerkungen = $wert;
  }
  
  $zieladresse = $email_empfaenger;

  $betreff .= " Präsenzbestätigung für ".$vorname." ".$nachname." | Meeting am ".$datum;

  $pdf_file_name = "praesenzkarten/"."RECofD1850_Praesenzkarte_".$vorname."_".$nachname."_".$datum.".pdf";
  $pdftext = $pdftext_intro."\n".$vorname." ".$nachname."\n";
  if ($clubname != "Rotary Club xyz") $pdftext .= "(".$clubname.")\n";
  $pdftext .=  $pdftext_meeting."\n".$datum;

  // set pdf properties
  $pdf->SetTitle('Rotary E-Club of D-1850: Präsenzbestätigung', true);
  $pdfsubject_text = 'Präsenz für '.$vorname." ".$nachname." (".$datum.")";
  $pdf->SetSubject($pdfsubject_text, true);
  $pdf->SetCreator('REC of D-1850 Präsenzkartengenerator', true);
  $pdf->SetAuthor('Kim Grüttner', true);

  // now write some text above the imported page
  $pdf->SetFont('Arial','B',20);
  $pdf->SetTextColor(0, 0, 0);
  $pdf->SetXY(90, 30);
  $pdf->MultiCell(135, 12, utf8_decode($pdftext), 0,'C', false);

  $pdf->Output(utf8_decode($pdf_file_name));

  /**
   * Sende Präsenzkarte per E-Mail
   */

  $message = Swift_Message::newInstance(); // Ein Objekt für die Mailnachricht.

  $message
      ->setFrom(array($absenderadresse => $absendername))
      ->setTo(array($zieladresse))
	  ->setBcc($email)
      ->setSubject($betreff);

  if (str_replace(' ','',$email_sekretaer) != "") { $message->setCC($email_sekretaer); }

  $mailtext = "";
  $mailtext .= "Liebe(r) ".$vorname." ".$nachname.",\n\n";
  $mailtext .= "Hiermit bestätigen wir Ihre Präsenz in unserem Meeting am ".$datum.".\n\n";
  $mailtext .= "Ihre persönliche Präsenzkarte zur Vorlage bei Ihrem Clubsekretär finden sie im Anhang dieser Mail.\n\n";
  if (($bemerkungen != "Bemerkungen") && (str_replace(' ','',$bemerkungen) != "")) $mailtext .= $bemerkungen."\n\n";
  $mailtext .= "Herzliche Grüße,\n".$versender."\n"."(".$email.")";

  $message->setBody($mailtext, 'text/plain');
  $message->attach(Swift_Attachment::fromPath(utf8_decode($pdf_file_name)));

  //$mailer = Swift_Mailer::newInstance(Swift_MailTransport::newInstance());
	
  // Create the Transport
  $transport = Swift_SmtpTransport::newInstance($mailServerName, $mailServerPort, $mailServerConnectionType)
	->setUsername($mailServerUsername)
    ->setPassword($mailServerPassword)
  ;

  // Create the Mailer using your created Transport
  $mailer = Swift_Mailer::newInstance($transport);
	
  $result = $mailer->send($message);

  if ($result == 0) {
      die("Fehler: Ihre Mail konnte nicht versendet werden.");
  }
  
  $success = "Präsenzkarte erfolgreich verschickt!";

}

header("Content-type: text/html; charset=utf-8");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
    <head>
        <title>Rotary E-Club of D-1850 - Präsenzkartengenerator</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="index.css" />
	    <!-- link calendar resources -->
		<link rel="stylesheet" type="text/css" href="tcal.css" />
	    <script type="text/javascript" src="tcal.js"></script> 
    </head>
    <body>
		<?php if(isset($success)) echo $success; ?>
	    
		<h2>Rotary E-Club of D-1850 - Präsenzerfassung</h2>
        <form accept-charset="utf-8" action="" method="post">
            <!-- Hier die eigentlichen Formularfelder eintragen  -->
			<div>
              <label for="Versender">Ihr Name (Sekretär/Präsident):</label>
              <input type="text" name="Versender" size="40" value="Jan-Arne Schiller"/>
            </div>
			<div>
              <label for="E-Mail">Ihre E-Mail (Sekretär/Präsident):</label>
              <input type="text" name="E-Mail" size="40" value="sekretaer@rotary-eclub1850.de"/>
            </div>
			<div>
              <label for="Vorname">Vorname Präsenzempfänger:</label>
              <input type="text" name="Vorname" size="40"/>
            </div>
			<div>
              <label for="Nachname">Nachname Präsenzempfänger:</label>
              <input type="text" name="Nachname" size="40"/>
            </div>
			<div>
              <label for="E-Mail">E-Mail Präsenzempfänger:</label>
              <input type="text" name="E-Mail-Empfaenger" size="40"/>
            </div>
			<div>
              <label for="E-Mail">E-Mail Sekretär des Präsenzempfängers (erhält Mail in CC):</label>
              <input type="text" name="E-Mail-Sekretaer" size="40"/>
            </div>
			<div>
              <label for="Clubname">Clubname Präsenzempfänger:</label>
              <input type="text" name="Clubname" size="40" value="RC xxxxxx"/>
            </div>
			<div>
              <label for="Datum">Datum des Meetings:</label>
              <input type="text" name="Datum" class="tcal" value=""/>
            </div>
			<div>
              <label for="Bemerkungen">Bemerkungen (wird in der Mail erwähnt, nicht auf der Karte):</label>
              <textarea name="Bemerkungen" rows="5" cols="40"></textarea>
            </div>
            <!-- Ende der Formularfelder -->
            <p>
            <input type="submit" value="Senden" />
            <input type="reset" value="Zurücksetzen" />
            </p>
        </form>
    </body>
</html>