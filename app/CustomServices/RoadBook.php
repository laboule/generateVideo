<?php
namespace App\Helpers;
use App\Helpers\DocManipulator;
use \PhpOffice\PhpWord\PhpWord;
use \PhpOffice\PhpWord\IOFactory;
use \DocxMerge\DocxMerge;
use \PhpOffice\PhpWord\TemplateProcessor;

/**
 * add lang es or fr
 */

class RoadBook
{


public $docManipulator; 
public $data,$pathTemplatesn,$outputPath;

public function __construct($data, $pathTemplates,$lang,$outputPath)
  {

    $this->docManipulator = new DocManipulator($lang,$outputPath);
    $this->data = $data;
    $this->pathTemplates = $pathTemplates;
    $this->outputPath = $outputPath;

  }

public function generateRoadBook()
{
   $templatePath = $this->pathTemplates;
   $items = $this->data;
    // PROGRAMME DU VOYAGE JOUR PAR JOUR
    $program = $this->generateProgram($items,$templatePath);

    // RESUME DU VOYAGE
    $table = $this->generateTableTrip($items);

    // DOCUMENT FINAL
    $finalDoc = $this->docManipulator->mergeDocuments([$table, $program]);

    // SUPPRESSION DES DOCUMENTS TEMPORAIRES
    DocManipulator::deleteFileList([$program, $table]);
    
    return $finalDoc;
}

public function generateFullRoadBook()
{
     $templatePath = $this->pathTemplates;
     $items = $this->data;

    $roadBook = $this->generateRoadBook();
    
    $finalDoc = $this->docManipulator->mergeDocuments([$templatePath.'roadbook_first.docx',$roadBook,$templatePath.'roadbook_last.docx' ]);
 
    // SUPPRESSION DES DOCUMENTS TEMPORAIRES
    unlink($roadBook);
    //DocManipulator::deleteFileList([$roadBook]);
    
    return $finalDoc;
}

public function generateTableTrip()
{
    $items = $this->data;
    // tri de $items on ne garde que les handler_category="Alojemiento" aka les hôtels
    $data = array_filter($items, function($item) {
        return isset($item["handler_category"]) && $item["handler_category"] == "Alojamiento" ;
    });

    // GENERATION DU DOC RESUME DU VOYAGE 
    $table = $this->docManipulator->generateTableDoc($data);
    
    return $table;

}


public function generateProgram()
{
    $templatePath = $this->pathTemplates;
    $items = $this->data;
    // tri de items par jour de voyage
    $itineraire = Self::convertInputData($items);

     // GENERATION DU DOC PROGRAMME DE VOYAGE JOUR PAR JOUR
    $program = $this->docManipulator->generateProgramDoc($itineraire,$templatePath);
    

return $program;

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

public static function convertInputData(array $data)
{
// TRI DATA par jours 
    if(!empty($data))
    {
        $itineraire = [1 => [$data[0]]];

        $dCurrent = new \DateTime($data[0]["check_in"]);
        $i=1;

        foreach ($data as $key=>$item) {

            if ($key > 0) {

                $dNow = new \DateTime($item["check_in"]);
                $dDiff = $dNow->diff($dCurrent);

                if($dDiff->days > 0)
                {
                    // new day
                    $i += $dDiff->days ;
                    $itineraire[$i] = [$item];
                    $dCurrent = $dNow;
                }
                else
                {
                    array_push($itineraire[$i],$item);
                    $dCurrent = $dNow;
                }    
            }    
        }
        return $itineraire;
    }
}
}