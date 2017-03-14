<?php
/**
 * Doc: https://anilist-api.readthedocs.io/en/latest/index.html
 * url: https://anilist.co/api/
 *
 */

require __DIR__ . '../../vendor/autoload.php';
class Anilist
{

    private $url = "https://anilist.co/api/";
  /**
   * POST: auth/access_token
   */
    private function auth()
    {
      $dotenv = new Dotenv\Dotenv(__DIR__.'../../');
      $dotenv->load();
        //defined all variable needed
        $sub_url="auth/access_token";
        $fields= array(
        'grant_type' => "client_credentials",
        'client_id' =>  $_ENV['ANILIST_CLIENT_ID'],
        'client_secret' => $_ENV['ANILIST_CLIENT_SECRET'],
      );

      //open connection
      $ch = curl_init();

      //set the url, number of POST vars, POST data
      curl_setopt($ch, CURLOPT_URL, $this->url.$sub_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


      //execute post
      $result = json_decode(curl_exec($ch), TRUE);

      //close connection
      curl_close($ch);

      return $result;
    }

    public function search($series_type, $title)
    {
        //Get Client Credentials
        $auth = $this->auth();
        $access_token = $auth['access_token'];
        //Set the sub Url
        $sub_url=$series_type."/search/".urlencode($title);

        //Set Header for cURL
        //$headers =array();
        $headers[] = 'Authorization: Bearer '.$access_token;
        //open connection
        $ch = curl_init();

        //set the url
        curl_setopt($ch, CURLOPT_URL, $this->url.$sub_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = json_decode(curl_exec($ch), TRUE);
        //print_r($result);
        curl_close($ch);

        foreach ($result as $key => $value) {
          $input[] = $value['id'].' : '.'['.$value['type'].'] '.$value['title_romaji'];
        }

        $final = implode("\n", $input);
        return $final;
    }
    public function id($series_type, $id)
    {
        //Get Client Credentials
        $auth = $this->auth();
        $access_token = $auth['access_token'];
        //Set the sub Url
        $sub_url=$series_type."/".$id;

        //Set Header for cURL
        //$headers =array();
        $headers[] = 'Authorization: Bearer '.$access_token;
        //open connection
        $ch = curl_init();

        //set the url
        curl_setopt($ch, CURLOPT_URL, $this->url.$sub_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = json_decode(curl_exec($ch), TRUE);
        //print_r($result);
        curl_close($ch);

        return $result;
    }

}
