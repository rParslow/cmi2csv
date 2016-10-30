<?php
/***********************************************
//
//  Version 2.52 - par NeoDiffusion.Fr 
//  Version 3 & suivantes - par rParslow 
//
***********************************************/


// Debug can have parameters subtrace var lines
# zip works only with debug
 $debug = 'subtrace var lines';


// html parameters 
 $cmi_title   = "Convertisseur CMI";
 $cmi_curl    = selfURL(); 
 $cmi_h1      = "Convertisseur de fichier .CMI (CyberMut)";
 $cmi_help    = "Convertisseur de fichier CMI vers CSV.";
 $cmi_end     = '---';
 $cmi_license = "The unLicence";
 $cmi_version = "3.02";

// default values
 $maxfields   = 14;
 $ziplist     = array();
 $tmpdir      = "tmp/";
 $zipfile     = "cmi2csv.zip";
 $sep         = ";";
 $enclosure   = '"';
 $escape_c    = "\\" ;
 $champs      = array("--","ChampVide", "Date", "Libelle", "DebitCredit","Debit" ,"Credit");
 $champsdef   = array("ChampVide","ChampVide","ChampVide","ChampVide","ChampVide","ChampVide","Date","Date","ChampVide","ChampVide","Libelle","Debit","Credit");

//
// Processing
//

// Très crade: traiter le GET
 if (isset($_GET['champcible'])) {
     $_POST = $_GET;
     unset($_POST['uploadedfile']); // sécurité tout de même: pas d'upload via GET
 }
// Crade mais efficace:
 foreach($_POST as $k=>$v) {
    if (is_array($$k))
        foreach($$k as $k2=>$v2)
            $$k2 = $v2;
    else 
        $$k = $v;
 }

// create tmpdir if needed
 if(!is_dir($tmpdir)) {
   mkdir($tmpdir);
 } 

 if (file_exists($tmpdir.$zipfile))  {
   unlink($tmpdir.$zipfile);
 }

