<?php
/**
 * Doc: https://anilist-api.readthedocs.io/en/latest/index.html
 * url: https://anilist.co/api/
 *
 */
class Anilist
{
    private $url = "https://anilist.co/api/";
  /**
   * POST: auth/access_token
   */
    private function auth()
    {
        //defined all variable needed
        $sub_url="auth/access_token";
        $fields= array(
        'grant_type' => "client_credentials",
        'client_id' =>  "tanyanime-5wk5u",
        'client_secret' =>  "ZU7dOga6KbeXMu5HYyIcMq",
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

        return $result;
    }
}