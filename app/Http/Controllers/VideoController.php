<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\VideoManager;

class VideoController extends Controller
{
    public function generateVideo(Request $request)
    {
    	return 123;
    	// Retrieve input from POST request

        // $videoNames = $request->input('videos') ?? null;
        // $idItin = $request->input('id_itin') ?? null;
        // $name = $request->input('name') ?? null;

        $videoNames = ['san_jose','tortugero','fjfj'];

 		$vm = new VideoManager();

		 // GET corresponding URLS 
	    $urls = $vm->getURLsFromNames($videoNames);
	    var_dump($urls);
	    return;
	    
		// CREATE VIDEO
		$video = $vm->createVideoFromUrls($urls);
		
		 //echo $res;
		
		///// UPLOAD VIDEO TO VOO
		$videoName="Video "+$name;
		$voo = $vm->uploadVideoToVoo($video,$videoName);
		//  //echo $voo;

		// ///// UPDATE COLUMN VIDEO URL IN KNACK
		 $data = $vm->addUrlToKnack($idItin,$voo);

		///// SEND RESPONSE MESSAGE
 		return $voo;
    }
}
