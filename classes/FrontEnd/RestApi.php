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
     * @throws RemoteException
     *
     * @return mixed
     */
    public function getCategories()
    {
        $url = "/categories/";
        $res = $this->curlDownload($url);

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
     * @param string $category
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function getCategoryOverview($category)
    {
        $url = "/shows/".$category."/";
        $list = $this->curlDownload($url);

        return $list;
    }

    /**
     * @param string $category
     * @param int    $id
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function getShowDetails($category, $id)
    {
        $url = "/shows/".$category."/".$id."/";
        $details = $this->curlDownload($url);

        return $details;
    }

    /**
     * @param string $category
     * @param int    $id
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function getEpisodeDescription($category, $id)
    {
        $url = "/shows/".$category."/episodes/".$id."/";
        $description = $this->curlDownload($url);

        return $description;
    }

    /**
     * @param string $category
     * @param int    $id
     * @param string $title
     * @param string $tvdbId
     * @param string $lang
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function updateShowDetails($category, $id, $title, $tvdbId, $lang)
    {
        $url = "/shows/".$category."/edit/".$id."/";
        $args = [
            "title"  => $title,
            "tvdbId" => $tvdbId,
            "lang"   => $lang,
        ];
        $result = $this->curlPost($url, $args);

        return $result;
    }

    /**
     * @throws RemoteException
     *
     * @return mixed
     */
    public function updateShows()
    {
        $url = "/shows/maintenance/";
        $result = $this->curlPost($url, "", 720);

        return $result;
    }

    /**
     * @param string $category
     * @param int    $id
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function getMovie($category, $id)
    {
        $url = "/movies/".$category."/".$id."/";
        $res = $this->curlDownload($url);

        return $res;
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
        $url = "/movies/lookup/".$id."/";
        $res = $this->curlDownload($url);

        return $res;
    }

    /**
     * @param string $category
     * @param string $sort
     * @param string $cnt
     * @param int    $offset
     * @param string $filter
     * @param string $genres
     * @param string $collection
     * @param string $list
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function getMovies($category, $sort, $cnt, $offset, $filter = "", $genres = "", $collection = "0", $list = "0")
    {
        $url = "/movies/".$category."/";
        $args = [
            "sort"       => $sort,
            "cnt"        => $cnt,
            "offset"     => $offset,
            "filter"     => $filter,
            "genre"      => $genres,
            "collection" => $collection,
            "list"       => $list,
        ];
        $res = $this->curlDownload($url, $args);

        return $res;
    }

    /**
     * @param string $category
     * @param int    $dbId
     * @param string $movieDbId
     * @param string $filename
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function updateMovie($category, $dbId, $movieDbId, $filename)
    {
        $url = "/movies/".$category."/".$dbId."/";
        $args = [
            "movieDbId" => $movieDbId,
            "filename"  => $filename,
        ];

        $res = $this->curlPost($url, $args);

        return $res;
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
        $url = "/movies/".$category."/genres/";
        $args = ["term" => $term];

        $res = $this->curlDownload($url, $args);

        return $res;
    }

    /**
     * @param string $category
     *
     * @throws RemoteException
     *
     * @return mixed
     */
    public function getCompilations($category)
    {
        $url = "/movies/".$category."/compilations/";
        $res = $this->curlDownload($url);

        return $res;
    }

    /**
     * @throws RemoteException
     *
     * @return mixed
     */
    public function updateMovies()
    {
        $url = "/movies/maintenance/";
        $result = $this->curlPost($url, "", 720);

        return $result;
    }

    /**
     * @param array $e
     * @throws RemoteException
     */
    private function raiseException($e)
    {
        $exp = new RemoteException($e["error"], $e["trace"]);
        throw $exp;
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
