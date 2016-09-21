<?php

namespace TinyMediaCenter\FrontEnd;

/**
 * Class RemoteException
 */
class RemoteException extends \Exception
{
    /**
     * @var array
     */
    private $trace;

    /**
     * RemoteException constructor.
     *
     * @param string $message
     * @param array  $trace
     */
    public function __construct($message, array $trace)
    {
        parent::__construct($message);
        $this->message = $message;
        $this->trace   = $trace;
    }

    /**
     * @return array
     */
    public function getStackTrace()
    {
        return $this->trace;
    }
}
