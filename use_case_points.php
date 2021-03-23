<?php
require_once "class_read_file.php";
require_once "class_PSOptimizer.php";

/**
 * Use Case Points Software Effort Estimation
 * 
 * @input 
 */

class UCPEstimator
{
    private $dataset_file_name;
    public $productivity_factor;

    function __construct($dataset_file_name, $productivity_factor)
    {
        $this->dataset_file_name = $dataset_file_name;
        $this->productivity_factor = $productivity_factor;
    }

    function prepareDataset()
    {
        $read = new FileReader($this->dataset_file_name);
        $data = $read->readFile();

        $column_indexes = [0, 1, 2, 3, 4, 5, 6];
        $columns = ['simpleUC', 'averageUC', 'complexUC', 'uaw', 'tcf', 'ecf', 'actualEffort'];
        foreach ($data as $key => $val) {
            foreach (array_keys($val) as $subkey) {
                if ($subkey == $column_indexes[$subkey]) {
                    $data[$key][$columns[$subkey]] = $data[$key][$subkey];
                    unset($data[$key][$subkey]);
                }
            }
        }
        return $data;
    }

    function UCP($weights, $parameters)
    {
        $xSimple = floatval($weights[0]);
        $xAverage = floatval($weights[1]);
        $xComplex = floatval($weights[2]);

        $ucSimple = $xSimple * floatval($parameters['simpleUC']);
        $ucAverage = $xAverage * floatval($parameters['averageUC']);
        $ucComplex = $xComplex * floatval($parameters['complexUC']);

        $UUCW = $ucSimple + $ucAverage + $ucComplex;
        $UUCP = $parameters['uaw'] + $UUCW;
        $UCP = $UUCP * $parameters['tcf'] * $parameters['ecf'];
        return $UCP * $this->productivity_factor;
    }
}

