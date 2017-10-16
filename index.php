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
	
});

$app->run();
