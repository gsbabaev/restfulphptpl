<?php

namespace RESTfulPHPtpl;

use Exception;

if( !defined('YOUR_SESSION_ID'))
    define('YOUR_SESSION_ID',md5($_SERVER['REMOTE_ADDR'].'admin'));


class TRS
{
    public $params;
    public $json;

    public function __construct()
    {
        $this->initParams();
        $this->data = $this->getFormData($this->params['method']);

        $this->json = $this->route();
    }



    public function check(){
        return isset($this->json) AND $this->json['message'] != 'Not execute.';
    }

    protected function initParams(){
        $this->params['SAPI'] = PHP_SAPI;
        $this->params['method'] = $_SERVER['REQUEST_METHOD'];
        //init params: router , task and urlData
        $this->setRouter();
    }

    // Получение данных из тела запроса
    protected function getFormData($method) {

        // GET или POST: данные возвращаем как есть
        if ($method === 'GET') return $this->data;
        if ($method === 'POST') return $_POST;

        // PUT, PATCH или DELETE
        $data = array();
        $exploded = explode('&', file_get_contents('php://input'));

        foreach($exploded as $pair) {
            $item = explode('=', $pair);
            if (count($item) == 2) {
                $data[urldecode($item[0])] = urldecode($item[1]);
            }
        }

        return $data;
    }

    protected function setRouter(){
        // Разбираем url
        $url = (isset($_GET['q'])) ? $_GET['q'] : '';
        $url = trim($url, '/');
        $urls = explode('/', $url);

        // Определяем роутер, задачи над данными и url data
        $this->params['router'] = $urls[0];
        $this->params['task'] = $urls[1];

        if($this->params['method'] == 'GET'){
            $this->data = array_slice($urls, 2);
        }
    }

    public function json(){
        return json_encode($this->json);
    }

    protected function route()
    {
        // Роутер
        $json = ['success' => FALSE, 'message' => 'Error', 'data' => null]; //Ошибка
        $low_m_name = strtolower($this->params['method']);
        $metod_name = __DIR__.'/'.$this->params['router'].'/'.$low_m_name.'.php';

        try{
            if(!is_file($metod_name)){
                throw new Exception('Not execute.');
            }
            require_once $metod_name;

            $m = new $low_m_name($this->params['task'], $this->filter_vars($this->data));
            $json = $m->json();

        }catch (Exception  $exception){
            $json['message'] = $exception->getMessage();
        }
        return $json;
    }

    protected function filter_vars($inputs, $filter = FILTER_SANITIZE_STRING){
        $r = [];
        foreach ($inputs as $k => $input) {
            $r [$k] = filter_var($input, $filter);
        }
        return $r;
    }

}


?>