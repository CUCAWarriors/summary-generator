<?php
function csv_to_array($filename='', $delimiter=',')
{
if(!file_exists($filename) || !is_readable($filename))
return FALSE;
$header = NULL;
$data = array();
if (($handle = fopen($filename, 'r')) !== FALSE)
{
while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
{
if(!$header)
$header = $row;
else
$data[] = array_combine($header, $row);
}
fclose($handle);
}
return $data;
} 

if (isset($_GET['p'])) {
$page = $_GET['p'];
}
else {
$page = "home";
}
 if ($page == "home") { ?>
<h1>Account Summary Generator</h1>
<p>You will need to upload 2 files, a listing of your patrons and a listing of fines</p>

<form action="/?p=upload" method="post" enctype="multipart/form-data">
    Patron CSV:
    <input type="file" name="patron_csv" id="patron_csv">
    Fines CSV:
    <input type="file" name="fines_csv" id="fines_csv"><br>
    Announcncement:<br>
    <textarea type="textarea" name="announcement" rows="4" cols="50"/>
    </textarea><br>
    <input type="submit" value="Upload CSVs" name="submit">
</form>
<?php }
 elseif ($page == "upload") { 
if(isset($_POST["submit"])) {
	// Include the main TCPDF library (search for installation path).
include 'tcpdf_load.php';
    $combined = array();
    $announcement = $_POST["announcement"];
 $patrons = csv_to_array($_FILES['patron_csv']['tmp_name'], ';');
 $fines = csv_to_array($_FILES['fines_csv']['tmp_name'], ';'); 
foreach ($patrons as &$patron) {
    unset($patroninfo);
    unset($fineinfo);
    $fineinfo = array();
    $cardnumber = $patron["cardnumber"];
    foreach ($fines as &$fine) {
        if ($fine["cardnumber"] == $cardnumber)
        {
            $fineinfo[] = array (
                "description" => $fine["description"],
                "amount" => $fine["amount"],
                "date" => $fine["date"],
                "line_id" => $fine["accountlines_id"],
                "notes" => $fine["note"],
                );
        }
    }
    $patroninfo = array(
        "firstname" => $patron["firstname"],
        "surname" => $patron["surname"],
        "cardnumber" => $patron["cardnumber"],
        "grade" => $patron['attribute'],
        "data" => $fineinfo
        );
        $combined[] = $patroninfo;
}

 
$head = <<<END
END;
$html = '';

 foreach ($combined as &$patron) {
 
     unset($statement);
     unset($header);
     
     $name = $patron["surname"] . ', ' . $patron["firstname"];
     $card = $patron["cardnumber"];
     $grade = $patron["grade"];
     $data = $patron["data"];
     if ($data[0]["line_id"] == "")
     {
     }
     else {
     $header = <<<END
		<h1>Library Account Statement</h1>
        <h2>$name ($cardnumber)</h2>
        <h3>Grade $grade </h3>

        <ul>
  
        </ul>

        

    <h2>Account fines and payments</h2>
        <table border="1">
            
            <tr>
                <th>Charge ID</th>
                <th>Description of charges</th>
                <th>Date</th>
                <th>Notes</th>
                 <th>Amount</th>
                
            </tr>
END;

$account_total = 0;
$body = "";

foreach ($data as &$line) {
    $desc = $line["description"];
    $amount = $line["amount"];
    $date = $line["date"];
    $line_id = $line["line_id"];
    $note = $line["notes"];
    $body .= "<tr><td>$line_id</td><td>$desc</td><td>$date</td><td>$note</td><td>$amount</td></tr>";
    $account_total = $account_total + $amount;
}
if ($account_total < 0) {
$account_total = $account_total * -1;
$total_note = "Have A Credit of";
}
else{
	$total_note = "Owe";
}
$body .= "</table>
<h3>You $total_note " . money_format('$%i',$account_total) . "</h3>
<h2>Notes</h2>
<p>$announcement</p>
";
$summary[] = array (
	'name' => $name,
	'cardnumber' => $card,
	'total' => money_format('$%i',$account_total)
	
	);
$statement = "$header $body  <br pagebreak='true'/>";

#$html = $statement;
$pdf->AddPage();
$pdf->writeHTML($statement, true, false, true, false, '');
$fold = "<br> <br><h1>Library Account Statement</h1><h2>$name</h2><h2>Grade $grade</h2><h3>View your account online at https://catalog.cucawarriors.com</h3>";

$pdf->AddPage();
$pdf->writeHTML($fold, true, false, true, false, '');

}
$total_note = "";
 }
 $report = " <h2>Report</h2>
 	<table border='1'><tr><th>Name</th><th>Cardnumber</th><th>Total</th></tr>";
 foreach ($summary as &$line) {

 	$report .= "<tr><td>" . $line['name'] . "</td><td>" . $line['cardnumber'] . "</td><td>" . $line["total"] . "</td></tr>";
 	
 	
 }
 $report .= "</table>";
 
$report .= "</body></html>";
$pdf->AddPage();
$pdf->writeHTML($report, true, false, true, false, '');
#$pdf->writeHTML($html, true, false, true, false);
# echo "<pre>";// Add a page
// This method has several options, check the source code documentation for more information.

// set text shadow effect

$pdf->Output('statements.pdf', 'I');
$pdf->lastPage();
#print_r ($head);
 #print_r($html);
# echo "</pre>";
#echo "<pre>";
 #print_r($combined);
#echo "</pre>";
 }

}
?>

