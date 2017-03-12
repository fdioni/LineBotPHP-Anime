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
	$anilist = new anilist();
	return $anilist->auth();
});

$app->get('/anilist/{series_type}/search/{input}', function ($request, $response, $args) {
	$anilist = new anilist();
	$ani_res = $anilist->search($args['series_type'], $args['input']);

	foreach ($ani_res as $key => $value) {
		$input[] = $value['id'].':'.$value['title_romaji'].'<br />';
	}
	echo implode('', $input);
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
				if(strpos($event['message']['text'], '/anime') === true){
						preg_match_all("/\/(anime)\s*(.*?)(?=\*|$)/",$event['message']['text'],$n);

						$anilist = new anilist();
						$ani_res = $anilist->search($n[1], $n[2]);

						foreach ($ani_res as $key => $value) {
							$input[] = $value['id'].':'.$value['title_romaji'].'<br />';
						}
						$final = implode('', $input);

						$result = $bot->replyText($event['replyToken'], "List of ".$n[1].":<br /> [ID NUMBER]:[ROMAJI TITLE]".$final.'for more detail please replay with /id [ID NUMBER]');
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