// Gestion du séparateur du CSV TSV
 if (isset( $_FILES['uploadedfile']['tmp_name'] ) && '' != $_FILES['uploadedfile']['tmp_name'] ) {
    $_FILES['uploadedfile']['name']; //  - name contains the original path of the user uploaded file.
    $_FILES['uploadedfile']['tmp_name']; // temp name
    if ('tab' == $sep) {
        $sep ="\t";
        $ext = ".tsv";
    } else {
        $ext =".csv";
    }

    // Build first line for spreadsheet header
    foreach($champcible as $k=>$v)
        if ('--' == $v)
            unset ($champcible[$k]);
    #3.x Moved after account 
    #$out = implode( $sep, $champcible )."\n";
	
    $destfile = substr($_FILES['uploadedfile']['name'], 0, strrpos($_FILES['uploadedfile']['name'], '.')).$ext;

    // traitement du fichier CMI
    if (preg_match('/lines/', $debug))
        echo "<h1>Traitement de ".$_FILES['uploadedfile']['name']."</h1>";

	$file = file( $_FILES['uploadedfile']['tmp_name']);
	$i=0; $debcred ='';
	if (is_array( $file )) {

        for ($l=0; $l<=count($file); $l++) { //start to process the file 


            $line = $file[$l];

		    if (preg_match('/lines/', $debug))
                echo "<br>&nbsp;&nbsp;&nbsp;[$l]= $line";

			if (preg_match("/^\[/", $line)) {                     // new account
				if (preg_match('/subtrace/', $debug))
					echo "<br><br>Debug:Trace:New Account Detected: $i <b>".trim(substr( $file[$l], 1 ))."</b><br>";

                //Find Account Label exit if | new records end at ]
                do {
                    $l++; 
		            if (preg_match('/lines/', $debug))
                        echo "<br>[[$l]] ".$file[$l];
 
                    if (preg_match("/^L/", $file[$l])) {          // Account name
                        #echo "<br>[[$l]] <b>account name</b> ".trim(substr( $file[$l], 1 ));
                        $acc_nme = trim(substr( $file[$l], 1 )); 
		                if (preg_match('/lines/', $debug))
                            echo "<br>[[$l]] <b>account name</b> $acc_nme";

                    } else if (preg_match("/^B/", $file[$l])) {   // Bank
                        #echo "<br>[[$l]] <b>Bank nbr</b> ".trim(substr( $file[$l], 1 ));

                    } else if (preg_match("/^A/", $file[$l])) {   // Branch
                        #echo "<br>[[$l]] <b>Branch nbr</b> ".trim(substr( $file[$l], 1 ));

                    } else if (preg_match("/^C/", $file[$l])) {   // Account number 
                        #echo "<br>[[$l]] <b>Accnt numbr</b> ".trim(substr( $file[$l], 1 ));
                        $acc_nbr = trim(substr( $file[$l], 1 )); 
		                if (preg_match('/lines/', $debug))
                            echo "<br>[[$l]] <b>account number</b> $acc_nbr";

                        $out .= "Number:".trim(substr( $file[$l], 1 ))."\n"; 

                    } else if ('|' == trim($file[$l])) { // we have almost a line
                        --$l;
	                    $out .= implode( $sep, $champcible )."\n";
                        if ('CRLF' == $lf)  stream_filter_register('crlf', 'crlf_filter');


                        // So create a file for this account 
                        $fp = fopen($tmpdir.$acc_nbr.$ext, 'wt');
                        if ('CRLF' == $lf)  stream_filter_append($fp, 'crlf');


                        // create the header of CSV 
                        array_push($ziplist, $tmpdir.$acc_nbr.$ext);
                        fputcsv($fp, $champcible, $sep, $enclosure, $escape_c);

                        break; // now process the lines
                    }

                } while (']' != trim($file[$l])); // end do

				if (preg_match('/subtrace/', $debug))
                    echo "<br><br>END DO<br>";
                    

			} else if ('|' == trim($line) OR ']' == trim($line)) {      // new record
				if (preg_match('/subtrace/', $debug))
					echo "<br>Debug:Trace:New Line Detected: $i";

				$new_line = '';
                $new_data = array();

				if ( $i ) {                       // skip first record: header
				if (preg_match('/subtrace/', $debug))
					echo " (non empty record)";

					foreach($champcible as $champ) {
						switch ($champ) {
							case 'Date':
								$new_line .= $date.$sep;
                                array_push($new_data, $date);
								break;

							case 'Debit':
								$new_line .= $deb.$sep;
                                array_push($new_data, $deb);
								break;

							case 'Credit':
								$new_line .= $cred.$sep;
                                array_push($new_data, $cred);
								break;

							case 'DebitCredit':
								$new_line .= $debcred.$sep;
                                array_push($new_data, $debcred);
								break;

							case 'Libelle':
								$new_line .= $lib.$sep;
                                array_push($new_data, $lib);
								break;

							default:
								$new_line .= $sep;
                                array_push($new_data, '');
								break;

						} // End Switch
					} // End foreach: build new_line

					if (preg_match('/subtrace/', $debug)) {
						echo "<br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;new_line=<b>$new_line </b>(".substr_count($new_line, $sep)." fields)<br>";
					}

					$out .= $new_line."\n";
					if (preg_match('/subtrace/', $debug)) {
                        echo "EndSwitch= ";
                        print_r($new_data);
                        echo "<br>";
                    }

                    // write the line
                    fputcsv($fp, $new_data, $sep, $enclosure, $escape_c);

					unset ($date, $deb, $cred, $debcred, $lib);
				}

				$i++; // count records

			} else if (preg_match("/^D/", $line)) {  // date
				$date = trim(substr( $line, 1 ));

				if (preg_match('/subtrace/', $debug))
                    echo "<br>^D date= $date</br>";

			} else if (preg_match("/^L/", $line)) {  // description 
				$lib = trim(substr( $line, 1 ));

				if (preg_match('/subtrace/', $debug))
                    echo "<br>^L date= $lib</br>";

			} else if (preg_match("/^M/", $line)) {  // ammount 
				$debcred = trim(substr( $line, 1 ));
				if ($debcred >= 0)
					$cred = $debcred;
				else
					$deb = $debcred;

			} else if (']' == trim($line)) {         // end of account
				if (preg_match('/subtrace/', $debug)) {
                    echo "<br><br>end of account<br>";
                    print_r($new_data);
                    echo "<br>--------- $lib<br>";
                }

			}
		
		} //end for
	}

	// export file as CSV
	if (preg_match('/var/', $debug)) {
		echo "<br><br>Debug:Var:$i lines processed, output size=".strlen($out);
    } else {
	    header("Content-Disposition: attachment; filename=\"$destfile\"");
	    header("Content-Type: application/vnd.ms-excel");		
//	    header("Content-Type: application/force-download"); 
//	    header("Content-Transfer-Encoding: $type\n"); // Surtout ne pas enlever le \n
//	    header("Content-Length: ".strlen($out) ); 
	    header("Pragma: no-cache"); 
	    header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public"); 
	    header("Expires: 0"); 
	    echo $out;
    }


    if (preg_match('/var/', $debug)) {
	    echo "<br><br>ziplist=";
        print_r($ziplist);
    }


    $result = create_zip($ziplist, $tmpdir.$zipfile);

	if (preg_match('/var/', $debug)) {
        echo "<br>zip = [$result]";
    }

    // add original cmi in ZIP file 
      #  and log ?
    $zip = new ZipArchive;
    if ($zip->open($tmpdir.$zipfile) === TRUE) {
       $zip->addFile($_FILES['uploadedfile']['tmp_name'], $tmpdir.$_FILES['uploadedfile']['name']);
       $zip->close();
	   if (preg_match('/var/', $debug)) 
           echo ' ADDZIP ok';
    } else {
	   if (preg_match('/var/', $debug)) 
           echo ' ADDZIP fail';
    }

    // delete uploaded cmi file 
	unlink($_FILES['uploadedfile']['tmp_name']);

    // delete csv generated from cmi file 
    array_map('unlink', $ziplist);

/*
$zipfile="tmp/archive.zip" //file location 
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.basename($zipfile).'"'); 
header('Content-Length: ' . filesize($zipfile));
readfile($file);
*/

	die(0);
}

