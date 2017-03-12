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

        $genres = implode("\n", $result['genres']);
        $alt = implode("\n", $result['synonyms']);
        $datestart = DateTime::createFromFormat('Ymd', $result['start_date_fuzzy']);
        $dateend = DateTime::createFromFormat('Ymd', $result['end_date_fuzzy']);

        $input = array(
          '------MAIN INFORMATION------',
          '----------------------------',
          'Title English: '.$result['title_english'],
          'Title Japanese: '.$result['title_japanese'],
          'Alternative Title: '.$alt,
          'Airing Status: '.$result['airing_status'],
          'Start Date: '.$datestart->format('d/m/Y'),
          'End Date: '.$dateend->format('d/m/Y'),
          'Type: '.$result['type'],
          'Genre: '.$genres,
          'Akan ditambahkan nanti.... capekk'
        );
        $final = implode("\n", $input);

        return $final;
    }

}
