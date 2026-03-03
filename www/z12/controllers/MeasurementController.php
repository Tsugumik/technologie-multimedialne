<?php
require_once 'models/Measurement.php';

class MeasurementController {
    private $model;

    public function __construct($pdoConnection) {
        $this->model = new Measurement($pdoConnection);
    }

    public function handlePost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['x1'], $_POST['x2'], $_POST['x3'], $_POST['x4'], $_POST['x5']) && 
                is_numeric($_POST['x1']) && is_numeric($_POST['x2'])) {
                
                $x1 = (float)$_POST['x1'];
                $x2 = (float)$_POST['x2'];
                $x3 = (float)$_POST['x3'];
                $x4 = (float)$_POST['x4'];
                $x5 = (float)$_POST['x5'];
                
                $pozar = isset($_POST['pozar']) ? 1 : 0;
                $zalanie = isset($_POST['zalanie']) ? 1 : 0;
                $wentylacja = isset($_POST['wentylacja']) ? (int)$_POST['wentylacja'] : 0;

                $this->model->save($x1, $x2, $x3, $x4, $x5, $pozar, $zalanie, $wentylacja);
                
                header("Location: index.php?page=form&success=1");
                exit;
            } else {
                return "Błąd: Wypełnij poprawnie wszystkie pola numeryczne.";
            }
        }
        return null;
    }
}
