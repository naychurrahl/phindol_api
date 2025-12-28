<?php

  header("Access-Control-Allow-Origin: *");
  //header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PATCH");
  header("Access-Control-Allow-Methods: *");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");
  header('Content-Type: application/json');

  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No content
    exit();
  }

  require_once __DIR__ . '/Controller.php';


  // Get method and URI

  $method = $_SERVER['REQUEST_METHOD'];

  $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

  $uri = trim($uri, '/');


  // Break URI parts

  $parts = explode('/', $uri);


  // Pass to router/controller

  $controller = new Controller($parts, $method);