// end of processing

/////////////////////////////////////////////
// functions 

// get script URL
function selfURL(){
    list($prot) = explode('/',strtolower($_SERVER['SERVER_PROTOCOL']));
    $s          = $_SERVER['HTTPS'] == 'on' ? 's' : '';
    $port       = $_SERVER['SERVER_PORT'] == '80' ? '' : ':'.$_SERVER['SERVER_PORT'];
    return $prot.$s.'://'.$_SERVER['SERVER_NAME'].$port.$_SERVER['PHP_SELF'];
}

// filter class that applies CRLF line endings
class crlf_filter extends php_user_filter
{
    function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            // make sure the line endings aren't already CRLF
            $bucket->data = preg_replace("/(?<!\r)\n/", "\r\n", $bucket->data);
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }
        return PSFS_PASS_ON;
    }
}

// Zip an array 
function create_zip($files, $destination = '', $overwrite = true) {

    //if the zip file already exists and overwrite is false, return false
    if(file_exists($destination) && !$overwrite) { return false; }

    //vars
    $valid_files = array();
    //if files were passed in...
    if(is_array($files)) {
        //cycle through each file
        foreach($files as $file) {
            //make sure the file exists
            if(file_exists($file)) {
                $valid_files[] = $file;
            }
        }
    }

    //if we have good files...
    if(count($valid_files)) {
        //create the archive
        $zip = new ZipArchive();

        if($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
# Doesn't work in PHP 5.6 / 7.0 
            return false;
        }

        //add the files
        foreach($valid_files as $file) {
            $zip->addFile($file,$file);
        }

        //debug
        //echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
        
        //close the zip -- done!
        $zip->close();
        
        //check to make sure the file exists
        return file_exists($destination);
    } else {
        return false;
    }
}
// end Functions 



