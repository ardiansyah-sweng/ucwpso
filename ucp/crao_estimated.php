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

    function findSolution($project)
    {
        for ($generation = 0; $generation <= $this->maximum_generation - 1; $generation++) {
            ## Generate population
            if ($generation === 0) {
                $r1[$generation + 1] = $this->sinu(0.8);
                $r2[$generation + 1] = $this->sinu(0.8);
                $r3[$generation + 1] = $this->sinu(0.8);

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
                $r1[$generation + 1] = $this->sinu($r1[$generation]);
                $r2[$generation + 1] = $this->sinu($r2[$generation]);
                $r3[$generation + 1] = $this->sinu($r3[$generation]);

                for ($i = 0; $i <= $this->particle_size - 1; $i++) {
                    $xSimple = $particles[$generation][$i]['xSimple'] + $r1[$generation] * ($best_particles[$generation]['xSimple'] - $worst_particles[$generation]['xSimple']);
                    $xAverage = $particles[$generation][$i]['xAverage'] + $r2[$generation] * ($best_particles[$generation]['xAverage'] - $worst_particles[$generation]['xAverage']);
                    $xComplex = $particles[$generation][$i]['xComplex'] + $r3[$generation] * ($best_particles[$generation]['xComplex'] - $worst_particles[$generation]['xComplex']);

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

        $best = min(array_column($ret, 'ae'));
        $best_index = array_search($best, array_column($ret, 'ae'));
        $solutions['best'] = $ret[$best_index]['ae'];
        $solutions['worst'] = $ret[$best_index + 1]['ae'];
        return $solutions;
    } ## End of findSolution()

    function processingDataset()
    {
        foreach ($this->prepareDataset() as $key => $project) {
            if ($key >= 0) {
                for ($i = 0; $i <= 999; $i++){
                    $results[$key][] = $this->findSolution($project);
                }
            }
        }
        return $results;
    }
} ## End of class ParticleSwarmOptimizer

$particle_size = 30;
$file_name = 'silhavy_dataset.txt';
$productivity_factor = 20;
$portion = 0.5;
$a = 0.5;
$b = 0.9;
$stopping_value = 200;
$maximum_generation = 40;
$trials = 1000;

$optimize = new EvolutionaryRaoAlgorithm($particle_size, $file_name, $productivity_factor, $portion, $a, $b, $stopping_value, $maximum_generation);
$results = $optimize->processingDataset();
foreach ($results as $key => $result){
    $best = array_sum(array_column($result,'best'))/$trials;
    $worst = array_sum(array_column($result,'worst'))/$trials;
    echo $best.' -- '.$worst.'<br>';
    $data = array($key,$best, $worst);
    $fp = fopen('hasil_crao_estimated.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
}

