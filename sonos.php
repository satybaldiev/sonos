<?php
include_once 'client.php';

class Sonos
{
    private $id;
    private $access_token;
    private $key;

    function credentials($params)
    {
        if (isset($params->loginToken) && isset($params->loginToken->token)&& isset($params->loginToken->key)) {
            $this->access_token = $params->loginToken->token;
            $this->key = $params->loginToken->key;
            $this->id = $params->loginToken->householdId;
        }
    }
    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function getDeviceAuthToken($params)
    {
        if (file_exists($params->householdId.'.auth')){
            $response = new StdClass();
            $response->getDeviceAuthTokenResult->authToken = 'c788bbb6-89c8-4176-8879-ac04772367d3';
            $response->getDeviceAuthTokenResult->privateKey = '123412341234';
            return $response;
        }
        return null;
    }

    function getAppLink($params)
    {
        $response = new StdClass();
        $code  = $this->generateRandomString(8);
        $response->getAppLinkResult->authorizeAccount->appUrlStringId = 'SIGN IN';
        $response->getAppLinkResult->authorizeAccount->deviceLink->regUrl = 'https://sonos.axeldemos.com/register.php?id='.$params->householdId.'&code='.$code;
        $response->getAppLinkResult->authorizeAccount->deviceLink->linkCode = $code;
        $response->getAppLinkResult->authorizeAccount->deviceLink->showLinkCode = true;
        return $response;
    }

    function getLastUpdate()
    {
        $lastUpdate = getAccountLastUpdate($this->access_token,$this->key);
        $response = new StdClass();
        $response->getLastUpdateResult = new StdClass();
        $response->getLastUpdateResult->catalog = $lastUpdate;
        $response->getLastUpdateResult->favorites = $lastUpdate;
        $response->getLastUpdateResult->pollInterval = 30;
        $response->getLastUpdateResult->autoRefreshEnabled = true;
        return $response;
    }

    function getMetadata($params)
    {

        $count = $params->count;
        $id = $params->id;
        $index = $params->index;
        $total = 0;
        $mediaMetadata = getStreams($this->access_token, $this->key,$count,$index);

        $response = new StdClass();
        $response->getMetadataResult = new StdClass();
        $response->getMetadataResult->index = $index;
        $response->getMetadataResult->total = count($mediaMetadata);
        $response->getMetadataResult->count = $count;
        $response->getMetadataResult->mediaCollection = [];
        $response->getMetadataResult->mediaMetadata = $mediaMetadata;
        return $response;
    }

    function getMediaMetadata($params)
    {
        $id = $params->id;
        $response = new StdClass();
        $response->getMediaMetadataResult = getStreamById($this->access_token, $this->key,$id);
        return $response;
    }

