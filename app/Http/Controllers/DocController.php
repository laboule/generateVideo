<?php

namespace App\Http\Controllers;

use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;




class DocController extends Controller
{
    public function generate(Request $req)
    {
    	// Retrieve data from request, some json
    	$data = ['title' => 'Welcome to HDTuto.com'];
  
    	// load view template
         $pdf = PDF::loadView('roadmap/myPDF', $data);

    	// generate and return pdf
        return $pdf->stream();
    	
    }

    public function send(Request $req)
    {

    	$param = $req->input('param');
    	$data = $req->input('data');
    	// Retrieve data from request, some json
       	$lang = $param['lang'] ?? "I don't know !" ;
       	$format = $param['format'] ?? "I don't know !";
       
  		$data = ['lang' => $lang, 'format' => $format, 'data' => $data];

    	// load view(check langage first : fr or es and extension : pdf or docx) template and pass data

    	$current = time();
  		$fileName = 'roadMap'.$current.'.pdf';
  		$filePath='storage/roadmap/'.$fileName;

        $pdf = PDF::loadView('roadmap/myPDF', $data)->save($filePath);
        
    	// generate and return pdf
        return response()->json(['url' => asset($filePath)]);
    }

    // clear storage pdf and docx - get request
    public function delete(Request $req)
    {
    	$files = glob(public_path('storage/roadmap/*')); // get all file names
		foreach($files as $file){ // iterate files
		  if(is_file($file))
		    unlink($file); // delete file
		}
    	
    	//Storage::delete(public_path('storage/roadMap1550751148.pdf'));


    }

    public function test(Request $req)
    {
  
    	return json_encode($req->input('data'));


    }
}
