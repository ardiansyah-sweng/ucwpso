<?php
set_time_limit(1000000);

class ParticleSwarmOptimizer
{
    protected $swarm_size;
    protected $C1;
    protected $C2;
    protected $max_iteration;
    protected $max_inertia;
    protected $min_inertia;
    protected $stopping_value;
    protected $dataset;
    protected $productivity_factor;
    protected $MAX_COUNTER;

    function __construct($swarm_size, $C1, $C2, $max_iteration, $max_inertia, $min_inertia, $stopping_value, $dataset, $productivity_factor, $max_counter)
    {
        $this->swarm_size = $swarm_size;
        $this->C1 = $C1;
        $this->C2 = $C2;
        $this->max_iteration = $max_iteration;
        $this->max_inertia = $max_inertia;
        $this->min_inertia = $min_inertia;
        $this->stopping_value = $stopping_value;
        $this->dataset = $dataset;
        $this->productivity_factor = $productivity_factor;
        $this->MAX_COUNTER = $max_counter;
    }

    function randomZeroToOne()
    {
        return (float) rand() / (float) getrandmax();
    }

    /**
     * Generate random Simple Use Case Complexity weight parameter
     * Min = 5,     xMinSimple = 4.5
     * Max = 7.49   xMaxSimple = 8.239
     */
    function randomSimpleUCWeight()
    {
        $MIN = 5;
        $MAX = 7.49;
        return mt_rand($MIN * 100, $MAX * 100) / 100;
    }

    /**
     * Generate random Average Use Case Complexity weight parameter
     * Min = 7.5    xMinAverage = 6.75
     * Max = 12.49  xMaxAverage = 13.739
     */
    function randomAverageUCWeight()
    {
        $MIN = 7.5;
        $MAX = 12.49;
        return mt_rand($MIN * 100, $MAX * 100) / 100;
    }

    /**
     * Generate random Complex Use Case Complexity weight parameter
     * Min = 12.5   xMinComplex = 11.25
     * Max = 15     xMaxComplex = 16.5
     */
    function randomComplexUCWeight()
    {
        $MIN = 12.5;
        $MAX = 15;
        return mt_rand($MIN * 100, $MAX * 100) / 100;
    }

    function minimalAE($particles)
    {
        foreach ($particles as $val) {
            $ae[] = $val['ae'];
        }
        return $particles[array_search(min($ae), $ae)];
    }

    function size($xSimple, $simpleUC, $xAverage, $averageUC, $xComplex, $complexUC, $uaw, $tcf, $ecf)
    {
        $ucSimple = $xSimple * $simpleUC;
        $ucAverage = $xAverage * $averageUC;
        $ucComplex = $xComplex * $complexUC;

        $UUCW = $ucSimple + $ucAverage + $ucComplex;
        $UUCP = $uaw + $UUCW;
        return $UUCP * $tcf * $ecf;
    }

    function velocity($inertia, $R1, $R2, $velocity, $position, $Pbest, $Gbest)
    {
        return $inertia * $velocity + ($this->C1 * $R1) * ($Pbest - $position) + ($this->C2 * $R2) * ($Gbest - $position);
    }

    function gauss($chaos_value)
    {
        return fmod(1/$chaos_value, 1);
    }