    function getMediaURI($params)
    {
        $id = $params->id;
        $url= getStreamById($this->access_token, $this->key,$id);

        $response = new StdClass();
        $response->getMediaURIResult = $url->url;
        return $response;
    }
//
    function getExtendedMetadata($params)
    {

        $id = $params->id;

        $response = new StdClass();
        $response->getExtendedMetadataResult = new StdClass();
        $stream= getStreamById($this->access_token, $this->key,$id);

        if ($id == "root") {
        } elseif ($id == "active") {
        } elseif ($id == "podcasts") {
        } elseif (substr($id, 0, 1) == '+') {
            $activeEpisodeIDs = fetchAccount($this->sessionId)->episodeIDs;
            $response->getExtendedMetadataResult->mediaMetadata = $this->findEpisodeMediaMetadata(
                $id,
                in_array($id, $activeEpisodeIDs)
            );
        } else {
            $response->getExtendedMetadataResult->mediaCollection = $this->findPodcastMediaMetadata(
                $id
            );
        }

        $duration = microtime(true) - $start;
        error_log("SOAP getExtendedMetadata " . round($duration * 1000) . "ms");

        return $response;
    }
//
//    function createItem($params)
//    {
//        $start = microtime(true);
//
//        $id = $params->favorite;
//
//        $response = new StdClass();
//        $response->createItemResult = $id;
//
//        addEpisode($this->sessionId, $id);
//
//        $duration = microtime(true) - $start;
//        error_log("SOAP createItem " . round($duration * 1000) . "ms");
//
//        return $response;
//    }
//
//    function deleteItem($params)
//    {
//        $start = microtime(true);
//
//        $id = $params->favorite;
//
//        $response = new StdClass();
//
//        deleteEpisode($this->sessionId, $id);
//
//        $duration = microtime(true) - $start;
//        error_log("SOAP createItem " . round($duration * 1000) . "ms");
//
//        return $response;
//    }
//
//    function setPlayedSeconds($params)
//    {
//        $start = microtime(true);
//
//        $id = $params->id;
//        $offsetMillis = $params->offsetMillis;
//
//        if ($offsetMillis) {
//            updateEpisodeProgress($this->sessionId, $id, $offsetMillis / 1000);
//        }
//
//        $response = new StdClass();
//        $response->setPlayedSecondsResult = new StdClass();
//
//        $duration = microtime(true) - $start;
//        error_log("SOAP setPlayedSeconds " . round($duration * 1000) . "ms");
//
//        return $response;
//    }
//
//    function reportPlaySeconds($params)
//    {
//        $start = microtime(true);
//
//        $id = $params->id;
//        $offsetMillis = $params->offsetMillis;
//
//        if ($offsetMillis) {
//            updateEpisodeProgress($this->sessionId, $id, $offsetMillis / 1000);
//        }
//
//        $response = new StdClass();
//        $response->reportPlaySecondsResult = new StdClass();
//        $response->reportPlaySecondsResult->interval = 10;
//
//        $duration = microtime(true) - $start;
//        error_log("SOAP reportPlaySeconds " . round($duration * 1000) . "ms");
//
//        return $response;
//    }
//
//    function reportPlayStatus($params)
//    {
//        $start = microtime(true);
//
//        $id = $params->id;
//        $offsetMillis = $params->offsetMillis;
//
//        if ($offsetMillis) {
//            updateEpisodeProgress($this->sessionId, $id, $offsetMillis / 1000);
//        }
//
//        $response = new StdClass();
//        $response->reportPlayStatusResult = new StdClass();
//
//        $duration = microtime(true) - $start;
//        error_log("SOAP reportPlayStatus " . round($duration * 1000) . "ms");
//
//        return $response;
//    }
//
    function findPodcastMediaMetadata($id)
    {
        $media = new StdClass();
        $podcast = fetchPodcast($id);

        if (is_null($podcast)) {
            $media->id = $id;
            $media->itemType = "album";
            $media->displayType = "";
            $media->title = "Podcast not found";
            $media->canPlay = false;
            $media->canAddToFavorites = false;
            $media->containsFavorite = false;
        } else {
            $media->id = $podcast->id;
            $media->itemType = "album";
            $media->displayType = "";
            $media->title = $podcast->title;
            $media->albumArtURI = $podcast->imageURL;
            $media->canPlay = true;
            $media->canAddToFavorites = false;
            $media->containsFavorite = false;
        }

        return $media;
    }
//
//    function findEpisodeMediaMetadata($id, $favorite)
//    {
//        $media = new StdClass();
//        $episode = fetchEpisode($id);
//
//        if (is_null($episode)) {
//            $media->id = $id;
//            $media->itemType = "track";
//            $media->title = "Episode not found";
//            $media->mimeType = "audio/mp3";
//            $media->displayType = "";
//            $media->summary = "";
//            $media->trackMetadata = new StdClass();
//            $media->trackMetadata->canPlay = false;
//        } else {
//            $media->id = $episode->id;
//            $media->isFavorite = $favorite;
//            $media->displayType = "";
//            $media->mimeType = $episode->mimeType;
//            $media->itemType = "track";
//            $media->title = $episode->title;
//            $media->summary = "";
//            $media->trackMetadata = new StdClass();
//            $media->trackMetadata->canPlay = true;
//            $media->trackMetadata->canAddToFavorites = true;
//            $media->trackMetadata->albumArtURI = $episode->imageURL;
//            $media->trackMetadata->albumId = $episode->podcastId;
//            $media->trackMetadata->album = $episode->podcastTitle;
//
//            if (isset($episode->number)) {
//                $media->trackMetadata->trackNumber = $episode->number;
//            }
//
//            if (isset($episode->duration)) {
//                $media->trackMetadata->canResume = true;
//                $media->trackMetadata->duration = $episode->duration;
//            }
//        }
//
//        return $media;
//    }
}

