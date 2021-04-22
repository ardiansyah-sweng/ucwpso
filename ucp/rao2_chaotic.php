<?php
set_time_limit(1000000);

class EvolutionaryRaoAlgorithm
{
    protected $particle_size;
    protected $dataset_name;
    protected $productivity_factor;
    protected $portion;
    protected $a;
    protected $b;
    protected $stopping_value;
    protected $maximum_generation;

    function __construct($particle_size, $dataset_name, $productivity_factor, $portion, $a, $b, $stopping_value, $maximum_generation)
    {
        $this->particle_size = $particle_size;
        $this->dataset_name = $dataset_name;
        $this->productivity_factor = $productivity_factor;
        $this->portion = $portion;
        $this->a = $a;
        $this->b = $b;
        $this->stopping_value = $stopping_value;
        $this->maximum_generation = $maximum_generation;
    }

    function prepareDataset()
    {
        $raw_dataset = file($this->dataset_name);
        foreach ($raw_dataset as $val) {
            $data[] = explode(",", $val);
        }
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

    function maximalAE($particles)
    {
        foreach ($particles as $val) {
            $ae[] = $val['ae'];
        }
        return $particles[array_search(max($ae), $ae)];
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

    function sine($value)
    {
        return sin(pi() * $value);
    }

    function singer($value)
    {
        return 1.07 * ((7.86 * $value) - (23.31 * POW($value, 2)) + (28.75 * POW($value, 3)) - (13.302875 * POW($value, 4)));
    }

    function sinu($chaos_value)
    {
        return (2.3 * POW($chaos_value, 2)) * sin(pi() * $chaos_value);
    }

    function gauss($value)
    {
        return fmod(1 / $value, 1);
    }

    function logistic($chaos_value)
    {
        return (4 * $chaos_value) * (1 - $chaos_value);
    }

    function chebyshev($iteration, $chaos_value)
    {
        return cos($iteration * cos(pow($chaos_value, -1)));
    }

    function bernoulli($chaos_value)
    {
        if ($chaos_value > 0 && $chaos_value <= (1 - (1 / 2))) {
            return $chaos_value / (1 - (1 / 2));
        }
        if ($chaos_value > (1 - (1 / 2)) && $chaos_value < 1) {
            return ($chaos_value - (1 - (1 / 2))) / (1 / 2);
        }
    }

    function iterative($chaos_value)
    {
        return sin(($chaos_value * pi()) / $chaos_value);
    }

    function circle($chaos_value)
    {
        return fmod($chaos_value + 0.2 - (0.5 / (2 * pi())) * sin(2 * pi() * $chaos_value), 1);
    }

    function piecewise($chaos_value)
    {
        $P = mt_rand(0.01 * 100, 0.5 * 100) / 100;

        //P >= r[iterasi] >= 0
        if ($chaos_value >= 0 && $chaos_value <= $P) {
            return $chaos_value / $P;
        }
        // 0.5 >= r[$iterasi] >= P
        if ($chaos_value >= $P && $chaos_value <= 0.5) {
            return ($chaos_value - $P) / (0.5 - $P);
        }
        //1-P >= r[$iterasi] >= 0.5
        if ($chaos_value >= 0.5 && $chaos_value <= (1 - $P)) {
            return (1 - $P - $chaos_value) / (0.5 - $P);
        }
        //1 >= r[iterasi] >= 1-P
        if ($chaos_value >= (1 - $P) && $chaos_value <= 1) {
            return (1 - $chaos_value) / $P;
        }
    }

    function tent($chaos_value)
    {
        if ($chaos_value < 0.7) {
            return $chaos_value / 0.7;
        }
        return (10 / 3) * (1 - $chaos_value);
    }

    

    function findSolution($project)
    {
        for ($generation = 0; $generation <= $this->maximum_generation - 1; $generation++) {
           
            ## Generate population
            if ($generation === 0) {
                $r11[$generation + 1] = $this->singer(0.8);
                $r12[$generation + 1] = $this->singer(0.8);
                $r21[$generation + 1] = $this->sinu(0.8);
                $r22[$generation + 1] = $this->sinu(0.8);
                $r31[$generation + 1] = $this->singer(0.8);
                $r32[$generation + 1] = $this->singer(0.8);

                for ($i = 0; $i <= $this->particle_size - 1; $i++) {
                    $xSimple = $this->randomSimpleUCWeight();
                    $xAverage = $this->randomAverageUCWeight();
                    $xComplex = $this->randomComplexUCWeight();

                    $UCP = $this->size($xSimple, $project['simpleUC'], $xAverage, $project['averageUC'], $xComplex, $project['complexUC'], $project['uaw'], $project['tcf'], $project['ecf']);
                    $esimated_effort = $UCP * $this->productivity_factor;
                    $particles[$generation + 1][$i]['estimatedEffort'] = $esimated_effort;
                    $particles[$generation + 1][$i]['ucp'] = $UCP;
                    $particles[$generation + 1][$i]['ae'] = abs($esimated_effort - floatval($project['actualEffort']));
                    $particles[$generation + 1][$i]['xSimple'] = $xSimple;
                    $particles[$generation + 1][$i]['xAverage'] = $xAverage;
                    $particles[$generation + 1][$i]['xComplex'] = $xComplex;
                }
            } ## End if Generation = 0

            if ($generation > 0) {
                $r11[$generation + 1] = $this->singer($r11[$generation]);
                $r12[$generation + 1] = $this->singer($r12[$generation]);
                $r21[$generation + 1] = $this->sinu($r21[$generation]);
                $r22[$generation + 1] = $this->sinu($r22[$generation]);
                $r31[$generation + 1] = $this->singer($r31[$generation]);
                $r32[$generation + 1] = $this->singer($r32[$generation]);

                for ($i = 0; $i <= $this->particle_size - 1; $i++) {
                    $index_candidate1 = array_rand($particles[$generation]);
                    $index_candidate2 = array_rand($particles[$generation]);
                    $ae_candidate1 = $particles[$generation][$index_candidate1]['ae'];
                    $ae_candidate2 = $particles[$generation][$index_candidate2]['ae'];

                    if ($particles[$generation][$i]['ae'] > $ae_candidate1){
                        $candidate_xSimple1 = $particles[$generation][$index_candidate1]['xSimple'];
                        $candidate_xAverage1 = $particles[$generation][$index_candidate1]['xAverage'];
                        $candidate_xComplex1 = $particles[$generation][$index_candidate1]['xComplex'];
                    }
                    if ($particles[$generation][$i]['ae'] < $ae_candidate1 || $particles[$generation][$i]['ae'] == $ae_candidate1){
                        $candidate_xSimple1 = $particles[$generation][$i]['xSimple'];
                        $candidate_xAverage1 = $particles[$generation][$i]['xAverage'];
                        $candidate_xComplex1 = $particles[$generation][$i]['xComplex'];
                    }
                    if ($particles[$generation][$i]['ae'] > $ae_candidate2 ){
                        $candidate_xSimple2 = $particles[$generation][$index_candidate2]['xSimple'];
                        $candidate_xAverage2 = $particles[$generation][$index_candidate2]['xAverage'];
                        $candidate_xComplex2 = $particles[$generation][$index_candidate2]['xComplex'];
                    }
                    if ($particles[$generation][$i]['ae'] < $ae_candidate2 ||$particles[$generation][$i]['ae'] == $ae_candidate2){
                        $candidate_xSimple2 = $particles[$generation][$i]['xSimple'];
                        $candidate_xAverage2 = $particles[$generation][$i]['xAverage'];
                        $candidate_xComplex2 = $particles[$generation][$i]['xComplex'];
                    }
                    $xSimple = ($particles[$generation][$i]['xSimple'] + $r11[$generation] * ($best_particles[$generation]['xSimple'] - $worst_particles[$generation]['xSimple'])) + ($r12[$generation] * (abs($candidate_xSimple1) - abs($candidate_xSimple2)));

                    $xAverage = $particles[$generation][$i]['xAverage'] + $r21[$generation] * ($best_particles[$generation]['xAverage'] - $worst_particles[$generation]['xAverage']) + ($r22[$generation] * (abs($candidate_xAverage1) - abs($candidate_xAverage2)));

                    $xComplex = $particles[$generation][$i]['xComplex'] + $r31[$generation] * ($best_particles[$generation]['xComplex'] - $worst_particles[$generation]['xComplex']) + ($r32[$generation] * (abs($candidate_xComplex1) - abs($candidate_xComplex2)));

                    $UCP = $this->size($xSimple, $project['simpleUC'], $xAverage, $project['averageUC'], $xComplex, $project['complexUC'], $project['uaw'], $project['tcf'], $project['ecf']);
                    $esimated_effort = $UCP * $this->productivity_factor;

                    $particles[$generation + 1][$i]['estimatedEffort'] = $esimated_effort;
                    $particles[$generation + 1][$i]['ucp'] = $UCP;
                    $particles[$generation + 1][$i]['ae'] = abs($esimated_effort - floatval($project['actualEffort']));
                    $particles[$generation + 1][$i]['xSimple'] = $xSimple;
                    $particles[$generation + 1][$i]['xAverage'] = $xAverage;
                    $particles[$generation + 1][$i]['xComplex'] = $xComplex;
                }
            }
            $best_particles[$generation + 1] = $this->minimalAE($particles[$generation + 1]);
            $worst_particles[$generation + 1] = $this->maximalAE($particles[$generation + 1]);
            $ret[] = $best_particles[$generation + 1];
            $ret[] = $worst_particles[$generation + 1];
        } ## End of Generation
        return min(array_column($ret, 'ae'));
    } ## End of findSolution()

    function processingDataset()
    {
        foreach ($this->prepareDataset() as $key => $project) {
            if ($key >= 0) {
                $results[] = $this->findSolution($project);
            }
        }
        return $results;
    }
} ## End of class ParticleSwarmOptimizer

$particle_size = 20;
$file_name = 'silhavy_dataset.txt';
$productivity_factor = 20;
$portion = 0.5;
$a = 0.5;
$b = 0.9;
$stopping_value = 200;
$maximum_generation = 100;
$trials = 1000;

for ($generation = 1; $generation <= $maximum_generation; $generation++) {
    $start = microtime(true);
    for ($i = 0; $i <= $trials - 1; $i++) {
        $optimize = new EvolutionaryRaoAlgorithm($particle_size, $file_name, $productivity_factor, $portion, $a, $b, $stopping_value, $generation);
        $temps[] = array_sum($optimize->processingDataset()) / 71;
    }
    $end = microtime(true);
    $duration = $end - $start;

    $mean = array_sum($temps) / $trials;

    if (is_nan($mean)) {
        $mean = 0;
    }

    echo $generation.' -- '.$duration . ' -- ' . $mean;
    echo '<br>';

    $data = array($duration, $mean);
    $fp = fopen('hasil_rao2_chaotic.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
    $temps = [];
}