    function findSolution($project)
    {
        $vMaxSimple = 2.49;
        $vMaxAverage = 4.99;
        $vMaxComplex = 2.5;

        ## Generate population
        for ($i = 0; $i <= $this->swarm_size - 1; $i++) {
            $xSimple = $this->randomSimpleUCWeight();
            $xAverage = $this->randomAverageUCWeight();
            $xComplex = $this->randomComplexUCWeight();

            $UCP = $this->size($xSimple, $project['simpleUC'], $xAverage, $project['averageUC'], $xComplex, $project['complexUC'], $project['uaw'], $project['tcf'], $project['ecf']);
            $esimated_effort = $UCP * $this->productivity_factor;
            $initial_particles[$i]['estimatedEffort'] = $esimated_effort;
            $initial_particles[$i]['ucp'] = $UCP;
            $initial_particles[$i]['ae'] = abs($esimated_effort - $project['actualEffort']);
            $initial_particles[$i]['xSimple'] = $xSimple;
            $initial_particles[$i]['xAverage'] = $xAverage;
            $initial_particles[$i]['xComplex'] = $xComplex;
        } ## End Generate Population

        for ($iteration = 0; $iteration <= $this->max_iteration - 1; $iteration++) {

            for ($i = 0; $i <= $this->swarm_size - 1; $i++) {

                if ($iteration === 0) {
                    $Gbest_initial = $this->minimalAE($initial_particles);

                    $R1[$iteration] = $this->gauss($this->randomZeroToOne());
                    $R2[$iteration] = $this->gauss($this->randomZeroToOne());
                    $chaos_value[$iteration] = $this->gauss($this->randomZeroToOne());
                    $inertia[$iteration] = $chaos_value[$iteration] * $this->min_inertia + (($this->max_inertia - $this->min_inertia) * $iteration / $this->max_iteration);

                    $vSimple = $this->randomZeroToOne();
                    $vAverage = $this->randomZeroToOne();
                    $vComplex = $this->randomZeroToOne();

                    $vSimple = $this->velocity($inertia[$iteration], $R1[$iteration], $R2[$iteration], $vSimple, $initial_particles[$i]['xSimple'], $initial_particles[$i]['xSimple'], $Gbest_initial['xSimple']);
                    if ($vSimple > $vMaxSimple) {
                        $vSimple = $vMaxSimple;
                    }
                    $vAverage = $this->velocity($inertia[$iteration], $R1[$iteration], $R2[$iteration], $vAverage, $initial_particles[$i]['xAverage'], $initial_particles[$i]['xAverage'], $Gbest_initial['xAverage']);
                    if ($vAverage > $vMaxAverage) {
                        $vAverage = $vMaxAverage;
                    }
                    $vComplex = $this->velocity($inertia[$iteration], $R1[$iteration], $R2[$iteration], $vComplex, $initial_particles[$i]['xComplex'], $initial_particles[$i]['xComplex'], $Gbest_initial['xComplex']);
                    if ($vComplex > $vMaxComplex) {
                        $vComplex = $vMaxComplex;
                    }

                    $xSimple = $initial_particles[$i]['xSimple'] + $vSimple;
                    $xAverage = $initial_particles[$i]['xAverage'] + $vAverage;
                    $xComplex = $initial_particles[$i]['xComplex'] + $vComplex;

                    $UCP = $this->size($xSimple, $project['simpleUC'], $xAverage, $project['averageUC'], $xComplex, $project['complexUC'], $project['uaw'], $project['tcf'], $project['ecf']);
                    $esimated_effort = $UCP * $this->productivity_factor;

                    $particles[$iteration][$i]['estimatedEffort'] = $esimated_effort;
                    $particles[$iteration][$i]['ucp'] = $UCP;
                    $particles[$iteration][$i]['ae'] = abs($esimated_effort - $project['actualEffort']);
                    $particles[$iteration][$i]['vSimple'] = $vSimple;
                    $particles[$iteration][$i]['vAverage'] = $vAverage;
                    $particles[$iteration][$i]['vComplex'] = $vComplex;
                    $particles[$iteration][$i]['xSimple'] = $xSimple;
                    $particles[$iteration][$i]['xAverage'] = $xAverage;
                    $particles[$iteration][$i]['xComplex'] = $xComplex;

                    if ($initial_particles[$i]['ae'] > $particles[$iteration][$i]['ae'] || ($initial_particles[$i]['ae'] == $particles[$iteration][$i]['ae'])) {
                        $Pbest[$iteration][] = $particles[$iteration][$i];
                    }
                    if ($initial_particles[$i]['ae'] < $particles[$iteration][$i]['ae']) {
                        $Pbest[$iteration][] = $initial_particles[$i];
                    }
                } ## End iteration = 0

                if ($iteration > 0) {
                    $R1[$iteration] = $this->gauss($R1[$iteration - 1]);
                    $R2[$iteration] = $this->gauss($R2[$iteration - 1]);
                    $chaos_value[$iteration] = $this->gauss($chaos_value[$iteration - 1]);
                    $inertia[$iteration] = $chaos_value[$iteration] * $this->min_inertia + (($this->max_inertia - $this->min_inertia) * $iteration / $this->max_iteration);

                    $Gbest_simple = $Gbest[$iteration - 1]['xSimple'];
                    $Gbest_average = $Gbest[$iteration - 1]['xAverage'];
                    $Gbest_complex = $Gbest[$iteration - 1]['xComplex'];

                    $Pbest_simple = $Pbest[$iteration - 1][$i]['xSimple'];
                    $Pbest_average = $Pbest[$iteration - 1][$i]['xAverage'];
                    $Pbest_complex = $Pbest[$iteration - 1][$i]['xComplex'];

                    $vSimple = $particles[$iteration - 1][$i]['vSimple'];
                    $vAverage = $particles[$iteration - 1][$i]['vAverage'];
                    $vComplex = $particles[$iteration - 1][$i]['vComplex'];

                    $xSimple = $particles[$iteration - 1][$i]['xSimple'];
                    $xAverage = $particles[$iteration - 1][$i]['xAverage'];
                    $xComplex = $particles[$iteration - 1][$i]['xComplex'];

                    $vSimple = $this->velocity($inertia[$iteration], $R1[$iteration], $R2[$iteration], $vSimple, $xSimple, $Pbest_simple, $Gbest_simple);
                    if ($vSimple > $vMaxSimple) {
                        $vSimple = $vMaxSimple;
                    }
                    $vAverage = $this->velocity($inertia[$iteration], $R1[$iteration], $R2[$iteration], $vAverage, $xAverage, $Pbest_average, $Gbest_average);
                    if ($vAverage > $vMaxAverage) {
                        $vAverage = $vMaxAverage;
                    }
                    $vComplex = $this->velocity($inertia[$iteration], $R1[$iteration], $R2[$iteration], $vComplex, $xComplex, $Pbest_complex, $Gbest_complex);
                    if ($vComplex > $vMaxAverage) {
                        $vComplex = $vMaxAverage;
                    }

                    $xSimple = $initial_particles[$i]['xSimple'] + $vSimple;
                    $xAverage = $initial_particles[$i]['xAverage'] + $vAverage;
                    $xComplex = $initial_particles[$i]['xComplex'] + $vComplex;

                    $UCP = $this->size($xSimple, $project['simpleUC'], $xAverage, $project['averageUC'], $xComplex, $project['complexUC'], $project['uaw'], $project['tcf'], $project['ecf']);
                    $esimated_effort = $UCP * $this->productivity_factor;

                    $particles[$iteration][$i]['estimatedEffort'] = $esimated_effort;
                    $particles[$iteration][$i]['ucp'] = $UCP;
                    $particles[$iteration][$i]['ae'] = abs($esimated_effort - $project['actualEffort']);
                    $particles[$iteration][$i]['vSimple'] = $vSimple;
                    $particles[$iteration][$i]['vAverage'] = $vAverage;
                    $particles[$iteration][$i]['vComplex'] = $vComplex;
                    $particles[$iteration][$i]['xSimple'] = $xSimple;
                    $particles[$iteration][$i]['xAverage'] = $xAverage;
                    $particles[$iteration][$i]['xComplex'] = $xComplex;

                    if ($particles[$iteration-1][$i]['ae'] > $particles[$iteration][$i]['ae']|| ($particles[$iteration-1][$i]['ae'] == $particles[$iteration][$i]['ae'])) {
                        $Pbest[$iteration][] = $particles[$iteration][$i];
                    }
                    if ($particles[$iteration-1][$i]['ae'] < $particles[$iteration][$i]['ae']) {
                        $Pbest[$iteration][] = $particles[$iteration-1][$i];
                    }
                } ## End iteration > 0
            } ## End of swarm size

            ## Personal learning strategy
            $SPbest = $Pbest[$iteration];
            $CPbest1_index = array_rand($Pbest[$iteration]);
            $CPbest2_index = array_rand($Pbest[$iteration]);
            $CPbest1 = $Pbest[$iteration][$CPbest1_index];
            $CPbest2 = $Pbest[$iteration][$CPbest2_index];
            $counter = 0;
            while ($counter < $this->MAX_COUNTER) {
                if ($CPbest1_index == $CPbest2_index) {
                    $CPbest1_index = array_rand($Pbest[$iteration]);
                    $CPbest2_index = array_rand($Pbest[$iteration]);
                    $CPbest1 = $Pbest[$iteration][$CPbest1_index];
                    $CPbest2 = $Pbest[$iteration][$CPbest2_index];
                    $counter = 0;
                } else {
                    break;
                }
            }
            if ($CPbest1_index != $CPbest2_index) {
                if ($CPbest1['ae'] < $CPbest2['ae']) {
                    $CPbest = $CPbest1;
                }
                if ($CPbest1['ae'] > $CPbest2['ae']) {
                    $CPbest = $CPbest2;
                }
                if ($CPbest1['ae'] == $CPbest2['ae']) {
                    $CPbest = $CPbest2;
                }
                //compared CPbest with all Pbest_i(t-1)
                foreach ($SPbest as $key => $pbest) {
                    if ($CPbest['ae'] < $pbest['ae']) {
                        $Pbest[$iteration][$key] = $CPbest;
                    }
                }
            }
            ## End of personal learning strategy

            $Gbest[$iteration] = $this->minimalAE($Pbest[$iteration]);
            if ($Gbest[$iteration]['ae'] < $this->stopping_value) {
                return $Gbest[$iteration];
            }
            $Gbests[] = $Gbest[$iteration];
        } ## End of iteration

        $minimal_AE = min(array_column($Gbests, 'ae'));
        $index_minimal_AE = array_search($minimal_AE, array_column($Gbests, 'ae'));
        return $Gbests[$index_minimal_AE];
    } ## End of findSolution()

