<html>
<Title>Use Case Complexity Weight using PSO</Title>

</html>

<?php
set_time_limit(10000);

/**
 * Optimizing Use case complexity weight parameter using particle swarm optimization
 * 2020 Ardiansyah
 */
class UCWParticleSwarmOptimization
{
    private $PRODUCTIVITY_FACTOR = 20;
    private $C1 = 2.8;
    private $C2 = 1.3;
    private $SWARM_SIZE = 10;
    private $MAX_ITERATION = 500;
    private $FITNESS_VALUE_BASELINE = array(
        'azzeh1' => 1219.8,
        'azzeh2' => 201.1,
        'azzeh3' => 1564.8,
        'silhavy' => 240.19,
        'karner' => 1820,
        'nassif' => 1712,
        'ardiansyah' => 404.85
    );
    private $INERTIA_WEIGHT = 0.9;
    private $USE_CASE_PARAMETER = 3;

    /**
     * Generate random number [0,1]
     */
    public function randomNumberBetweenZeroToOne()
    {
        return (float) rand() / (float) getrandmax();
    }

    /**
     * Generate initial velocity
     */
    function initialVelocity()
    {
        for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
            for ($j = 0; $j <= $this->USE_CASE_PARAMETER - 1; $j++) {
                $ret[$i][$j] = $this->randomNumberBetweenZeroToOne();
            }
        }
        return $ret;
    }

    /**
     * Generate random Simple Use Case Complexity weight parameter
     * Min = 5
     * Max = 7.49
     */
    function randomSimpleUCWeight()
    {
        $MIN = 5;
        $MAX = 7.49;
        return mt_rand($MIN * 100, $MAX * 100) / 100;
    }

    /**
     * Generate random Average Use Case Complexity weight parameter
     * Min = 7.5
     * Max = 12.49 
     */
    function randomAverageUCWeight()
    {
        $MIN = 7.5;
        $MAX = 12.49;
        return mt_rand($MIN * 100, $MAX * 100) / 100;
    }

    /**
     * Generate random Complex Use Case Complexity weight parameter
     * Min = 12.5
     * Max = 15
     */
    function randomComplexUCWeight()
    {
        $MIN = 12.5;
        $MAX = 15;
        return mt_rand($MIN * 100, $MAX * 100) / 100;
    }

    /**
     * Split into three
     */
    public function splitter($data)
    {
        for ($i = 0; $i <= count($data) - 1; $i += 3) {
            for ($j = 0; $j <= $this->USE_CASE_PARAMETER - 1; $j++) {
                if ($j == 0) {
                    $ret[$i][$j] = $data[$i];
                }
                if ($j == 1) {
                    $ret[$i][$j] = $data[$i + 1];
                }
                if ($j == 2) {
                    $ret[$i][$j] = $data[$i + 2];
                }
            }
        }
        return $ret;
    }

    /**
     * Linear decreasing weight (LDW)
     */
    function inertia($iteration)
    {
        $we = 0.4;
        return ($this->INERTIA_WEIGHT - $we) * ($this->MAX_ITERATION - $iteration) / ($this->MAX_ITERATION + $we);
    }

    /**
     * Generate Use Case Complexity Weight
     * @return array simple, average, complex
     */
    function initialUseCaseWeight()
    {
        for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
            for ($j = 0; $j <= $this->USE_CASE_PARAMETER - 1; $j++) {
                if ($j == 0) {
                    $ret[$i]['simple'] = $this->randomSimpleUCWeight();
                }
                if ($j == 1) {
                    $ret[$i]['average'] = $this->randomAverageUCWeight();
                }
                if ($j == 2) {
                    $ret[$i]['complex'] = $this->randomComplexUCWeight();
                }
            }
        }
        return $ret;
    }

    /**
     * Use Case Complexity Weight Calculation
     * complexity = complexity weight * number of Use Case
     * @return array {0 UC Simple | 1 Position Simple | 2 UC Average | 3 Position Average | 4 UC Complex | 5 Position Complex}
     * Every multiples of two is UC complexity weight
     * @param flag UCCW or POSITIONS
     */
    function useCaseComplexity($dataset, $flag, $positions)
    {
        for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
            for ($j = 0; $j <= $this->USE_CASE_PARAMETER - 1; $j++) {
                if ($j == 0) {
                    $ret[] = $positions[$i]['simple'] * $dataset['simpleUC'];
                    $ret[] = $positions[$i]['simple'];
                }
                if ($j == 1) {
                    $ret[] = $positions[$i]['average'] * $dataset['averageUC'];
                    $ret[] = $positions[$i]['average'];
                }
                if ($j == 2) {
                    $ret[] = $positions[$i]['complex'] * $dataset['complexUC'];
                    $ret[] = $positions[$i]['complex'];
                }
            }
        }
        for ($i = 0; $i <= count($ret) - 1; $i += 2) {
            $useCaseWeight[] = $ret[$i];
        }
        for ($i = 1; $i <= count($ret); $i += 2) {
            $position[] = $ret[$i];
        }
        $ret1 = $this->splitter($useCaseWeight);
        $ret2 = $this->splitter($position);
        
        if ($flag == 'UCCW') {
            return $ret1;
        }
        if ($flag == 'POSITIONS') {
            return $ret2;
        }
    }

    /**
     * Change key index
     */
    function changeKeyIndex($data)
    {
        $rows = count($data) * 3;
        for ($i = 0; $i <= $rows - 1; $i += 3) {
            $ret[] = $data[$i];
        }
        return $ret;
    }

    /**
     * Labeling column name
     */
    function labelingColumn($data)
    {
        $rows = count($data) * 3;
        for ($i = 0; $i <= $rows - 1; $i += 3) {
            for ($j = 0; $j <= $this->USE_CASE_PARAMETER - 1; $j++) {
                if ($j == 0) {
                    $ret[$i]['simple'] = $data[$i][$j];
                }
                if ($j == 1) {
                    $ret[$i]['average'] = $data[$i][$j];
                }
                if ($j == 2) {
                    $ret[$i]['complex'] = $data[$i][$j];
                }
            }
        }
        return $ret;
    }

    /**
     * Labeling Positions
     */
    function labelingPositions($velocity, $positions)
    {
        for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
            for ($j = 0; $j <= $this->USE_CASE_PARAMETER - 1; $j++) {
                if ($j == 0) {
                    $positions[$i]['simple'] = $this->updatePositions($positions, $velocity)[$i][$j];
                }
                if ($j == 1) {
                    $positions[$i]['average'] = $this->updatePositions($positions, $velocity)[$i][$j];
                }
                if ($j == 2) {
                    $positions[$i]['complex'] = $this->updatePositions($positions, $velocity)[$i][$j];
                }
            }
        }
        return $positions;
    }

    /**
     * Personal Best
     */
    function personalBest($comparedAE)
    {
        for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
            for ($j = 0; $j <= $this->USE_CASE_PARAMETER - 1; $j++) {
                if ($j == 0) {
                    $pBest[$i]['simple'] = $comparedAE[$i]['positions']['simple'];
                }
                if ($j == 1) {
                    $pBest[$i]['average'] = $comparedAE[$i]['positions']['average'];
                }
                if ($j == 2) {
                    $pBest[$i]['complex'] = $comparedAE[$i]['positions']['complex'];
                }
            }
        }
        return $pBest;
    }

    /**
     * Calculate Unadjusted Use Case Weighting
     * UUCW = use case complexity simple + average + complex
     * @return array 
     */
    function uucw($data)
    {
        foreach ($this->changeKeyIndex($this->labelingColumn($data)) as $val) {
            $uucw[] = $val['simple'] + $val['average'] + $val['complex'];
        }
        return $uucw;
    }

    function positions($data)
    {
        return $this->changeKeyIndex($this->labelingColumn($data));
    }

    /**
     * Unadjusted Use Case Points (UUCP)
     * UUCP = UAW + UUCW
     * @return array
     */
    function uucp($dataset, $uucw)
    {
        for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
            $ret[$i] = $uucw[$i] + $dataset['uaw'];
        }
        return $ret;
    }

    /**
     * Use Case Points (UCP)
     * UCP = UUCP + TCF + ECF
     * @return array
     */
    function ucp($dataset, $ucp)
    {
        for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
            $ucp[$i] = $ucp[$i] * $dataset['tcf'] * $dataset['ecf'];
        }
        return $ucp;
    }

    /**
     * Estimated Effort = UCP x 20 Person Hours
     * Also called solution candidate or particle in problem space
     * @return array
     */
    function estimatedEffort($ucp)
    {
        for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
            $estimatedEffort[$i] = $ucp[$i] * $this->PRODUCTIVITY_FACTOR;
        }
        return $estimatedEffort;
    }

    /**
     * Search array key index
     */
    function searchKeyIndex($key, $data)
    {
        foreach ($data as $index => $val) {
            if ($val['ae'] == $key) {
                return $index;
            }
        }
        //return array_search($key, $data);
    }

    /**
     * Absolute Error = |estimated effort - actual effort|
     * Also known as fitness value in each particle
     * @return array
     */
    function absoluteError($dataset, $estimatedEffort)
    {
        for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
            $ret[$i]['ae'] = abs($estimatedEffort[$i] - $dataset['actualEffort']);
            $ret[$i]['estimated'] = $estimatedEffort[$i];
        }
        return $ret;
    }

    /**
     * Sum array
     */
    function sumArray($data)
    {
        $sum = 0;
        foreach ($data as $key => $val) {
            $sum = $sum + $val['ae'];
        }
        return $sum;
    }

    /**
     * Fitness Function evaluation
     * MAE = sum of Absolute Error / count of absolute error data poin
     * @return simpleGBest, averageGBest, complexGBest, keyIndex, minimumAE, mae, continue
     */
    public function fitnessFunction($absoluteError, $positions)
    {
        // print_r($absoluteError);
        // echo '<p>';
        // print_r(min($absoluteError));
        // echo '<p>';
        $minimumAbsoluteError = min($absoluteError);
        // print_r($minimumAbsoluteError['ae']);
        // echo '<p>';
        $keyIndex = $this->searchKeyIndex($minimumAbsoluteError['ae'], $absoluteError);
        foreach ($positions as $key => $val) {
            if ($keyIndex == $key) {
                for ($i = 0; $i <= 2; $i++) {
                    if ($i == 0) {
                        $ret[0]['simpleGBest'] = $val['simple'];
                    }
                    if ($i == 1) {
                        $ret[0]['averageGBest'] = $val['average'];
                    }
                    if ($i == 2) {
                        $ret[0]['complexGBest'] = $val['complex'];
                    }
                }
            }
        }
        $ret[0]['keyIndex'] = $this->searchKeyIndex($minimumAbsoluteError, $absoluteError);
        $ret[0]['minimumAE'] = $minimumAbsoluteError['ae'];
        $ret[0]['estimated'] = $minimumAbsoluteError['estimated'];

        $mae = $this->sumArray($absoluteError) / count($absoluteError);
        $ret[0]['mae'] = $mae;

        // print_r($ret);
        // echo '<p>';

        if ($mae < $this->FITNESS_VALUE_BASELINE['silhavy']) {
            $ret[0]['continue'] = "False";
            return $ret;
        }
        if ($mae >= $this->FITNESS_VALUE_BASELINE['silhavy']) {
            $ret[0]['continue'] = "True";
            return $ret;
        }
    }

    public function updateVelocity($inertia, $velocity, $simpleGBest, $averageGBest, $complexGBest, $pBest, $positions, $C1, $C2)
    {
        for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
            for ($j = 0; $j <= $this->USE_CASE_PARAMETER - 1; $j++) {
                if ($j == 0) {
                    $ret[$i][$j] = ($inertia * $velocity[$i][$j]) + ($this->randomNumberBetweenZeroToOne() * $C1) * ($pBest[$i]['simple'] - $positions[$i]['simple']) + ($this->randomNumberBetweenZeroToOne() * $C2) * ($simpleGBest - $positions[$i]['simple']);
                }
                if ($j == 1) {
                    $ret[$i][$j] = ($inertia * $velocity[$i][$j]) + ($this->randomNumberBetweenZeroToOne() * $C1) * ($pBest[$i]['average'] - $positions[$i]['average']) + ($this->randomNumberBetweenZeroToOne() * $C2) * ($averageGBest - $positions[$i]['average']);
                }
                if ($j == 2) {
                    $ret[$i][$j] = ($inertia * $velocity[$i][$j]) + ($this->randomNumberBetweenZeroToOne() * $C1) * ($pBest[$i]['complex'] - $positions[$i]['complex']) + ($this->randomNumberBetweenZeroToOne() * $C2) * ($complexGBest - $positions[$i]['complex']);
                }
            }
        }
        return $ret;
    }

    public function updatePositions($positions, $velocity)
    {
        for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
            for ($j = 0; $j <= $this->USE_CASE_PARAMETER - 1; $j++) {
                if ($j == 0) {
                    $ret[$i][$j] = $positions[$i]['simple'] + $velocity[$i][$j];
                }
                if ($j == 1) {
                    $ret[$i][$j] = $positions[$i]['average'] + $velocity[$i][$j];
                }
                if ($j == 2) {
                    $ret[$i][$j] = $positions[$i]['complex'] + $velocity[$i][$j];
                }
            }
        }
        return $ret;
    }

    /**
     * Generate Initial Population
     */
    function initialPopulation($dataset, $flag)
    {
        $positions = $this->initialUseCaseWeight();
        return $this->useCaseComplexity($dataset, $flag, $positions);
    }

    /**
     * Merge Absolute Error and Positions
     */
    function mergeAEandPosition($absoluteError, $positions)
    {
        for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
            $ret[$i]['ae'] = $absoluteError[$i]['ae'];
            $ret[$i]['positions'] = $positions[$i];
            $ret[$i]['estimated'] = $absoluteError[$i]['estimated'];
        }
        return $ret;
    }

    /**
     * AE Comparison to determine pBest in each iteration
     */
    function compareAE($lastAE, $currentAE)
    {
        for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
            if ($lastAE[$i]['ae'] > $currentAE[$i]['ae']) {
                $lastAE[$i]['ae'] = $currentAE[$i]['ae'];
                $lastAE[$i]['positions'] = $currentAE[$i]['positions'];
                $lastAE[$i]['estimated'] = $currentAE[$i]['estimated'];
            }
        }
        return $lastAE;
    }

    /**
     * Main PSO
     */
    public function mainPSO($dataset)
    {
        //Generate Population
        $C1 = $this->C1;
        $C2 = $this->C2;
        $uucw = $this->uucw($this->initialPopulation($dataset, "UCCW"));
        $positions = $this->positions($this->initialPopulation($dataset, "POSITIONS"));
        $uucp = $this->uucp($dataset, $uucw);
        $ucp = $this->ucp($dataset, $uucp);
        $estimatedEffort = $this->estimatedEffort($ucp);
        $absoluteError = $this->absoluteError($dataset, $estimatedEffort);
        // print_r($absoluteError);
        // echo '<p>';
        $copiedAEInitial = $this->mergeAEandPosition($absoluteError, $positions);

        foreach ($this->fitnessFunction($absoluteError, $positions) as $val) {
            $simpleGBest = $val['simpleGBest'];
            $averageGBest = $val['averageGBest'];
            $complexGBest = $val['complexGBest'];
        }

        //Entering Iteration
        for ($iteration = 0; $iteration <= $this->MAX_ITERATION - 1; $iteration++) {
            if ($iteration == 0) {
                $velocity = $this->updateVelocity(
                    $this->inertia($iteration),
                    $this->initialVelocity(),
                    $simpleGBest,
                    $averageGBest,
                    $complexGBest,
                    $positions,
                    $positions,
                    $C1,
                    $C2
                );
                $positions = $this->labelingPositions($velocity, $positions);
                $UCCW = $this->useCaseComplexity($dataset, "UCCW", $positions);
                $uucw = $this->uucw($UCCW);
                $uucp = $this->uucp($dataset, $uucw);
                $ucp = $this->ucp($dataset, $uucp);
                $estimatedEffort = $this->estimatedEffort($ucp);
                $absoluteError = $this->absoluteError($dataset, $estimatedEffort);
                $copiedAEIteration0 = $this->mergeAEandPosition($absoluteError, $positions);
                $comparedAE = $this->compareAE($copiedAEInitial, $copiedAEIteration0);
                $pBest = $this->personalBest($comparedAE);
                foreach ($this->fitnessFunction($absoluteError, $positions) as $val) {
                    $simpleGBest = $val['simpleGBest'];
                    $averageGBest = $val['averageGBest'];
                    $complexGBest = $val['complexGBest'];
                }
            }
            if ($iteration >= 1) {
                $velocity = $this->updateVelocity(
                    $this->inertia($iteration),
                    $velocity,
                    $simpleGBest,
                    $averageGBest,
                    $complexGBest,
                    $pBest,
                    $positions,
                    $C1,
                    $C2
                );
                $positions = $this->labelingPositions($velocity, $positions);
                $UCCW = $this->useCaseComplexity($dataset, "UCCW", $positions);
                $uucw = $this->uucw($UCCW);
                $uucp = $this->uucp($dataset, $uucw);
                $ucp = $this->ucp($dataset, $uucp);
                $estimatedEffort = $this->estimatedEffort($ucp);
                $absoluteError = $this->absoluteError($dataset, $estimatedEffort);
                $comparedAE = $this->compareAE($comparedAE, $this->mergeAEandPosition($absoluteError, $positions));
                // print_r($comparedAE);
                // echo '<p>';
                $pBest = $this->personalBest($comparedAE);

                foreach ($this->fitnessFunction($absoluteError, $positions) as $val) {
                    $simpleGBest = $val['simpleGBest'];
                    $averageGBest = $val['averageGBest'];
                    $complexGBest = $val['complexGBest'];
                }

                // print_r($this->fitnessFunction($absoluteError, $positions));
                // echo '<p>';
                $isContinue = $this->fitnessFunction($absoluteError, $positions)[0]['continue'];
                $mae = $this->fitnessFunction($absoluteError, $positions)[0]['mae'];
                $estimated = $this->fitnessFunction($absoluteError, $positions)[0]['estimated'];

                if ($isContinue == "True") {
                    $all[$iteration]['mae'] = $mae;
                    $all[$iteration]['continue'] = $isContinue;
                    $all[$iteration]['estimated'] = $estimated;
                    //print_r($this->fitnessFunction($absoluteError, $positions));
                    //echo '<p>';
                } else {
                    $all[$iteration]['mae'] = $mae;
                    $all[$iteration]['continue'] = $isContinue;
                    $all[$iteration]['estimated'] = $estimated;
                    //print_r($this->fitnessFunction($absoluteError, $positions));
                    //echo '<p>';
                    break;
                }
            }
        }
        //print_r($all);
        //echo '<p>';

        //print_r(min($all));
        return min($all);
        // if ($this->fitnessFunction($absoluteError, $positions)[0]['continue'] == "False") {
        //     $ret[] = $this->fitnessFunction($absoluteError, $positions)[0]['mae'];
        //     return $this->fitnessFunction($absoluteError, $positions)[0]['mae'];
        // } else {
        //     $ret[] = min($mae);
        //     return min($mae);
        // }
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
    array('simpleUC' => 4, 'averageUC' => 14, 'complexUC' => 17, 'uaw' => 7, 'tcf' => 1.025, 'ecf' => 0.98, 'actualEffort' => 7427),
    array('simpleUC' => 2, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 8, 'tcf' => 0.94, 'ecf' => 1.02, 'actualEffort' => 7449),
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
$start = microtime(true);
$ucwPSO = new UCWParticleSwarmOptimization();
$numDataset = count($dataset);

$temp = 0;
$iterasi = 30;

foreach ($dataset as $key => $val) {
    for ($i = 0; $i <= $iterasi - 1; $i++) {
        $run = $ucwPSO->mainPSO($dataset[$key]);
        $estimated[] = $run['estimated'];
    }
    //print_r($estimated);
    //echo '<br>';   
    //$ae = abs((array_sum($estimated)/count($estimated)) - $dataset[$key]['actualEffort']);
    $rerataKolomEstimated = array_sum($estimated) / count($estimated);
    echo $rerataKolomEstimated . '<br>';
    $ae[] = abs($rerataKolomEstimated - $dataset[$key]['actualEffort']);
    $estimated = [];
}

echo '<br>';
echo "Grand MAE:" . array_sum($ae) / count($ae);
// }





//print_r($run);
//     if($run[0]['continue'] == "False"){
//         echo $run[0]['mae'].'<br>';
//     }
// }
// $repeat = 1;
// for ($j = 0; $j <= $repeat - 1; $j++) {
//     $temp = 0;
//     for ($i = 0; $i <= count($dataset) - 1; $i++) {
//         $tes = $ucwPSO->mainPSO($dataset[0]);
//         $allAE[$i]['ae'] = $tes;
//         $temp = $temp + $tes;
//     }
//     $grandMAE[] = $allAE;
// }

// $temp = 0;
// for ($i = 0; $i <= count($grandMAE) - 1; $i++) {
//     for ($j = 0; $j <= $numDataset - 1; $j++) {
//         $temp = $temp + $grandMAE[$i][$j]['ae'];
//     }
//     $arr[$i] = $temp / $numDataset;
//     $temp = 0;
// }
// echo '<p>';
// echo 'Number of experiment: ' . $repeat . '<br>';
// echo 'Best minimum AE: ' . min($arr) . '<br>';
// $keyIndexMinAE = array_search(min($arr), $arr);
// for ($i = 0; $i <= count($grandMAE) - 1; $i++) {
//     if ($keyIndexMinAE == $i) {
//         for ($j = 0; $j <= $numDataset - 1; $j++) {
//             echo $grandMAE[$i][$j]['ae'] . '<br>';
//         }
//     }
// }

echo '<p>';
$end = microtime(true);
$duration = $end - $start;
if ($duration >= 60) {
    $minutes = $duration / 60;
    echo 'Execution time: ' . $minutes . ' minutes';
}
if ($duration < 60) {
    echo 'Execution time: ' . $duration . ' seconds';
}
