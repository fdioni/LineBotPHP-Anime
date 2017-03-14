<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/anime/anilist.php';

use \LINE\LINEBot\SignatureValidator as SignatureValidator;

// load config
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// initiate app
$configs =  [
	'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);

/* ROUTES */
$app->get('/', function ($request, $response) {
return 'ok';
});

$app->get('/anilist/{series_type}/search/{input}', function ($request, $response, $args) {
	$anilist = new anilist();
	$ani_res = $anilist->search($args['series_type'], $args['input']);
//echo "ok";
	return $ani_res;
});

$app->get('/anilist/{series_type}/id/{input}', function ($request, $response, $args) {
	$anilist = new anilist();
	$ani_res = $anilist->id($args['series_type'], $args['input']);
//echo "ok";

	$genres = implode(",", $ani_res['genres']);
	$alt = implode(",", $ani_res['synonyms']);
	if(empty($ani_res['start_date_fuzzy'])){
		$datestart = '';
	}else{
	$datestart = DateTime::createFromFormat('Ymd', $ani_res['start_date_fuzzy'])->format('d/m/Y');
	}
	if(empty($ani_res['end_date_fuzzy'])){
		$dateend = '';
	}else{
	$dateend = DateTime::createFromFormat('Ymd', $ani_res['end_date_fuzzy'])->format('d/m/Y');
	}
	if (array_key_exists('airing_status', $ani_res)) {
    $status= 'Airing Status: '.$ani_res['airing_status'];
} else {
	$status= 'Publishing Status: '.$ani_res['publishing_status'];
}

	$input = array(
		'Title English: '.$ani_res['title_english'],
		'Title Japanese: '.$ani_res['title_japanese'],
		'Alternative Title: '.$alt,
		$status,
		'Start Date: '.$datestart,
		'End Date: '.$dateend,
		'Type: '.$ani_res['type'],
		'Genre: '.$genres,
		'Akan ditambahkan nanti.... capekk'
	);
	$final = implode("\n", $input);

		return $final;
});

$app->post('/', function ($request, $response)
{
	// get request body and line signature header
	$body 	   = file_get_contents('php://input');
	$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'];

	// log body and signature
	file_put_contents('php://stderr', 'Body: '.$body);

	// is LINE_SIGNATURE exists in request header?
	if (empty($signature)){
		return $response->withStatus(400, 'Signature not set');
	}

	// is this request comes from LINE?
	if($_ENV['PASS_SIGNATURE'] == false && ! SignatureValidator::validateSignature($body, $_ENV['CHANNEL_SECRET'], $signature)){
		return $response->withStatus(400, 'Invalid signature');
	}

	// init bot
	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

	$data = json_decode($body, true);
	foreach ($data['events'] as $event)
	{
		if ($event['type'] == 'message')
		{
			if($event['message']['type'] == 'text')
			{
				// send same message as reply to user
				//$result = $bot->replyText($event['replyToken'], $event['message']['text']);
				if(strpos($event['message']['text'], '/anime') !== false || strpos($event['message']['text'], '/manga') !== false){
						preg_match_all("/\/(anime|manga)(\s*)(.*?)(?=\*|$)/",$event['message']['text'],$n);
						$anilist = new anilist();
						if(is_numeric($n[3][0]) === true){

							$ani_res = $anilist->id($n[1][0], $n[3][0]);

							$genres = implode(", ", $ani_res['genres']);
							$alt = implode(", ", $ani_res['synonyms']);
							if(empty($ani_res['start_date_fuzzy'])){
								$datestart = '';
							}else{
							$datestart = DateTime::createFromFormat('Ymd', $ani_res['start_date_fuzzy'])->format('d/m/Y');
							}
							if(empty($ani_res['end_date_fuzzy'])){
								$dateend = '';
							}else{
							$dateend = DateTime::createFromFormat('Ymd', $ani_res['end_date_fuzzy'])->format('d/m/Y');
							}

							$input = array(
								'ID: '.$ani_res['id'],
								'Title Romaji : '.$ani_res['title_romaji'],
								'Title English: '.$ani_res['title_english'],
								'Title Japanese: '.$ani_res['title_japanese'],
								'Alternative Title: '.$alt
							);
											if (array_key_exists('airing_status', $ani_res)) {
												array_push($input, 'Airing Status: '.$ani_res['airing_status'],
																						'Total Episodes: '.$ani_res['total_episodes'],
																						'Source: '.$ani_res['source'],
																						'Source: '.$ani_res['duration']
																	);
										} else {
											array_push($input,
											'Publishing Status: '.$ani_res['publishing_status'],
											'Total Chapters: '.$ani_res['total_chapters'],
											'Total Chapters: '.$ani_res['total_volumes']
										);
										}

										array_push($input,
											'Type: '.$ani_res['type'],
								      'Start Date: '.$datestart,
								      'End Date: '.$dateend,
								      'Genre: '.$genres,
											'Description: '.$ani_res['description']
									);

							        $final = implode("\n", $input);

							$imageMessageBuilder = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($ani_res['image_url_lge'],$ani_res['image_url_lge']);

							if(isset($event['source']['groupId']) === TRUE){
								$bot->pushMessage($event['source']['groupId'], $imageMessageBuilder);
							} else {
							$bot->pushMessage($event['source']['userId'], $imageMessageBuilder);
						}
							$result = $bot->replyText($event['replyToken'], "Detail of [".$ani_res['series_type']."] ".$ani_res['title_romaji'].":\n\n".$final."\n\n".'karena keterbatasan untuk menampilkan data, lebih lengkap silakan akses: https://anilist.co/'.$n[1][0].'/'.$n[3][0]);

						} else {
							$ani_res = $anilist->search($n[1][0], $n[3][0]);

							$result = $bot->replyText($event['replyToken'], "List of ".$n[1][0].":\n [ID NUMBER]:[MEDIA TYPE][ROMAJI TITLE]\n".$ani_res."\n".'for more detail please replay with /'.$n[1][0].' [ID NUMBER]');
						}

				}
				else if(strpos($event['message']['text'], '/help') !== false){
					$result = $bot->replyText($event['replyToken'], "List of Help Command:\n/anime [title] : search anime based on title\n/anime [number] : show anime details based on ID\n/manga [title] : search manga based on title\n/manga [number] : show manga details based on ID\n/manga [title] : search manga and anime based on title\n\nThis bot fork from https://github.com/dicodingacademy/SimpleLineBotPHP and modified by ShinDion (fdioni)\n\nAPI Provided by: \n- https://anilist.co \n- https://myanimelist.net/ \n\nThis Bot Line is meant for educational purposes (and just for fun) only");
				}else if(strpos($event['message']['text'], '/out') !== false){
					if(isset($event['source']['groupId']) == TRUE){
					$bot->replyText($event['replyToken'], "Terima kasih telah mengundang saya di Grup ini");
					$result = $bot->leaveGroup($event['source']['groupId']);
				}
			}else if(strpos($event['message']['text'], '/all') !== false){
				preg_match_all("/\/(all)(\s*)(.*?)(?=\*|$)/",$event['message']['text'],$n);
				$anilist = new anilist();
				$ani_res_anime = $anilist->search(anime, $n[3][0]);
				$ani_res_manga = $anilist->search(manga, $n[3][0]);
				$result = $bot->replyText($event['replyToken'], "List of Anime:\n [ID NUMBER]:[MEDIA TYPE][ROMAJI TITLE]\n".$ani_res_anime."\nfor more detail please replay with /manga [ID NUMBER]\n\n\nList of manga:\n [ID NUMBER]:[MEDIA TYPE][ROMAJI TITLE]\n".$ani_res_manga."\nfor more detail please replay with /manga [ID NUMBER]");
			}
				// or we can use pushMessage() instead to send reply message
				// $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($event['message']['text']);
				// $result = $bot->pushMessage($event['source']['userId'], $textMessageBuilder);

				return $result->getHTTPStatus() . ' ' . $result->getRawBody();
			}
		}
	}

});

$app->run();
