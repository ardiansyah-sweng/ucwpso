<?php

interface OptimizerInterface
{
    /**
     * Adds optimization Algorithms
     *
     * @param integer  $swarm_size      Size of swarm or population
     * @param mixed    $fitness         Stopping value
     * @param integer  $max_generation  Maximal generation or iteration allowed
     */
    public function optimizer(
        $swarm_size,
        $fitness,
        $max_generation
    );
}

class GeneticAlgorithm implements OptimizerInterface
{
    public function optimizer($swarm_size, $fitness, $max_generation)
    {
        // your optimizer code is here
    }
}

class ParticleSwarmOptimizer implements OptimizerInterface
{
    public function optimizer($swarm_size, $fitness, $max_generation)
    {
        // your optimizer code is here
    }
}

/**
 * Optimization algorithm selection
 *
 * @param string  $type       Type of optimization algorithm choosen
 * @param mixed   $iteration  Number of current iteration 
 *
 */
class ChaoticFactory
{
    public function initializeOptimizer($type)
    {
        if ($type === 'ga') {
            return new GeneticAlgorithm();
        }
        if ($type === 'pso') {
            return new ParticleSwarmOptimizer();
        }
    }
}

## Instantiation / usage
// $type = ['bernoulli', 'sine', 'chebyshev', 'circle', 'gauss', 'logistic', 'singer', 'sinu'];
// $chaos_value = 0.8;
// $iteration = 1;

// foreach ($type as $x) {
//     $chaoticFactory = new ChaoticFactory();
//     $chaos = $chaoticFactory->initializeChaotic($x, $iteration);
//     echo $chaos->chaotic($chaos_value);
//     echo '<br>';
// }
