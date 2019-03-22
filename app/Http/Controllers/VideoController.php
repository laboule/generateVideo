<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\VideoManager;

class VideoController extends Controller
{
    public function generateVideo(Request $request)
    {

    	// Retrieve input from POST request
        $videoNames = $request->input('videos') ?? [];
        $idItin = $request->input('id_itin') ?? "";
        
    	$ffmpeg = "ffmpeg"; //local dev - PATH windows !
    	//binaries
    	//$ffmpeg = base_path().'/ffmpeg'; 
 		$vm = new VideoManager($ffmpeg);

		 // GET corresponding URLS 
	    $urls = $vm->getURLsFromNames($videoNames);
	    
	    
		// CREATE VIDEO
		$video = $vm->createVideoFromUrls($urls);
		 //echo $res;
		
		///// UPLOAD VIDEO TO VOO
		 $voo = $vm->uploadVideoToVoo($video);
		//  //echo $voo;

		// ///// UPDATE COLUMN VIDEO URL IN KNACK
		 $data = $vm->addUrlToKnack($idItin,$voo);

		///// SEND RESPONSE MESSAGE
 		return $voo;
    }
}
