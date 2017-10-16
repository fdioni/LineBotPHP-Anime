<?php
namespace App;
class LineBot
{
    public function index(){
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
                    $message = $event['message']['text'];
                    $regex_1 = "/\/([^\s]+)(.*?)(?=\*|$)/";
                    $regex_2 = "/\/(anime|manga)(.*?)(?=\*|$)/";
                    preg_match_all($regex_1,$message,$n);
                    $command = strtolower($n[1][0]);
                    $input = $n[2][0];
                    if(strcmp($command, 'anime') == 0 || stcmp($command, 'manga') == 0){
                        
                        $anilist = new anilist();
                        if(is_numeric($input) === true){

                            $ani_res = $anilist->id($command, $input);

                            $imageMessageBuilder = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($ani_res['image_url_lge'],$ani_res['image_url_lge']);

                                if(isset($event['source']['groupId']) === TRUE){
                                    $bot->pushMessage($event['source']['groupId'], $imageMessageBuilder);
                                } else {
                                $bot->pushMessage($event['source']['userId'], $imageMessageBuilder);
                            }
                                $result = $bot->replyText($event['replyToken'], "Detail of [".$ani_res['series_type']."] ".$ani_res['title_romaji'].":\n\n".$final."\n\n".'karena keterbatasan untuk menampilkan data, lebih lengkap silakan akses: https://anilist.co/'.$n[1][0].'/'.$n[3][0]);

                            } else {
                                $ani_res = $anilist->search($n[1][0], $n[2][0]);

                                $result = $bot->replyText($event['replyToken'], "List of ".$n[1][0].":\n [ID NUMBER]:[MEDIA TYPE][ROMAJI TITLE]\n".$ani_res."\n".'for more detail please replay with /'.$n[1][0].' [ID NUMBER]');
                            }

                    }
                    else if(strcmp($command, 'help') == 0 ){
                        $result = $bot->replyText($event['replyToken'], "List of Help Command:\n/anime [title] : search anime based on title\n/anime [number] : show anime details based on ID\n/manga [title] : search manga based on title\n/manga [number] : show manga details based on ID\n/all [title] : search manga and anime based on title\n\nThis bot fork from https://github.com/dicodingacademy/SimpleLineBotPHP and modified by ShinDion (fdioni)\n\nAPI Provided by: \n- https://anilist.co \n- https://myanimelist.net/ \n\nThis Bot Line is meant for educational purposes (and just for fun) only");
                    }else if(strcmp($command, 'out') == 0){
                        if(isset($event['source']['groupId']) == TRUE){
                        $bot->replyText($event['replyToken'], "Terima kasih telah mengundang saya di Grup ini");
                        $result = $bot->leaveGroup($event['source']['groupId']);
                    }
                }else if(strcmp($command, 'all') == 0){
                    $anilist = new anilist();
                    $ani_res_anime = $anilist->search(anime, $input);
                    $ani_res_manga = $anilist->search(manga, $input);
                    $result = $bot->replyText($event['replyToken'], "List of Anime:\n [ID NUMBER]:[MEDIA TYPE][ROMAJI TITLE]\n".$ani_res_anime."\n\nfor more detail please replay with /manga [ID NUMBER]\n\n\nList of manga:\n [ID NUMBER]:[MEDIA TYPE][ROMAJI TITLE]\n".$ani_res_manga."\n\nfor more detail please replay with /manga [ID NUMBER]");
                }
                    // or we can use pushMessage() instead to send reply message
                    // $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
                    // $result = $bot->pushMessage($event['source']['userId'], $textMessageBuilder);

                    return $result->getHTTPStatus() . ' ' . $result->getRawBody();
                }
            }
        }
    }
}
?>