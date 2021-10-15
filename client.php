<?php

function encrypt($data, $key)
{
    $iv = openssl_random_pseudo_bytes(16);
    return $iv . ":" . openssl_encrypt($data, 'AES-256-CBC', $key, true, $iv);
}

function decrypt($data, $key)
{
    $iv = substr($data, 0, 16);
    return openssl_decrypt(substr($data, 17), 'AES-256-CBC', $key, true, $iv);
}

class Podcast
{
    public $id;
    public $title;
    public $imageURL;
    public $episodeIDs;
    public $episodeDurations;
}

class Episode
{
    public $id;
    public $number;
    public $podcastId;
    public $podcastTitle;
    public $title;
    public $description;
    public $date;
    public $imageURL;
    public $duration;
    public $mimeType;
    public $url;
    public $itemId;
    public $speedId;
}

function fetch($url, $token = null)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    if ($token) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Cookie: o=$token"]);
    }

    $body = curl_exec($ch);

    curl_close($ch);

    return $body;
}

function getAccountLastUpdate($token,$key)
{
    return sha1(serialize(getStreams($token,$key)));
}

function fetchAccount($token,$key)
{
    $result = new StdClass();
    $result->streamsIDs = [];
    $result->podcastIDs = [];
    $body = fetch("https://easyonholdcloud.com/api/v1/brandistreams?token=".$token."&id=".$key);
    foreach (json_decode($body) as $item){
        $result->streamsIDs[] = $item->id;
    }
    return $result;
}


function getStreams($token,$key,$count = 100,$index = 0)
{
    $body = fetch("https://easyonholdcloud.com/api/v1/brandistreams?token=".$token."&id=".$key);
    $items = json_decode($body);
    $mediaMetadata = [];
    foreach ($items as $item){
        $current= new StdClass();
        $current->id = $item->id;
        $current->title = $item->name;
        $current->url = $item->url;
        $current->mimeType = 'audio/mpeg';
        $current->itemType = 'stream';
        $current->streamMetadata->logo = 'https://easyonholdcloud.com/assets/img/Logo_blue.png';
        $mediaMetadata[] = $current;
    }
    return $mediaMetadata;
}
function getStreamById($token,$key,$id, $count = 100,$index = 0)
{
    $streams = getStreams($token,$key);
    foreach ($streams as $stream){
        if ($stream->id == $id){
            return $stream;
        }
    }
    return null;
}
function fetchStream($id)
{

    if (substr($id, 0, 1) == '+') {
        throw new Exception("invalid podcast id");
    }

    $body = fetch("https://overcast.fm/" . $id);

    preg_match('/extendedepisodecell/', $body, $matches);
    if (!isset($matches[0])) {
        return null;
    }

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($body);

    $xpath = new DOMXpath($dom);

    $podcast = new Podcast();
    $podcast->id = $id;

    $podcast->title = $xpath->query('//h2[@class="centertext"]')[0]->textContent;

    $url = $xpath->query('//img[@class="art fullart"]')[0]->getAttribute('src');
    $params = array();
    parse_str(parse_url($url, PHP_URL_QUERY), $params);
    $podcast->imageURL = $params['u'];

    $podcast->episodeIDs = [];
    foreach (
        $xpath->query('//a[contains(@class, "extendedepisodecell")]')
        as $a
    ) {
        $id = substr($a->getAttribute('href'), 1);
        $podcast->episodeIDs[] = $id;

        $caption = $xpath->query('.//div[@class="caption2 singleline"]', $a)[0]
            ->textContent;
        preg_match('/(\d+) min/', $caption, $matches);
        if (isset($matches[0])) {
            $podcast->episodeDurations[$id] = ((int)$matches[1] * 60);
        }
    }

    return $podcast;
}
function fetchPodcast($id)
{

    if (substr($id, 0, 1) == '+') {
        throw new Exception("invalid podcast id");
    }

    $body = fetch("https://overcast.fm/" . $id);

    preg_match('/extendedepisodecell/', $body, $matches);
    if (!isset($matches[0])) {
        return null;
    }

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($body);

    $xpath = new DOMXpath($dom);

    $podcast = new Podcast();
    $podcast->id = $id;

    $podcast->title = $xpath->query('//h2[@class="centertext"]')[0]->textContent;

    $url = $xpath->query('//img[@class="art fullart"]')[0]->getAttribute('src');
    $params = array();
    parse_str(parse_url($url, PHP_URL_QUERY), $params);
    $podcast->imageURL = $params['u'];

    $podcast->episodeIDs = [];
    foreach (
        $xpath->query('//a[contains(@class, "extendedepisodecell")]')
        as $a
    ) {
        $id = substr($a->getAttribute('href'), 1);
        $podcast->episodeIDs[] = $id;

        $caption = $xpath->query('.//div[@class="caption2 singleline"]', $a)[0]
            ->textContent;
        preg_match('/(\d+) min/', $caption, $matches);
        if (isset($matches[0])) {
            $podcast->episodeDurations[$id] = ((int)$matches[1] * 60);
        }
    }

    return $podcast;
}

