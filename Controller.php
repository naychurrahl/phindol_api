<?php

require_once __DIR__ . '/Functions.php';

class Controller
{
    private $action;

    private $functions;
    
    private $method;
    
    private $param;
    
    private $requestBody;
    
    private $route;

    public function __construct(array $path, string $method)
    {
        $this->route  = $path[0] ?? null;
        $this->action = @urldecode($path[1] ?? null);
        $this->param  = @urldecode($path[2]) ?? null;
        
        $this->method = $method;

        $this -> functions = new Functions();

        //Get request body
        switch(True){
            case ! empty($_POST):
                $this -> requestBody = $_POST;
                break;

            case ! empty($_GET):
                $this -> requestBody = $_GET;
                break;

            default:
                $this -> requestBody = (array) json_decode(file_get_contents("php://input"), true);
        }

        $this->handle();
    }

    private function handle()
    {

        //echo (json_encode($this -> route));
        switch ($this->route) {

            case 'ping':
                $this -> functions -> ping();
                break;
        }
            
    }

    private function methodNotAllowed(array $allowed) :void
    {
        http_response_code(405);
        header('Allow: ' . implode(', ', $allowed));
        echo json_encode([
            'error' => 'Method Not Allowed',
            'allowed' => $allowed
        ]);
    }

    private function endpointNotFound(array $allinks = [], int $level = 0) :void
    {

        $res = [
            [
                "header" => 'HTTP/1.1 404 Not Found',
                "rescode" => 404,
                "message" => [
                    "message" => 'Page not Found',
                    'Allowed' => $allinks,
                ],
            ],
            [
                "header" => 'HTTP/1.1 400 Bad Request',
                "rescode" => 400,
                "message" => [
                    'message' => 'Bad Request',
                    'Allowed' => $allinks,
                ],
            ],
        ];

        header($res[$level]['header']);
        //http_response_code ($res[$level]['rescode']);

        echo json_encode($res[$level]['message']);
    }
    
}
