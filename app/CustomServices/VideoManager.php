<?php

namespace App\Helpers;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * 
 */
class VideoManager 
{
	
	private $ffmpeg,$music,$vooKey,$vooApi,$knackAppID,$knackAPI,$knackKey;
	

	public function __construct($ffmpeg)
	{
	
		$this->ffmpeg=$ffmpeg;

		$this->music = realpath(public_path('storage/audio/music.mp3'));
		$this->vooKey = env("VOO_API_KEY", "somedefaultvalue");
		$this->vooApi = env("VOO_API_URL", "somedefaultvalue");
		$this->knackAppID = env("KNACK_APP_ID", "somedefaultvalue");
		$this->knackAPI = env("KNACK_API_URL", "somedefaultvalue");
		$this->knackKey = env("KNACK_API_KEY", "somedefaultvalue");

	}
	
	public function createVideoFromUrls(array $urls=[]): ?string
	{
		$ffmpeg = $this->ffmpeg;
		$musicPath = $this->music;
		if (!empty($urls)) {
			// Add urls to list.txt
			foreach ($urls as $key => $url) {
				$text = "file '$url' \n";
				if ($key===0) {
					file_put_contents("list.txt", $text);
				}
				else
				{
					file_put_contents("list.txt", $text,FILE_APPEND);	
				}
				
			}

		
			// Create Output Paths
			$id = uniqid();
			$outputRoot = public_path('storage/video_output/');
			$outputVideoNoAudio = $outputRoot.'video_withoutAudio_'.$id.'.mp4';
			$outputVideo = $outputRoot.'video_uncomp_'.$id.'.mp4';
			$outputVideoComp = $outputRoot.'video_'.$id.'.mp4';

			// LOG 
			$today = date("dmY"); 
			$logVideo = storage_path()."/logs/video_".$today.".log";


																										
// for debian ovh add "nice" at begining of command for CPU usage !
			// Concatenate videos - without audio
			
			$concat = `{$ffmpeg} -safe 0 -protocol_whitelist file,http,https,tcp,tls -f concat -i list.txt -c copy -an {$outputVideoNoAudio} >> {$logVideo} 2>&1`;

			// Add audio
			$addAudio = `{$ffmpeg} -i {$outputVideoNoAudio} -i {$musicPath} -codec copy -shortest {$outputVideo} >> {$logVideo} 2>&1`;

			// compress video (less size)

			$compressVeryFastCRF = `{$ffmpeg} -i {$outputVideo} -vcodec libx264 -crf 31 -preset veryfast -c:a copy {$outputVideoComp} >> {$logVideo} 2>&1`;

			// $compress = `ffmpeg -i {$outputVideo} -vcodec h264 -acodec aac {$outputVideoComp}`;
			// Delete temp video (without audio) and list.txt
			unlink($outputVideoNoAudio);
			unlink($outputVideo);
			unlink("list.txt");

			// return final video path
			//echo realpath(__DIR__.'/'.$outputVideo);
			chmod($outputVideoComp, 0755);
			return $outputVideoComp;
		}

		// no urls provided
		return null;
	}

	public function uploadVideoToVoo(?string $videoPath)
	{


		// Guzzle Client
		$client = new Client(['verify' => false]); //SSL diable for local testing

		$response = $client->request(
			'POST',  
			// from env -config(app.vooUrlAPI)
			$this->vooApi.'/createVideo', 
			[
			'multipart' => 
				[
	       	 		[
		            'name'     => 'vooKey',
		            // from env ? config(app.vooKey)
		            'contents' => $this->vooKey
	        		],
	        		[
		            'name'     => 'name',
		            'contents' => 'video_'.uniqid()
	        		],
	        		[
		            'name'     => 'create',
		            'contents' => 1
	        		],
	        		[
		            'name'     => 'customS3',
		            'contents' => ''
	        		],
	        		[
		            'name'     => 'file',
		            'contents' => fopen($videoPath, 'r')
	        		],

				]
			]);


			if ($response->getBody()) 
			{
			    return $response->getBody(); // URL voo
			}
			return null;
	}

	public function getVideosFromVoo() : ?array
	{
		// Guzzle Client
		$client = new Client(['verify' => false]); //SSL diable for local testing

		$res = $client->get($this->vooApi.'/videos?vooKey='.$this->vooKey);
		//$res = $client->get('https://jsonplaceholder.typicode.com/todos/1');
			if ($res->getBody()) 
			{

			    $data = json_decode($res->getBody(),true);
			    $videos = $data["videos"]["data"] ?? null;
			    return $videos;

			}
			return null;
	}

	public function getURLsFromNames(array $names) : ?array
	{
		$videos = $this->getVideosFromVoo();
		$listUrls=[];

			foreach ($names as $name) {
				foreach ($videos as $video) {
					if ($video["name"] === $name) {
						$path_parts = pathinfo($video["url"]);
						// if it's an .mp4
						if (isset($path_parts['extension'])&&$path_parts['extension'] === 'mp4') { 
							array_push($listUrls, $video["url"]);
						}
						
						break;
					}
				}
			}

		return $listUrls;
	}

	public function addUrlToKnack(string $id_itin, string $url)
	{
		
		$path = $this->knackAPI."/object_8/records/".$id_itin;

		$headers = ['X-Knack-Application-Id' => $this->knackAppID,
					'X-Knack-REST-API-KEY' => $this->knackKey,
					'content-type' => 'application/json'];

		$data = ["field_1281" => $url];			
		$body = json_encode($data);
		

		$client = new Client(['verify' => false]); //SSL diable for local testing
		$request = new Request('PUT', $path, $headers, $body );
		$response = $client->send($request);

		if ($response->getBody()) 
			{
			    return $response->getBody();
			}
		return null;	

	}
}

