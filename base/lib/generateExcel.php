<?php
/**
 * PHPExcel
*/

/** Error reporting */
error_reporting(E_ALL);

date_default_timezone_set('Europe/London');

/** Include PHPExcel */
require_once 'PHPExcel-1.7.7/Classes/PHPExcel.php';

$details = html_entity_decode($_POST['data']);
$filename = html_entity_decode($_POST['name']);
$headerDelimiter = '---';
$headerDetailDelimiter = ',';
$userDelimiter = '||';
$userDetailDelimiter = '~';
//Get header and users list separated
list($header, $userDetails) = explode($headerDelimiter, $details);
//Get array of all the users
$users = explode($userDelimiter, $userDetails);
// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
							 ->setLastModifiedBy("Steven Dunn")
							 ->setTitle("Office 2007 XLSX Document")
							 ->setSubject("Office 2007 XLSX Document")
							 ->setDescription("Export To Excel.")
							 ->setKeywords("office 2007 openxml php")
							 ->setCategory("result file");

//Adding header to Excel sheet.
$headerDetails = explode($headerDetailDelimiter, $header);
$count = count($headerDetails);
$row = 1;
// for($i=0;$i<$count;$i++){
	// $objPHPExcel->setActiveSheetIndex(0)
            // ->setCellValueByColumnAndRow($i, $row, $headerDetails[$i]);
// }
$objPHPExcel->getActiveSheet()->fromArray(array($headerDetails),NULL,'A'.$row);  
$objPHPExcel->getActiveSheet()->getStyle('A1:N1')->getFont()->setBold(true); 
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('N')->setAutoSize(true);

$objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
$row++;
//Adding User details to Excel sheet.
$userCount = count($users);
for($j=0;$j<$userCount;$j++){
	$userDetails = explode($userDetailDelimiter, $users[$j]);
	$userDetailsCount = count($userDetails);
	for($k=0;$k<$userDetailsCount;$k++){
		$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValueByColumnAndRow($k, $row, $userDetails[$k]);
	}
	$row++;
}


// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('User Details');


// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);

// Redirect output to a client’s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename='.$filename);
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');
exit;
