<?php

namespace TinyMediaCenter\FrontEnd;

/**
 * Class RestApi
 */
class RestApi
{
    /**
     * @var string
     */
    private $baseUrl;

    /**
     * RestAPI constructor.
     *
     * @param string $baseUrl
     */
    public function __construct($baseUrl)
    {
        if (substr($baseUrl, -1) === '/') {
            $baseUrl = substr($baseUrl, 0, strlen($baseUrl) - 1);
        }

        $this->baseUrl = $baseUrl;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $url = "/config/";
        $resp = $this->checkUrl($url);

        return $resp;
    }

    /**
     * @throws RemoteException
     *
     * @return mixed
     */
    public function getConfig()
    {
        $url = "/config/";
        $config = $this->curlDownload($url);

        return $config;
    }

    /**
     * @param array $config
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function updateConfig($config)
    {
        $url = "/config/";
        $res = $this->curlPost($url, $config);

        return $res;
    }

    /**
     * @param string $type
     * @param array  $args
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function check($type, $args)
    {
        $url = "/config/check/".$type."/";
        $res = $this->curlDownload($url, $args);

        return $res;
    }

    /**
     * @throws RemoteException
     *
     * @return mixed
     */
    public function setupDB()
    {
        $url = "/config/db/";
        $res = $this->curlPost($url);

        return $res;
    }

    /**
     * @deprecated
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function getCategories()
    {
        $url = "/categories/";
        $res = $this->curlDownload($url);

        return $res['data'];
    }

    /**
     * Get categories for the series area.
     *
     * @throws RemoteException
     *
     * @return array
     */
    public function getSeriesCategories()
    {
        $url = "/areas/series/categories/";
        $res = $this->curlDownload($url);
        $res = array_map(function (array $row) {
            return $row['id'];
        }, $res);

        return $res;
    }

    /**
     * Get categories for the movies area.
     *
     * @throws RemoteException
     *
     * @return array
     */
    public function getMovieCategories()
    {
        $url = "/areas/movies/categories/";
        $res = $this->curlDownload($url);
        $res = array_map(function (array $row) {
            return $row['id'];
        }, $res);

        return $res;
    }

    /**
     * @throws RemoteException
     *
     * @return mixed
     */
    public function updateShows()
    {
        return $this->curlPost("/areas/series/maintenance/", "", 720);
    }

    /**
     * @param string $category
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function getCategoryOverview($category)
    {
        return $this->curlDownload(sprintf("/areas/series/categories/%s/", $category));
    }

    /**
     * @param string $category
     * @param int    $showId
     *
     * @return mixed
     *@throws RemoteException
     *
     */
    public function getShowDetails($category, $showId)
    {
        return $this->curlDownload(sprintf("/areas/series/categories/%s/entries/%s/", $category, $showId));
    }

    /**
     * @param string $category
     * @param int    $showId
     * @param string $title
     * @param string $tvdbId
     * @param string $lang
     *
     * @return mixed
     *@throws RemoteException
     *
     */
    public function updateShowDetails($category, $showId, $title, $tvdbId, $lang)
    {
        $url = sprintf("/shows/categories/%s/shows/%s/", $category, $showId);
        $args = [
            "title"  => $title,
            "tvdbId" => $tvdbId,
            "lang"   => $lang,
        ];
        $result = $this->curlPost($url, $args);

        return $result;
    }

    /**
     * @param string $category
     * @param int    $showId
     * @param int    $episodeId
     *
     * @return mixed
     * @throws RemoteException
     *
     */
    public function getEpisodeDescription($category, $showId, $episodeId)
    {
        $url = sprintf("/shows/categories/%s/shows/%s/episodes/%s/", $category, $showId, $episodeId);
        $description = $this->curlDownload($url);

        return $description;
    }

