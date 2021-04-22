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

    ## Return splitted two particles
    function qualityEvalution($portion, $particles)
    {
        $lq_proportion = $portion * $this->particle_size;
        array_multisort(array_column($particles, 'ae'), SORT_DESC, $particles); 
        foreach ($particles as $key => $particle) {
            if ($key <= $lq_proportion - 1) {
                $LQ[] = $particle;
            }
            if ($key >= $lq_proportion) {
                $HQ[] = $particle;
            }
        }
        $ret['lq'] = $LQ;
        $ret['hq'] = $HQ;
        return $ret;
    }

    function bestWorstParticle($splitted_particles,$r1,$r2, $project)
    {
        $min_lq = min(array_column($splitted_particles['lq'],'ae'));
        $max_lq = max(array_column($splitted_particles['lq'],'ae'));
        $min_hq = min(array_column($splitted_particles['hq'],'ae'));
        $max_hq = max(array_column($splitted_particles['hq'],'ae'));
       
        foreach ($splitted_particles['hq'] as $particle){
            $random_index1 = array_rand($splitted_particles['hq']);
            $random_particle1 = $splitted_particles['hq'][$random_index1];
            if ($particle['ae'] < $random_particle1['ae']){
                $particle1 = $particle;
            }
            if ($particle['ae'] > $random_particle1['ae']){
                $particle1 = $random_particle1;
            }

            $random_index2 = array_rand($splitted_particles['hq']);
            $random_particle2 = $splitted_particles['hq'][$random_index2];
            if ($particle['ae'] < $random_particle2['ae']){
                $particle2 = $particle;
            }
            if ($particle['ae'] > $random_particle2['ae']){
                $particle2 = $random_particle2;
            }
            if (is_null($particle1) || is_null($particle2)){
                echo 'ada';
            }

            $xSimple = $particle['xSimple'] + ($r1 * ($min_hq - abs($max_hq))) + ($r2 * (abs($particle1['xSimple']) - abs($particle2['xSimple'])));

            $xAverage = $particle['xAverage'] + ($r1 * ($min_hq - abs($max_hq))) + ($r2 * (abs($particle1['xAverage']) - abs($particle2['xAverage'])));

            $xComplex = $particle['xComplex'] + ($r1 * ($min_hq - abs($max_hq))) + ($r2 * (abs($particle1['xComplex']) - abs($particle2['xComplex'])));

            $UCP = $this->size($xSimple, $project['simpleUC'], $xAverage, $project['averageUC'], $xComplex, $project['complexUC'], $project['uaw'], $project['tcf'], $project['ecf']);
            $esimated_effort = $UCP * $this->productivity_factor;
            $ret[] = abs($esimated_effort - floatval($project['actualEffort']));
        }
        return $ret;
    }


    function findSolution($project)
    {
        for ($generation = 0; $generation <= 1; $generation++) {
            $r1 = $this->randomZeroToOne();
            $r2 = $this->randomZeroToOne();

            ## Generate population
            if ($generation === 0) {
                for ($i = 0; $i <= $this->particle_size - 1; $i++) {
                    $xSimple = $this->randomSimpleUCWeight();
                    $xAverage = $this->randomAverageUCWeight();
                    $xComplex = $this->randomComplexUCWeight();

                    $UCP = $this->size($xSimple, $project['simpleUC'], $xAverage, $project['averageUC'], $xComplex, $project['complexUC'], $project['uaw'], $project['tcf'], $project['ecf']);
                    $esimated_effort = $UCP * $this->productivity_factor;
                    $particles[$i]['estimatedEffort'] = $esimated_effort;
                    $particles[$i]['ucp'] = $UCP;
                    $particles[$i]['ae'] = abs($esimated_effort - floatval($project['actualEffort']));
                    $particles[$i]['xSimple'] = $xSimple;
                    $particles[$i]['xAverage'] = $xAverage;
                    $particles[$i]['xComplex'] = $xComplex;
                }
                $splitted_particles = $this->qualityEvalution($this->portion, $particles);
                $best_particles = $this->minimalAE($particles);
                $positions = $this->bestWorstParticle($splitted_particles,$r1,$r2, $project);
                $bestHQ = min($positions);
                echo $best_particles['ae'].' vs '.$bestHQ;
            } ## End if Generation = 0

            break;
        } ## End of Generation
    } ## End of findSolution()

    function processingDataset()
    {
        foreach ($this->prepareDataset() as $key => $project) {
            if ($key == 0) {
                $results[] = $this->findSolution($project);
            }
        }
        return $results;
    }
} ## End of class ParticleSwarmOptimizer

$particle_size = 60;
$file_name = 'silhavy_dataset.txt';
$productivity_factor = 20;
$portion = 0.5;
$a = 0.5;
$b = 0.9;
$stopping_value = 200;
$maximum_generation = 40;

$optimize = new EvolutionaryRaoAlgorithm($particle_size, $file_name, $productivity_factor, $portion, $a, $b, $stopping_value, $maximum_generation);
print_r($optimize->processingDataset());
