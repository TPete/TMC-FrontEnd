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
     * @param RemoteException $exp
     * @param string          $host
     * @param Container       $container
     * @param Response        $response
     *
     * @return Response
     */
    public static function renderException(RemoteException $exp, $host, Container $container, Response $response)
    {
        return $container->view->render(
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