function fetchEpisode($id)
{
    $body = fetch("https://overcast.fm/" . $id);

    preg_match('/audioplayer/', $body, $matches);
    if (!isset($matches[0])) {
        return null;
    }

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($body);

    $xpath = new DOMXpath($dom);

    $episode = new Episode();
    $episode->id = $id;

    $episode->podcastId = substr(
        $xpath->query('//div[@class="centertext"]/h3/a')[0]->getAttribute('href'),
        1
    );

    $podcast = fetchPodcast($episode->podcastId);

    if (empty($podcast->episodeDurations[$id])) {
        $podcast = fetchPodcast($episode->podcastId);
    }

    if (isset($podcast->episodeDurations[$id])) {
        $episode->duration = $podcast->episodeDurations[$id];
    }
    $episode->podcastTitle = $podcast->title;

    $episode->title = $xpath->query(
        '//div[@class="centertext"]/h2'
    )[0]->textContent;
    $episode->description = $xpath
        ->query('//meta[@name="og:description"]')[0]
        ->getAttribute('content');

    $dateEl = $xpath->query('//div[@class="centertext"]/div');
    if (isset($dateEl[0])) {
        $episode->date = strftime('%Y-%m-%d', strtotime($dateEl[0]->textContent));
    }

    preg_match('/^#?(\d+)\s*(:|-|–|—)?\s+/', $episode->title, $matches);
    if (isset($matches[0])) {
        $episode->title = substr($episode->title, strlen($matches[0]));
        $episode->number = (int)$matches[1];
    }

    $url = $xpath->query('//meta[@name="og:image"]')[0]->getAttribute('content');
    $params = array();
    parse_str(parse_url($url, PHP_URL_QUERY), $params);
    $episode->imageURL = $params['u'];

    $audio = $xpath->query('//audio')[0];
    $episode->itemId = $audio->getAttribute('data-item-id');
    $episode->speedId = $audio->getAttribute('data-speed-id');

    $source = $xpath->query('//audio/source')[0];
    $episode->mimeType = $source->getAttribute('type');
    $episode->url = $source->getAttribute('src');

    return $episode;
}

function addEpisode($token, $id)
{
    updateEpisodeProgress($token, $id, 0);
}

function deleteEpisode($token, $id)
{
    $episode = fetchEpisode($id);
    fetch("https://overcast.fm/podcasts/delete_item/" . $episode->itemId, $token);
}

function fetchEpisodeProgress($token, $id)
{

    $body = fetch("https://overcast.fm/" . $id, $token);

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($body);

    $xpath = new DOMXpath($dom);

    $audio = $xpath->query('//audio')[0];

    $itemId = $audio->getAttribute('data-item-id');
    $speedId = (int)$audio->getAttribute('data-speed-id');
    $version = (int)$audio->getAttribute('data-sync-version');
    $position = (int)$audio->getAttribute('data-start-time');

    $progress = new StdClass();
    $progress->itemId = $itemId;
    $progress->speedId = $speedId;
    $progress->version = $version;
    $progress->position = $position;

    $key = "overcast:fetchEpisodeProgress:" . sha1("$token:$id");

    return $progress;
}

function updateEpisodeProgress($token, $id, $position)
{

    $episode = fetchEpisode($id);

    if (isset($episode->duration) && $position >= $episode->duration) {
        $position = 2147483647;
    }

    $progress = fetchEpisodeProgress($token, $id);

    $ch = curl_init();

    curl_setopt(
        $ch,
        CURLOPT_URL,
        "https://overcast.fm/podcasts/set_progress/" . $episode->itemId
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Cookie: o=$token"]);
    curl_setopt($ch, CURLOPT_POST, 2);
    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        http_build_query(array(
            'speed' => '' . $progress->speedId,
            'v' => '' . $progress->version,
            'p' => '' . $position
        ))
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    $version = curl_exec($ch);

    curl_close($ch);

    $progress->version = (int)$version;
    $progress->position = (int)$position;

    $key = "overcast:fetchEpisodeProgress:" . sha1("$token:$id");
}

function login($username, $password)
{
    if ($username == 'acme')
        return ['success' => true, 'access_token' => 'c788bbb6-89c8-4176-8879-ac04772367d3', 'id' => '123412341234'];
    return ['success' => false];
}

function followRedirects($url)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_exec($ch);

    $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

    curl_close($ch);

    return $url;
}

?>
