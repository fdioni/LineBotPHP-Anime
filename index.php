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
echo "ok";
	foreach ($ani_res as $key => $value) {
		$input[] = $value['id'].':'.$value['title_romaji'].'<br />';
	}
	$final = implode('', $input);
	echo $final;
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
				if(strpos($event['message']['text'], '/anime') !== false){
						preg_match_all("/\/(anime)(\s*)(.*?)(?=\*|$)/",$event['message']['text'],$n);
						$anilist = new anilist();
						if(is_numeric($n[3][0]) === true){

							$ani_res = $anilist->id($n[1][0], $n[3][0]);
							$result = $bot->replyText($event['replyToken'], "Detail of [".$ani_res['media_type']."] ".$ani_res['title_romaji'].":\n [ID NUMBER]:[MEDIA TYPE][ROMAJI TITLE]\n".$ani_res."\n".'karena keterbatasan baris untuk data lebih lengkap silakan akses: https://anilist.co/'.$n[1][0].'/'.$n[3][0].);

						} else {
							$ani_res = $anilist->search($n[1][0], $n[3][0]);
							$result = $bot->replyText($event['replyToken'], "List of ".$n[1][0].":\n [ID NUMBER]:[MEDIA TYPE][ROMAJI TITLE]\n".$ani_res."\n".'for more detail please replay with /anime [ID NUMBER]');
						}

				}
				else if(strpos($event['message']['text'], '/manga') !== false){
						preg_match_all("/\/(manga)(\s*)(.*?)(?=\*|$)/",$event['message']['text'],$n);

						$anilist = new anilist();
						$ani_res = $anilist->search($n[1][0], $n[3][0]);

						$result = $bot->replyText($event['replyToken'], "List of ".$n[1][0].":\n [ID NUMBER]:[MEDIA TYPE][ROMAJI TITLE]\n".$ani_rest."\n".'for more detail please replay with /id [ID NUMBER]');
				}
				// or we can use pushMessage() instead to send reply message
				// $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($event['message']['text']);
				// $result = $bot->pushMessage($event['source']['userId'], $textMessageBuilder);

				return $result->getHTTPStatus() . ' ' . $result->getRawBody();
			}
		}
	}

});

// $app->get('/push/{to}/{message}', function ($request, $response, $args)
// {
// 	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
// 	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

// 	$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($args['message']);
// 	$result = $bot->pushMessage($args['to'], $textMessageBuilder);

// 	return $result->getHTTPStatus() . ' ' . $result->getRawBody();
// });

/* JUST RUN IT */
$app->run();
