<?php
namespace App\Helpers;

use \PhpOffice\PhpWord\TemplateProcessor;
use \PhpOffice\PhpWord\PhpWord;
use \PhpOffice\PhpWord\IOFactory;
use \DocxMerge\DocxMerge;

/**
 * 
 */

class DocManipulator 
{ 
  public $lang = "fr";
  public $outputPath;

  public static $listItemBeginning = [
  "fr"=>
  [
    "telephone" =>"Tél : ",
    "reservation"=>"N° Reservation : ",
    "address"=>"Adresse : ",
    "gps" => "GPS : ",
    "check_in" => "Check-in : ",
    "check_out" => "Check-out : ",
    "service" => "Service : ",
    "important" => "Important : ",
    "good_to_know"=> "Bon à Savoir : "], 
  "es"=>
  [
    "telephone" =>"Tel : ",
    "reservation"=>"N° reservación : ",
    "address"=>"Dirección : ",
    "gps" => "GPS : ",
    "check_in" => "Check-in : ",
    "check_out" => "Check-out : ",
    "service" => "Servicio : ",
    "important" => "Importante : ",
    "good_to_know"=> "Conviene saber : "]];

    public static $day_title =[
      "es"=>"Día", "fr"=>"Jour"
    ];


  public function __construct($lang,$outputPath)
  {
    $this->lang = $lang;
    $this->outputPath = $outputPath;
  }

