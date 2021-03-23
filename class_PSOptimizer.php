<?php
require_once "class_read_file.php";
require_once "effort_estimation.php";

set_time_limit(10000);

/**
 * Particle Swarm Optimizer (PSO)
 * 
 * @input weight parameters, 
 */
class ParticleSwarmOptimizer
{
    private $weight_parameters;
    private $randomization_algorithm_indexes;
    public $swarm_size;
    private $weight_limit;
    private $max_iteration;
    private $inertia_max;
    private $inertia_min;
    private $C1;
    private $C2;
    public $stopping_value;

    function __construct($weight_parameters, $randomization_algorithm_indexes, $swarm_size, $weight_limit, $max_iteration, $inertia_max, $inertia_min, $C1, $C2, $stopping_value)
    {
        $this->weight_parameters = $weight_parameters;
        $this->randomization_algorithm_indexes = $randomization_algorithm_indexes;
        $this->swarm_size = $swarm_size;
        $this->weight_limit = $weight_limit;
        $this->max_iteration = $max_iteration;
        $this->inertia_max = $inertia_max;
        $this->inertia_min = $inertia_min;
        $this->C1 = $C1;
        $this->C2 = $C2;
        $this->stopping_value = $stopping_value;
    }

    /**
     * generate random value [0,1]
     */
    function zeroToOne()
    {
        return (float) rand() / (float) getrandmax();
    }

    /**
     * Generate random value betweeen [min,max] weight parameters
     * @return array weights
     */
    function randomWeightParametersMinToMax()
    {
        foreach ($this->weight_parameters as $val) {
            $minWeight = floatval($val[1]);
            $maxWeight = floatval($val[2]);
            $ret[] = mt_rand($minWeight * 100, $maxWeight * 100) / 100;
        }
        return $ret;
    }

    // TODO based on fitness function chosen
    // @return array[fitness value, best fitness particles index]
    function fitnessFunction($particles, $parameters)
    {
        foreach ($particles as $particle_iteration) {
            foreach ($particle_iteration as $particle_no_objective_value) {
                $absolute_error[] = abs($particle_no_objective_value[0] - floatval($parameters['actualEffort']));
            }
        }
        $ret['best_index'] = array_search(min($absolute_error), $absolute_error);
        $ret['fitness_value'] = array_sum($absolute_error) / $this->swarm_size;
        return $ret;
    }

    /**
     * Main function of PSO
     */
    function particle()
    {
        // Initial population
        for ($i = 0; $i <= $this->swarm_size; $i++) {
            $particles_weights[] =  $this->randomWeightParametersMinToMax();
        }
        return $particles_weights;
    }

    function PSO($objective_value, $parameters)
    {
       $errors[] = abs($objective_value - floatval($parameters['actualEffort'])); // TODO separate with another class fitness function
       $fitness_value = array_sum($errors) / $this->swarm_size;
       echo $fitness_value;
    }
}
