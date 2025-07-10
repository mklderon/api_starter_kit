<?php

namespace Core;

abstract class Controller {
    protected $model;

    public function __construct() {
        if ($this->model && config('app.db_enable', false)) {
            $db = app()->getDatabase();
            if ($db === null) {
                throw new \Exception("Database connection is null in Controller (DB_ENABLE may be false)");
            }
            $this->model = new $this->model($db);
        }
    }
    
    protected function json($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data);
    }
    
    protected function getRequestData() {
        return json_decode(file_get_contents('php://input'), true);
    }
    
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            if (strpos($rule, 'required') !== false && empty($data[$field])) {
                $errors[$field] = "The {$field} field is required";
            }
        }
        
        if (!empty($errors)) {
            $this->json(['errors' => $errors], 422);
            exit;
        }
        
        return true;
    }
}