<?php
require_once "use_case_points.php";
require_once "class_PSOptimizer.php";

interface ObjectiveFunction
{
    public function objectiveFunction($weights, $parameters);
}

class UseCasePoints implements ObjectiveFunction
{
    public function objectiveFunction($weights, $parameters)
    {
        $predict = new UCPEstimator('silhavy_dataset.txt', $productivity_factor = 20);
        $file_name = 'ucp_weight_parameters.txt';
        $read = new FileReader($file_name);
        $optimize = new ParticleSwarmOptimizer($read->readFile(), [], 70, 0.35, 10, $inertia_max = 0.9, $inertia_min = 0.4, 2, 2, 238.1);
        foreach ($weights as $weight){
            $estimated_effort = $optimize->PSO($predict->UCP($weight, $parameters), $parameters);
        }

    }
}

class Objective
{
    public static function functions($name)
    {
        switch ($name) {
            case "UCP":
                return new UseCasePoints;
        }
    }
}

$predict = new UCPEstimator('silhavy_dataset.txt', $productivity_factor = 20);
$dataset = $predict->prepareDataset();

$file_name = 'ucp_weight_parameters.txt';
$read = new FileReader($file_name);
$optimize = new ParticleSwarmOptimizer($read->readFile(), [], 70, 0.35, 10, $inertia_max = 0.9, $inertia_min = 0.4, 2, 2, 238.1);

foreach ($dataset as $key => $parameters) {
    if ($key == 0) {
        echo 'Actual effort ' . $parameters['actualEffort'];
        $particles_weights = $optimize->particle($parameters);
        Objective::functions('UCP')->objectiveFunction($particles_weights, $parameters);
        echo '<br>';
        echo "<p>";
    }
}
