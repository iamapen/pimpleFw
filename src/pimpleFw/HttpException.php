<?php
namespace pimpleFw;

class HttpException extends \RuntimeException {
    /**
     * HTTPステータスコード + メッセージ定義
     * @var string[]
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xml
     */
    private static $statuses = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates (Experimental)',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    );

    private $headers;
    private $statusMessage;

    public function __construct($code=null, $headers=null, $message=null) {
        if(!array_key_exists($code, static::$statuses)) {
            throw new \InvalidArgumentException(
                sprintf('The HTTP status code "%s" is not implemented.', $code)
            );
        }
        $code = $code ?: 500;
        $this->headers = $headers ?: [];
        $this->statusMessage = $this->buildStatusMessage($code);

        $message = $message ?: $this->statusMessage;
        parent::__construct($message, $code);
    }

    public function getHeaders() {
        return $this->headers;
    }
    public function getStatusMessage() {
        return $this->statusMessage;
    }
    private function buildStatusMessage($code) {
        return sprintf('%d %s', $code, static::$statuses[$code]);
    }
}