    /**
     * @param int $id
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function lookupMovie($id)
    {
        return $this->curlDownload(sprintf("/areas/movies/lookup/%s/", $id));
    }

    /**
     * @throws RemoteException
     *
     * @return mixed
     */
    public function updateMovies()
    {
        return $this->curlPost("/areas/movies/maintenance/", "", 720);
    }

    /**
     * @param string $category
     * @param string $sort
     * @param string $cnt
     * @param int    $offset
     * @param string $filter
     * @param string $genres
     * @param string $collection
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function getMovies($category, $sort, $cnt, $offset, $filter = "", $genres = "", $collection = "0")
    {
        return $this->curlDownload(
            sprintf("/areas/movies/categories/%s/", $category),
            [
                "sort"       => $sort,
                "count"        => $cnt,
                "offset"     => $offset,
                "filter"     => $filter,
                "genre"      => $genres,
                "collection" => $collection,
            ]
        );
    }

    /**
     * @param string $category
     * @param int    $movieId
     *
     * @return mixed
     * @throws RemoteException
     *
     */
    public function getMovie($category, $movieId)
    {
        return $this->curlDownload(sprintf("/areas/movies/categories/%s/movies/%s/", $category, $movieId));
    }

    /**
     * @param string $category
     * @param int    $localId
     * @param string $remoteId
     * @param string $filename
     *
     * @throws RemoteException
     *
     *@return mixed
     */
    public function updateMovie($category, $localId, $remoteId, $filename)
    {
        return $this->curlPost(
            sprintf("/areas/movies/categories/%s/movies/%s/", $category, $localId),
            [
                "remoteId" => $remoteId,
                "filename"  => $filename,
            ]
        );
    }

    /**
     * @param string $category
     * @param string $term
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function getGenres($category, $term = '')
    {
        return $this->curlDownload(sprintf("/areas/movies/categories/%s/genres/", $category), ["term" => $term]);
    }

    /**
     * @param string $category
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function getCollections($category)
    {
        return $this->curlDownload(sprintf("/areas/movies/categories/%s/collections/", $category));
    }

    /**
     * @param array $e
     * @throws RemoteException
     */
    private function raiseException($e)
    {
        throw new RemoteException($e["error"], $e["trace"]);
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function getCompleteUrl($url)
    {
        if (substr($url, 0, 1) === '/') {
            $url = substr($url, 1);
        }

        return sprintf('%s/%s', $this->baseUrl, $url);
    }

    /**
     * @param string $url
     * @param array  $args
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    private function curlDownload($url, $args = [])
    {
        if (!function_exists('curl_init')) {
            die('Sorry cURL is not installed!');
        }

        $url = $this->getCompleteUrl($url);

        $queryString = "?";
        foreach ($args as $argName => $argVal) {
            $queryString .= $argName."=".urlencode($argVal)."&";
        }
        $queryString = substr($queryString, 0, strlen($queryString) - 1);
        $url .= $queryString;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);

        if ($output === false) {
            $this->raiseException([
                "error" => "Call to API failed: ".curl_error($ch),
                "trace" => [],
            ]);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode >= 400) {
            curl_close($ch);
            $this->raiseException([
                "error" => "Call to API failed: ".$httpCode,
                "trace" => [],
            ]);
        }

        curl_close($ch);
        $response = json_decode($output, true);
        if (isset($response["error"])) {
            $this->raiseException($response);
        }

        return $response;
    }

    /**
     * @param string $url
     * @param string $args
     * @param int    $timeout
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    private function curlPost($url, $args = "", $timeout = 10)
    {
        if (!function_exists('curl_init')) {
            die('Sorry cURL is not installed!');
        }

        $url = $this->getCompleteUrl($url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $output = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($output, true);
        if (isset($response["error"])) {
            $this->raiseException($response);
        }

        return $response;
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    private function checkUrl($url)
    {
        if (!function_exists('curl_init')) {
            die('Sorry cURL is not installed!');
        }

        $url = $this->getCompleteUrl($url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);

        if ($output === false) {
            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode < 400);
    }
}
