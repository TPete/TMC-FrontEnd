<?php

namespace TinyMediaCenter\FrontEnd;

use Slim\Container;
use Slim\Http\Response;

/**
 * Class Util
 */
class Util
{
    /**
     * @return string
     */
    public static function getHost()
    {
        $dir = $_SERVER["SCRIPT_NAME"];
        $dir = substr($dir, 0, strrpos($dir, "/"));
        $host = $_SERVER["HTTP_HOST"].$dir;

        return $host;
    }

    /**
     * @return array
     */
    public static function getConfig()
    {
        $config = self::readJSONFile("config.json");

        return $config;
    }

    /**
     * @param string $file
     *
     * @return array
     */
    public static function readJSONFile($file)
    {
        $fileData = file_get_contents($file);
        if (!mb_check_encoding($fileData, 'UTF-8')) {
            $fileData = utf8_encode($fileData);
        }
        $res = json_decode($fileData, true);

        return $res;
    }

    /**
     * @param string $file
     * @param mixed  $data
     *
     * @return bool
     */
    public static function writeJSONFile($file, $data)
    {
        $json = json_encode($data);
        $res = file_put_contents($file, $json);

        return ($res !== false);
    }

    /**
     * @param mixed  $var
     * @param string $default
     * @param bool   $toInt
     *
     * @return int|string
     */
    public static function initGET($var, $default = "", $toInt = false)
    {
        $res = isset($_GET[$var]) ? $_GET[$var] : $default;
        $res = trim($res);
        if ($toInt) {
            $res = intval($res, 10);
        }

        return $res;
    }

    /**
     * @param int    $offset
     * @param int    $cnt
     * @param string $sort
     * @param string $filter
     * @param array  $genres
     * @param int    $collection
     * @param int    $list
     *
     * @return array
     */
    public static function getPreviousLink($offset, $cnt, $sort, $filter, $genres, $collection, $list)
    {
        if ($offset > 0) {
            $offsetPrev = $offset - $cnt;
            if ($offsetPrev < 0) {
                $offsetPrev = 0;
            }
            if ($collection === 0 and $list === 0) {
                $tmp = [
                    "sort" => $sort,
                    "filter" => $filter,
                    "genres" => $genres,
                    "offset" => $offsetPrev,
                ];
            } else {
                if ($collection > 0) {
                    $tmp = [
                        "collection" => $collection,
                        "offset" => $offsetPrev,
                    ];
                }
                if ($list > 0) {
                    $tmp = [
                        "list" => $list,
                        "offset" => $offsetPrev,
                    ];
                }
            }
            $previous = http_build_query($tmp);
            $previousClass = "";
        } else {
            $previous = "javascript: void(0);";
            $previousClass = "disabled";
        }

        return ["link" => $previous, "class" => $previousClass];
    }

    /**
     * @param int    $offset
     * @param int    $cnt
     * @param int    $moviesCnt
     * @param string $sort
     * @param string $filter
     * @param array  $genres
     * @param int    $collection
     * @param int    $list
     *
     * @return array
     */
    public static function getNextLink($offset, $cnt, $moviesCnt, $sort, $filter, $genres, $collection, $list)
    {
        if ($offset + 2 * $cnt <= $moviesCnt) {
            $offsetNext = $offset + $cnt;
            if ($collection === 0 and $list === 0) {
                $tmp = [
                    "sort" => $sort,
                    "filter" => $filter,
                    "genres" => $genres,
                    "offset" => $offsetNext,
                ];
            } else {
                if ($collection > 0) {
                    $tmp = [
                        "collection" => $collection,
                        "offset" => $offsetNext,
                    ];
                }
                if ($list > 0) {
                    $tmp = [
                        "list" => $list,
                        "offset" => $offsetNext,
                    ];
                }
            }
            $next = http_build_query($tmp);
            $nextClass = "";
        } else {
            if ($moviesCnt - $cnt > $offset) {
                $offsetNext = $moviesCnt - $cnt;
                if ($collection === 0 and $list === 0) {
                    $tmp = [
                        "sort" => $sort,
                        "filter" => $filter,
                        "genres" => $genres,
                        "offset" => $offsetNext,
                    ];
                } else {
                    if ($collection > 0) {
                        $tmp = [
                            "collection" => $collection,
                            "offset" => $offsetNext,
                        ];
                    }
                    if ($list > 0) {
                        $tmp = [
                            "list" => $list,
                            "offset" => $offsetNext,
                        ];
                    }
                }
                $next = http_build_query($tmp);
                $nextClass = "";
            } else {
                $next = "javascript: void(0);";
                $nextClass = "disabled";
            }
        }

        return ["link" => $next, "class" => $nextClass];
    }

    /**
     * @param RemoteException $exp
     * @param string          $host
     * @param Container       $container
     * @param Response        $response
     */
    public static function renderException(RemoteException $exp, $host, Container $container, Response $response)
    {
        $container->view->render(
            $response,
            'error/page.html.twig',
            [
                'host'           => $host,
                'header'         => 'Error',
                'showEditButton' => false,
                "message"        => $exp->getMessage(),
                "trace"          => $exp->getStackTrace(),
            ]
        );
    }
}
