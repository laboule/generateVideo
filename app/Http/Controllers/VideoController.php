<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\VideoManager;

class VideoController extends Controller
{
    public function generateVideo(Request $request)
    {
    	// Retrieve input from POST request

         $videoNames = $request->input('videos') ?? null;
         $idItin = $request->input('id_itin') ?? null;
         $name = $request->input('name') ?? null;
	
        

 		$vm = new VideoManager();

		 // GET corresponding URLS 
	    $urls = $vm->getURLsFromNames($videoNames);
	    //var_dump($urls);
	    
	    
		// CREATE VIDEO
		$video = $vm->createVideoFromUrls($urls);
			
		 		
		///// UPLOAD VIDEO TO VOO
		$videoName="VIDEO ".$name;
		$voo = $vm->uploadVideoToVoo($video,$videoName);
		

		// ///// UPDATE COLUMN VIDEO URL IN KNACK
		 $data = $vm->addUrlToKnack($idItin,$voo);

		///// SEND RESPONSE MESSAGE
 		return $voo;
    }
}