 /**
 *
 * Generate a basic document (HTML or DOCX)
 *
 * @param   string $text the text to add, 
 * @param   string $format the format (HTML html/DOCX) case insensitive
 * @return   mixed : the path to the document (string) or null
 *
 */

public function generateProgramDoc(array $itineraire,string $templatePath)
{
	  $listFiles =[]; // our little bricks :)
    $programPath = $templatePath.'travel_book/';

    foreach ($itineraire as $day => $items) {
          // Date
          $date = new \DateTime($items[0]["check_in"]);
          $date = $date->format('d/m/Y');
          // Retrieve location for day title
          $location="";
          foreach ($items as $item) {
            if($item["handler_category"] == "Alojamiento")
            {
              $location = $item["location"];
              break; // stop when first location (type : Alojamiento) is found
            }
          }
          if(!empty($location))
          {
            $location = ", ".$location; // adding some custom spacing for template
          }
          // Generate TITLE DAY file

          //// OPTION 1

          // bold 14, left
          $phpWord = new PhpWord();
          $section = $phpWord->addSection(array('paperSize'=>'Letter'));
          $phpWord->addParagraphStyle(
              'center',
              array(//'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
          'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(1),
          'spacing' => 120,
          'lineHeight' => 1,
          'align'=>"center",
          'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
          ));

          $dayBegin = Self::$day_title[$this->lang];
          $text = $dayBegin." ".$day." - ".$date.$location;
          $day_title_doc=$this->outputPath.'day_title_'.uniqid().'.docx';
          
          $section->addText($text, array("bold"=>true,"size"=>12,"name"=>"Century Gothic"), 'center');
          $section->addTextBreak(1,[],
            array('spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(0), 'lineHeight' => 0.9));
          $phpWord->save($day_title_doc);
        

          array_push($listFiles, $day_title_doc); //push day title to our list of files

          // Generate Items for $day
          foreach ($items as $item) {

          	//generate Item List
          	$file = $this->generateItem($item);

            array_push($listFiles, $file); //push item to our list of files
          }
}

array_unshift($listFiles, $programPath.'title_roadbook.docx'); // add title of the roadbook at beginning
$roadbook = $this->mergeDocuments($listFiles); // concatenate
array_shift($listFiles); // we don't want to remove the title_roadbook template file ! 
Self::deleteFileList($listFiles); // delete temporary files

return $roadbook;
}

 /**
 *
 * Generate a basic document (HTML or DOCX)
 *
 * @param   string $text the text to add, 
 * @param   string $format the format (HTML html/DOCX) case insensitive
 * @return   mixed : the path to the document (string) or null
 *
 */


public function generateItem($data)
{

$check_in = new \DateTime($data["check_in"]);
$check_out = new \DateTime($data["check_out"]);
$check_in = $check_in->format('d/m/Y');
$check_out = $check_out->format('d/m/Y');

$phpWord = new PhpWord();

// Naming OUTPUT file
$outputName = 'item_'.uniqid();
$itemPath = $this->outputPath.$outputName.'.docx';

// STYLING

$phpWord->addFontStyle(
    'bold',
    array(
      'bold' => true,
      'name'=>'Century Gothic',
      'size' => 11)
);

$phpWord->addFontStyle(
    'basic',
    array(
      'name'=>'Century Gothic',
      'size' => 11)
);

$phpWord->addParagraphStyle(
    'left',
    array(//'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(1),
'spacing' => 120,
'lineHeight' => 1,
'align'=>"left",
'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
));

// START writing
$beginning = Self::$listItemBeginning[$this->lang];

$section = $phpWord->addSection(array('paperSize'=>'Letter',"align"=>'left'));
//$section->setPaperSize();

 if (!empty($data["handler"])) {
   $textrun = $section->addTextRun('left');
   $textrun->addText($data["handler"]." : ",'bold');
   //$textrun->addTextBreak(1);
 }

 if (!empty($data["telephone"])) {
   
   $textrun = $section->addTextRun('left');
   $textrun->addText($beginning["telephone"],'basic');
   $textrun->addText($data["telephone"],'basic');
 }

 // Text break
 $section->addTextBreak(1,[],'left');

  if (!empty($data["address"])) {
   $textrun = $section->addTextRun('left');
   $textrun->addText($beginning["address"],'basic');
   $textrun->addText($data["address"],'basic');
 }
   if (!empty($data["gps_latitude"])) {
   $textrun = $section->addTextRun('left');
   $textrun->addText($beginning["gps"],'basic'); 
   $textrun->addText($data["gps_latitude"]." / ".$data["gps_longitude"],'basic');
 }
 if (!empty($data["Check-in"])) {
  $textrun = $section->addTextRun('left');
  $textrun->addText($beginning["check_in"],'basic'); 
  $textrun->addText($check_in."   ",'basic');
  $textrun->addText($beginning["check_out"],'basic');
  $textrun->addText($check_out,'basic');
 }
 if (!empty($data["service"])) {
   $textrun = $section->addTextRun('left');
   $textrun->addText($beginning["service"],'basic');
   $textrun->addText($data["service"],'basic');
 }
  if (!empty($data["important"])) {
   $textrun = $section->addTextRun('left');
   $textrun->addText($beginning["important"],'bold');
   $textrun->addText($data["important"],'basic');
 }
   if (!empty($data["good_to_know"])) {
   $textrun = $section->addTextRun('left');
   $textrun->addTextBreak(1);
   $imgSrc = public_path('img/img01.png');
   $textrun->addImage($imgSrc); 
   $textrun->addText($beginning["good_to_know"],'bold');
   $textrun->addTextBreak();
   $textrun->addText($data["good_to_know"],'basic');
 }
 $section->addTextBreak(1,[],'left');

// EXPORT DOC
$objWriter = IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save($itemPath);


return $itemPath;

}

 /**
 *
 * Generate a basic document (HTML or DOCX)
 *
 * @param   string $text the text to add, 
 * @param   string $format the format (HTML html/DOCX) case insensitive
 * @return   mixed : the path to the document (string) or null
 *
 */

public function generateTableDoc(array $data)
{

// DOCUMENT CREATION ////
$phpWord = new PhpWord();
$section = $phpWord->addSection(array('paperSize'=>'Letter'));

// STYLING
$firstRow = 'customFirstRow';
$titleFont = 'titleFont';
$cellFont = 'cellFont';
$basicRow = 'basicRow';

$phpWord->addFontStyle(
    $firstRow,
    array('name' => 'Century Gothic', 'size' => 12, 'bold' => true)
);

$phpWord->addFontStyle(
    $basicRow,
    array('name' => 'Century Gothic', 'size' => 10)
);

$phpWord->addFontStyle(
    $titleFont,
    array('name' => 'Century Gothic', 'size' => 22)
);

$phpWord->addFontStyle(
    $cellFont,
    array('name' => 'Century Gothic', 'size' => 10)
);

$phpWord->addParagraphStyle(
    'generalStyle', array('align'=>'center')
);

$phpWord->addTableStyle('myTable', 
    array(
    'borderColor' => '006699',
    'borderSize'  => 6,
    'cellMargin'  => 50,
    'valign' => 'center'
    ));

// TITLE
if ($this->lang == "fr") {
	$section->addText("En un clin d’œil", $titleFont, 'generalStyle');
}
else
{
	$section->addText("Resumen de Viaje", $titleFont, 'generalStyle');
}
$section->addText("");


// CREATE TABLE
$table = $section->addTable('myTable');

// FIRST ROW
$table->addRow();
if ($this->lang == "fr") {

	$table->addCell(2000)->addText("Check-in", $firstRow, 'generalStyle' );
  $table->addCell(2000)->addText("Check-out", $firstRow, 'generalStyle' );
	$table->addCell(2000)->addText("Lieux", $firstRow, 'generalStyle');
	$table->addCell(4000)->addText("Hôtels", $firstRow, 'generalStyle');
	$table->addCell(4000)->addText("Réservations", $firstRow, 'generalStyle');
}
else
{
	
  $table->addCell(2000)->addText("Check-in", $firstRow, 'generalStyle' );
  $table->addCell(2000)->addText("Check-out", $firstRow, 'generalStyle' );
	$table->addCell(2000)->addText("Lugar", $firstRow, 'generalStyle');
	$table->addCell(4000)->addText("Hoteles", $firstRow, 'generalStyle');
	$table->addCell(4000)->addText("Reservación", $firstRow, 'generalStyle');

}

// fill table rows
foreach ($data as $key => $item) {
        
$check_in =  new \DateTime($item["check_in"]);
$check_out =  new \DateTime($item["check_out"]);
$check_in = $check_in->format('d/m/Y');
$check_out = $check_out->format('d/m/Y');


$table->addRow();
$table->addCell(2000)->addText($check_in, $basicRow,'generalStyle');
$table->addCell(2000)->addText($check_out, $basicRow,'generalStyle');
$table->addCell(2000)->addText($item["location"], $basicRow, 'generalStyle');
$table->addCell(4000)->addText($item["handler"],$basicRow, 'generalStyle');
$table->addCell(4000)->addText($item["reservation"],$basicRow,'generalStyle');

}

$section->addPageBreak();

// EXPORT DOC

$outputName = 'table_'.uniqid().'.docx';
$output = $this->outputPath.$outputName;
$objWriter = IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save($output);
return $output;

}

 /**
 *
 * Generate a basic document (HTML or DOCX)
 *
 * @param   string $text the text to add, 
 * @param   string $format the format (HTML html/DOCX) case insensitive
 * @return   mixed : the path to the document (string) or null
 *
 */

public function generateDocFromTemplate(string $templatePath, array $data=[])
{
// $data = [$key1 => $val1, $key2 => $val2 ....]
$arrayKeys = array_keys($data); // [$key1, $key2 ...]
$arrayValues = array_values($data); // [$val1, $val2...]

$outputName = 'template_'.uniqid();
$outputPath = $this->outputPath.$outputName.'.docx';

$templateProcessor = new TemplateProcessor($templatePath);

$templateProcessor->setValue($arrayKeys,$arrayValues);
$templateProcessor->saveAs($outputPath);

 return $outputPath;
}


public function mergeDocuments(array $listDocPath)
{

 $outputName = 'document_'.uniqid();
 $outputPath = $this->outputPath.$outputName.'.docx';
 ///// DOCXMERGE
$dm = new DocxMerge();
$dm->merge( $listDocPath, $outputPath);
return $outputPath;
}


public static function deleteFileList(array $fileList)
{
    foreach ($fileList as $file) {
        unlink($file);
    }
}
}