// HTML part
?>
<html>
<head>
<title><? echo $cmi_title; ?></title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<link rel="canonical" href="<? echo $cmi_curl; ?>">
</head>
<body>
<h1><? echo $cmi_h1; ?></h1>
<form enctype="multipart/form-data" action="<?php echo basename($_SERVER['PHP_SELF']); ?>" method="POST">
<input type="hidden" name="MAX_FILE_SIZE" value="500000" />

1. D&eacute;finir les champs du fichier CSV apr&egrave;s conversion&nbsp;:(<a href="<?php echo basename($_SERVER['PHP_SELF']); ?>?champcible%5B0%5D=Date&champcible%5B1%5D=Debit&champcible%5B2%5D=Credit&champcible%5B3%5D=Libelle&champcible%5B4%5D=--&champcible%5B5%5D=--&champcible%5B6%5D=--&champcible%5B7%5D=--&champcible%5B8%5D=--&champcible%5B9%5D=--&champcible%5B10%5D=--&champcible%5B11%5D=--&champcible%5B12%5D=--&champcible%5B13%5D=--&sep=;&lf=CRLF">exemple</a>)<br \>
<?php
for ($i = 0; $i < $maxfields; $i++) {
	echo "<select name='champcible[$i]'>\n";
	foreach ($champs as $champ) {
		echo "<option value='$champ'";
		if ( (isset($champcible[$i]) && $champcible[$i] == $champ) || (!isset($champcible[$i]) && $champsdef[$i]==$champ))
			echo " SELECTED";
		echo ">$champ</option>\n";
	}
	echo "</select>\n";
	if ($i < $maxfields-1)
		echo " -&gt;";
	if (!(($i+1)%7))
		echo "<br \>";
	
//	echo "$i<br>";
}
if ('' != $debug)
	echo "<input type='hidden' name='debug' value='$debug'>\n";
?>
<br>
<?php if (isset($_FILES['uploadedfile']['tmp_name'] ) )
	echo "<font color='red' id='errfile'>Erreur, veuillez</font><br>";
?>

<br>
2. S&eacute;lectionner le fichier CMI &agrave; convertir&nbsp;: <input name="uploadedfile" value="
<?php
if (isset($_FILES['uploadedfile']['name']))
	echo $_FILES['uploadedfile']['name'];
else
	echo "*.cmi";
?>
" type="file" accept="*.cmi"/><br />

<br>3. S&eacute;parateur de champs dans le fichier r&eacute;sultant&nbsp;: <select name='sep'>
<option value=";" <?php if (";" == $sep ) echo " SELECTED"; ?>> ; </option>
<option value=";" <?php if ("," == $sep ) echo " SELECTED"; ?>> , </option>
<option value="tab" <?php if (";" != $sep && ',' != $sep ) echo " SELECTED"; ?>> (tab) </option>
</select> 
<br>

<br>4. S&eacute;parateur de lignes dans le fichier r&eacute;sultant&nbsp;: <select name='lf'>
<option value="CR" <?php if ("CR" == $lf ) echo " SELECTED"; ?>> CR </option>
<option value="CRLF" <?php if ("CRLF" == $lf ) echo " SELECTED"; ?>> CRLF </option>
</select> 

<input type="submit" value="Convertir" onclick="document.getElementById('errfile').style.display = 'none';" />

</form>

<p>
 <br><br>
 <hr>
 <? echo "CMI2CSV Version $cmi_version - $cmi_curl"; ?><br>
 <? echo $cmi_help; ?><br>
 Licence: <? echo $cmi_license; ?><br>
<? echo $cmi_end; ?>
</p>


</body>
</html>