    /**
     * Return: mae 
     */
    function finishing()
    {
        foreach ($this->dataset as $project) {
            $ret[] = $this->findSolution($project);
        }
        return $ret;
    }
}

/**
 * Dataset 71 data point
 * Attribute (7): simple, average, complex, uaw, tcf, ecf, actual effort
 */
$dataset = array(
    array('simpleUC' => 6, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 9, 'tcf' => 0.81, 'ecf' => 0.84, 'actualEffort' => 7970),
    array('simpleUC' => 4, 'averageUC' => 20, 'complexUC' => 15, 'uaw' => 8, 'tcf' => 0.99, 'ecf' => 0.99, 'actualEffort' => 7962),
    array('simpleUC' => 1, 'averageUC' => 5, 'complexUC' => 20, 'uaw' => 9, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 7935),
    array('simpleUC' => 5, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 8, 'tcf' => 0.9, 'ecf' => 0.91, 'actualEffort' => 7805),
    array('simpleUC' => 1, 'averageUC' => 10, 'complexUC' => 16, 'uaw' => 8, 'tcf' => 0.9, 'ecf' => 0.91, 'actualEffort' => 7758),
    array('simpleUC' => 1, 'averageUC' => 13, 'complexUC' => 14, 'uaw' => 8, 'tcf' => 0.99, 'ecf' => 0.99, 'actualEffort' => 7643),
    array('simpleUC' => 3, 'averageUC' => 18, 'complexUC' => 15, 'uaw' => 7, 'tcf' => 0.94, 'ecf' => 1.02, 'actualEffort' => 7532),
    array('simpleUC' => 0, 'averageUC' => 16, 'complexUC' => 12, 'uaw' => 8, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 7451),
    array('simpleUC' => 2, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 8, 'tcf' => 0.94, 'ecf' => 1.02, 'actualEffort' => 7449),
    array('simpleUC' => 4, 'averageUC' => 14, 'complexUC' => 17, 'uaw' => 7, 'tcf' => 1.025, 'ecf' => 0.98, 'actualEffort' => 7427),
    array('simpleUC' => 5, 'averageUC' => 16, 'complexUC' => 10, 'uaw' => 8, 'tcf' => 0.92, 'ecf' => 0.78, 'actualEffort' => 7406),
    array('simpleUC' => 1, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 8, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 7365),
    array('simpleUC' => 9, 'averageUC' => 8, 'complexUC' => 19, 'uaw' => 7, 'tcf' => 0.75, 'ecf' => 0.81, 'actualEffort' => 7350),
    array('simpleUC' => 5, 'averageUC' => 8, 'complexUC' => 20, 'uaw' => 7, 'tcf' => 1.02, 'ecf' => 1.085, 'actualEffort' => 7303),
    array('simpleUC' => 2, 'averageUC' => 15, 'complexUC' => 11, 'uaw' => 8, 'tcf' => 1.095, 'ecf' => 0.95, 'actualEffort' => 7252),
    array('simpleUC' => 1, 'averageUC' => 8, 'complexUC' => 16, 'uaw' => 8, 'tcf' => 0.92, 'ecf' => 0.78, 'actualEffort' => 7245),
    array('simpleUC' => 2, 'averageUC' => 15, 'complexUC' => 16, 'uaw' => 7, 'tcf' => 0.75, 'ecf' => 0.81, 'actualEffort' => 7166),
    array('simpleUC' => 5, 'averageUC' => 11, 'complexUC' => 17, 'uaw' => 7, 'tcf' => 0.965, 'ecf' => 0.755, 'actualEffort' => 7119),
    array('simpleUC' => 3, 'averageUC' => 9, 'complexUC' => 14, 'uaw' => 8, 'tcf' => 0.92, 'ecf' => 0.78, 'actualEffort' => 7111),
    array('simpleUC' => 2, 'averageUC' => 14, 'complexUC' => 11, 'uaw' => 8, 'tcf' => 1.05, 'ecf' => 0.95, 'actualEffort' => 7044),
    array('simpleUC' => 5, 'averageUC' => 14, 'complexUC' => 15, 'uaw' => 7, 'tcf' => 0.71, 'ecf' => 0.73, 'actualEffort' => 7040),
    array('simpleUC' => 3, 'averageUC' => 23, 'complexUC' => 10, 'uaw' => 7, 'tcf' => 1.02, 'ecf' => 1.085, 'actualEffort' => 7028),
    array('simpleUC' => 1, 'averageUC' => 16, 'complexUC' => 10, 'uaw' => 8, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 6942),
    array('simpleUC' => 1, 'averageUC' => 15, 'complexUC' => 10, 'uaw' => 7, 'tcf' => 0.965, 'ecf' => 0.755, 'actualEffort' => 6814),
    array('simpleUC' => 2, 'averageUC' => 19, 'complexUC' => 12, 'uaw' => 9, 'tcf' => 0.78, 'ecf' => 0.79, 'actualEffort' => 6809),
    array('simpleUC' => 2, 'averageUC' => 20, 'complexUC' => 11, 'uaw' => 8, 'tcf' => 0.98, 'ecf' => 0.97, 'actualEffort' => 6802),
    array('simpleUC' => 0, 'averageUC' => 14, 'complexUC' => 11, 'uaw' => 12, 'tcf' => 0.78, 'ecf' => 0.51, 'actualEffort' => 6787),
    array('simpleUC' => 1, 'averageUC' => 9, 'complexUC' => 14, 'uaw' => 7, 'tcf' => 1.08, 'ecf' => 0.77, 'actualEffort' => 6764),
    array('simpleUC' => 4, 'averageUC' => 15, 'complexUC' => 14, 'uaw' => 7, 'tcf' => 1.05, 'ecf' => 0.95, 'actualEffort' => 6761),
    array('simpleUC' => 0, 'averageUC' => 15, 'complexUC' => 10, 'uaw' => 7, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 6725),
    array('simpleUC' => 1, 'averageUC' => 16, 'complexUC' => 9, 'uaw' => 7, 'tcf' => 1.02, 'ecf' => 1.085, 'actualEffort' => 6690),
    array('simpleUC' => 0, 'averageUC' => 18, 'complexUC' => 8, 'uaw' => 7, 'tcf' => 1.08, 'ecf' => 0.77, 'actualEffort' => 6600),
    array('simpleUC' => 0, 'averageUC' => 17, 'complexUC' => 8, 'uaw' => 7, 'tcf' => 0.94, 'ecf' => 1.02, 'actualEffort' => 6474),
    array('simpleUC' => 0, 'averageUC' => 13, 'complexUC' => 15, 'uaw' => 6, 'tcf' => 0.95, 'ecf' => 0.92, 'actualEffort' => 6433),
    array('simpleUC' => 1, 'averageUC' => 13, 'complexUC' => 10, 'uaw' => 7, 'tcf' => 0.78, 'ecf' => 0.79, 'actualEffort' => 6416),
    array('simpleUC' => 0, 'averageUC' => 14, 'complexUC' => 10, 'uaw' => 8, 'tcf' => 0.94, 'ecf' => 1.02, 'actualEffort' => 6412),
    array('simpleUC' => 0, 'averageUC' => 14, 'complexUC' => 9, 'uaw' => 6, 'tcf' => 0.9, 'ecf' => 0.94, 'actualEffort' => 6400),
    array('simpleUC' => 1, 'averageUC' => 10, 'complexUC' => 12, 'uaw' => 7, 'tcf' => 0.71, 'ecf' => 0.73, 'actualEffort' => 6360),
    array('simpleUC' => 0, 'averageUC' => 13, 'complexUC' => 15, 'uaw' => 6, 'tcf' => 0.9, 'ecf' => 0.91, 'actualEffort' => 6337),
    array('simpleUC' => 1, 'averageUC' => 20, 'complexUC' => 27, 'uaw' => 18, 'tcf' => 0.72, 'ecf' => 0.67, 'actualEffort' => 6240),
    array('simpleUC' => 1, 'averageUC' => 11, 'complexUC' => 11, 'uaw' => 7, 'tcf' => 0.78, 'ecf' => 0.51, 'actualEffort' => 6232),
    array('simpleUC' => 1, 'averageUC' => 14, 'complexUC' => 9, 'uaw' => 7, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 6173),
    array('simpleUC' => 0, 'averageUC' => 12, 'complexUC' => 15, 'uaw' => 6, 'tcf' => 1, 'ecf' => 0.92, 'actualEffort' => 6160),
    array('simpleUC' => 2, 'averageUC' => 15, 'complexUC' => 12, 'uaw' => 6, 'tcf' => 1.095, 'ecf' => 0.95, 'actualEffort' => 6117),
    array('simpleUC' => 2, 'averageUC' => 13, 'complexUC' => 9, 'uaw' => 7, 'tcf' => 0.75, 'ecf' => 0.81, 'actualEffort' => 6062),
    array('simpleUC' => 1, 'averageUC' => 27, 'complexUC' => 15, 'uaw' => 19, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 6051),
    array('simpleUC' => 3, 'averageUC' => 26, 'complexUC' => 15, 'uaw' => 18, 'tcf' => 0.72, 'ecf' => 0.67, 'actualEffort' => 6048),
    array('simpleUC' => 2, 'averageUC' => 19, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 6035),
    array('simpleUC' => 1, 'averageUC' => 19, 'complexUC' => 5, 'uaw' => 6, 'tcf' => 0.965, 'ecf' => 0.755, 'actualEffort' => 6024),
    array('simpleUC' => 20, 'averageUC' => 25, 'complexUC' => 9, 'uaw' => 18, 'tcf' => 0.85, 'ecf' => 0.88, 'actualEffort' => 6023),
    array('simpleUC' => 5, 'averageUC' => 25, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 1.118, 'ecf' => 0.995, 'actualEffort' => 5993),
    array('simpleUC' => 4, 'averageUC' => 16, 'complexUC' => 21, 'uaw' => 18, 'tcf' => 0.85, 'ecf' => 0.88, 'actualEffort' => 5985),
    array('simpleUC' => 5, 'averageUC' => 21, 'complexUC' => 17, 'uaw' => 18, 'tcf' => 0.75, 'ecf' => 0.81, 'actualEffort' => 5971),
    array('simpleUC' => 5, 'averageUC' => 21, 'complexUC' => 17, 'uaw' => 18, 'tcf' => 0.81, 'ecf' => 0.84, 'actualEffort' => 5962),
    array('simpleUC' => 6, 'averageUC' => 16, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 5944),
    array('simpleUC' => 5, 'averageUC' => 25, 'complexUC' => 20, 'uaw' => 17, 'tcf' => 0.85, 'ecf' => 0.88, 'actualEffort' => 5940),
    array('simpleUC' => 0, 'averageUC' => 14, 'complexUC' => 8, 'uaw' => 6, 'tcf' => 0.98, 'ecf' => 0.97, 'actualEffort' => 5927),
    array('simpleUC' => 3, 'averageUC' => 18, 'complexUC' => 19, 'uaw' => 17, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 5885),
    array('simpleUC' => 5, 'averageUC' => 16, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 1.08, 'ecf' => 0.77, 'actualEffort' => 5882),
    array('simpleUC' => 1, 'averageUC' => 14, 'complexUC' => 12, 'uaw' => 6, 'tcf' => 0.72, 'ecf' => 0.67, 'actualEffort' => 5880),
    array('simpleUC' => 3, 'averageUC' => 26, 'complexUC' => 14, 'uaw' => 18, 'tcf' => 0.82, 'ecf' => 0.79, 'actualEffort' => 5880),
    array('simpleUC' => 1, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 6, 'tcf' => 0.96, 'ecf' => 0.96, 'actualEffort' => 5876),
    array('simpleUC' => 0, 'averageUC' => 3, 'complexUC' => 20, 'uaw' => 6, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 5873),
    array('simpleUC' => 3, 'averageUC' => 17, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 1.095, 'ecf' => 0.95, 'actualEffort' => 5865),
    array('simpleUC' => 2, 'averageUC' => 17, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 0.965, 'ecf' => 0.755, 'actualEffort' => 5863),
    array('simpleUC' => 3, 'averageUC' => 21, 'complexUC' => 17, 'uaw' => 18, 'tcf' => 0.98, 'ecf' => 0.97, 'actualEffort' => 5856),
    array('simpleUC' => 2, 'averageUC' => 18, 'complexUC' => 18, 'uaw' => 18, 'tcf' => 1.05, 'ecf' => 0.95, 'actualEffort' => 5800),
    array('simpleUC' => 1, 'averageUC' => 23, 'complexUC' => 22, 'uaw' => 17, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 5791),
    array('simpleUC' => 5, 'averageUC' => 30, 'complexUC' => 10, 'uaw' => 19, 'tcf' => 0.95, 'ecf' => 0.92, 'actualEffort' => 5782),
    array('simpleUC' => 5, 'averageUC' => 15, 'complexUC' => 5, 'uaw' => 6, 'tcf' => 1, 'ecf' => 0.92, 'actualEffort' => 5778),
    array('simpleUC' => 5, 'averageUC' => 18, 'complexUC' => 17, 'uaw' => 18, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 5775)
);

$swarm_size = 70;
$C1 = 2;
$C2 = 2;
$MAX_ITERATION = 40;
$max_inertia = 0.9;
$min_inertia = 0.4;
$stopping_value = 200;
$trials = 1000;
$productivity_factor = 20;
$MAX_COUNTER = 100000;

for ($trial = 0; $trial <= $trials - 1; $trial++) {
    $optimize = new ParticleSwarmOptimizer($swarm_size, $C1, $C2, $MAX_ITERATION, $max_inertia, $min_inertia, $stopping_value, $dataset, $productivity_factor, $MAX_COUNTER);
    $results[]  = $optimize->finishing();
}

foreach ($results as $key => $estimated_project) {
    $mae[] = array_sum(array_column($estimated_project, 'ae')) / count($dataset);
}
$index = array_search(min($mae), $mae);
foreach ($results[$index] as $key => $best_estimated) {
    echo $best_estimated['ae'] . ' == ' . $best_estimated['estimatedEffort'] . '<br>';
    //convert to txt
    $data = array($best_estimated['ae'], $best_estimated['estimatedEffort']);
    $fp = fopen('hasil_gauss_original.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
}
