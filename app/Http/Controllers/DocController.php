<?php

namespace App\Http\Controllers;

use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Helpers\RoadBook;
use App\Helpers\DocManipulator;

class DocController extends Controller
{

  public function generateDoc(Request $req)
    {
      
      // Retrieve input param from POST request
        $param = $req->input('param') ?? [];
        //$data = [];
        $data = $req->input('data') ?? [];
       	$lang = $param['lang'] ?? "fr" ; 
       	$format = $param['format'] ?? "docx"; 


      if (!empty($data)) {

        $pathOutput = public_path('storage/roadbook/');
        $pathTemplates =  public_path('storage/templates/'.$lang.'/');
      
        // filter special characters
        foreach ($data as &$item) {
        	$good_to_know = $item["good_to_know_".$lang] ?? ""; 
        	$item["good_to_know"]=$good_to_know;
        	foreach ($item as $key => &$value) {
        		$value = htmlspecialchars($value);
        	}
        }



        $roadBook = new RoadBook($data, $pathTemplates, $lang, $pathOutput); // Our RoadBook instance

        if(isset($param["full"]) && $param["full"] == "yes") // generate full roadbook
        {
            $url = $roadBook->generateFullRoadBook();
        }
        else
        {
            $url = $roadBook->generateRoadBook();
        }
        
        chmod($url, 0755); // to avoid reading issues in OVH 
        $fileName = basename($url); // fileName document_4554.docx
        $url = asset('storage/roadbook/'.$fileName); //generate URL
        return response()->json(['url' => $url]);
       
      }
      else
      {
        return response()->json(['data' => "no data passed"]);
      }
    
    }

    public function deleteDoc(Request $req)
    {
      $doc = $req->input("name");

      DocManipulator::deleteFileList([$doc]);
    }

}
