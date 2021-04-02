<?php
set_time_limit(1000000);

class MPUCWPSO
{
    private $PRODUCTIVITY_FACTOR = 20;
    private $FITNESS_VALUE_BASELINE = array(
        'azzeh1' => 1219.8,
        'azzeh2' => 201.1,
        'azzeh3' => 1564.8,
        'silhavy' => 240.19,
        'karner' => 1820,
        'nassif' => 1712,
        'ardiansyah' => 404.85,
        'polynomial' => 238.11
    );

    private $INERTIA_MAX = 0.9;
    private $INERTIA_MIN = 0.4;
    private $C1 = 2;
    private $C2 = 2;

    /**
     * Membangkitkan nilai acak dari 0..1
     */
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

    /**
     * Fungsi AE Minimal
     * Parameter: arrPartikel
     * Return: arrPartikel[indexAEMinimal]
     */
    function minimalAE($arrPartikel)
    {
        foreach ($arrPartikel as $val) {
            $ae[] = $val['ae'];
        }
        return $arrPartikel[array_search(min($ae), $ae)];
    }

    function chaoticR1R2($R1R2)
    {
        return 1.07 * ((7.86 * $R1R2) - (23.31 * POW($R1R2, 2)) + (28.75 * POW($R1R2, 3)) - (13.302875 * POW($R1R2, 4)));
    }

    function Main($dataset, $max_iter, $swarm_size, $max_counter, $limit_percentage)
    {
        ##Generate Population
        for ($i = 0; $i <= $swarm_size - 1; $i++) {
            $xSimple = $this->randomSimpleUCWeight();
            $xAverage = $this->randomAverageUCWeight();
            $xComplex = $this->randomComplexUCWeight();

            $ucSimple = $xSimple * $dataset['simpleUC'];
            $ucAverage = $xAverage * $dataset['averageUC'];
            $ucComplex = $xComplex * $dataset['complexUC'];

            $UUCW = $ucSimple + $ucAverage + $ucComplex;
            $UUCP = $UUCW + $dataset['uaw'];
            $UCP = $UUCP * $dataset['tcf'] * $dataset['ecf'];

            $partikelAwal[$i]['estimatedEffort'] = $UCP * $this->PRODUCTIVITY_FACTOR;
            $partikelAwal[$i]['ae'] = abs($partikelAwal[$i]['estimatedEffort'] - $dataset['actualEffort']);
            $partikelAwal[$i]['xSimple'] = $xSimple;
            $partikelAwal[$i]['xAverage'] = $xAverage;
            $partikelAwal[$i]['xComplex'] = $xComplex;
        }

        //echo '<p>';
        //Pada generate partikel, Pbest sama dengan Partikel
        $Pbest = $partikelAwal;
        $GBest = $this->minimalAE($Pbest);
        //Proses SPBest:
        //1. Ambil 2 partikel acak dari Pbest
        $CPbestIndex1 = array_rand($Pbest);
        $CPbestIndex2 = array_rand($Pbest);
        $CPbest1 = $Pbest[$CPbestIndex1];
        $CPbest2 = $Pbest[$CPbestIndex2];

        //2. Jika kedua partikel tidak sama maka bandingkan keduanya. Ambil yang terkecil
        $counter = 0;
        while ($counter < $max_counter) {
            if ($CPbestIndex1 == $CPbestIndex2) {
                $CPbestIndex1 = array_rand($Pbest);
                $CPbestIndex2 = array_rand($Pbest);
                $CPbest1 = $Pbest[$CPbestIndex1];
                $CPbest2 = $Pbest[$CPbestIndex2];
                $counter = 0;
            } else {
                break;
            }
        }
        //echo $CPbestIndex1.' '.$CPbestIndex2.'<br>';

        if ($CPbestIndex1 != $CPbestIndex2) {
            if ($CPbest1['ae'] < $CPbest2['ae']) {
                $CPbest = $CPbest1;
            }
            if ($CPbest1['ae'] > $CPbest2['ae']) {
                $CPbest = $CPbest2;
            }
            if ($CPbest1['ae'] == $CPbest2['ae']) {
                $CPbest = $CPbest2;
            }
            foreach ($Pbest as $key => $val) {
                //3. Ganti PBest dengan CPbest
                if ($CPbest['ae'] < $val['ae']) {
                    $Pbest[$key] = $CPbest;
                }
            }
            //4. SPbest baru diperoleh. Siap digunakan untuk velocity pada Iterasi-0
            // print_r($Pbest);
            // echo '<br>';
        }
        $SPbest = $Pbest;
        // echo 'Gbest awal: '; print_r($GBest); echo '<br>';

        ##End Generate Population

        //Inertia weight
        //inisialisasi singer map chaotic inertia weight
        //$r0 = $this->randomNumber(number_format($this->randomZeroToOne(), 2));

        //check if there are particles exceeds the lower or upper limit
        $arrLimit = array(
            'xSimple' => array('xSimpleMin' => (5 - (5 * $limit_percentage)), 'xSimpleMax' => (7.49 + (7.49 * $limit_percentage))),
            'xAverage' => array('xAverageMin' => (7.5 - (7.5 * $limit_percentage)), 'xAverageMax' => (12.49 + (12.49 * $limit_percentage))),
            'xComplex' => array('xComplexMin' => (12.5 - (12.5 * $limit_percentage)), 'xComplexMax' => (15 + (15 * $limit_percentage))),
        );

        ##Masuk Iterasi
        $iterasi = 0;
        $counter = 0;
        while ($iterasi <= $max_iter - 1) {
            if ($iterasi == 0) {
                $R1[$iterasi] = $this->chaoticR1R2($this->randomZeroToOne());
                $R2[$iterasi] = $this->chaoticR1R2($this->randomZeroToOne());

                //Inertia weight
                $random_zeroToOne = $this->randomZeroToOne();
                $r[$iterasi] = 1.07 * ((7.86 * $random_zeroToOne) - (23.31 * POW($random_zeroToOne, 2)) + (28.75 * POW($random_zeroToOne, 3)) - (13.302875 * POW($random_zeroToOne, 4)));

                $w = $r[$iterasi] * $this->INERTIA_MIN + ((($this->INERTIA_MAX - $this->INERTIA_MIN) * $iterasi) / $max_iter);

                //Update Velocity dan X_Posisi
                for ($i = 0; $i <= $swarm_size - 1; $i++) {

                    $vInitial = $this->randomZeroToOne();

                    //Simple
                    $vSimple = ($w * $vInitial) + (($this->C1 * $R1[$iterasi]) * ($SPbest[$i]['xSimple'] - $partikelAwal[$i]['xSimple'])) + (($this->C2 * $R2[$iterasi]) * ($GBest['xSimple'] - $partikelAwal[$i]['xSimple']));
                    $xSimple = $partikelAwal[$i]['xSimple'] + $vSimple;

                    //Average
                    $vAverage = ($w * $vInitial) + (($this->C1 * $R1[$iterasi]) * ($SPbest[$i]['xAverage'] - $partikelAwal[$i]['xAverage'])) + (($this->C2 * $R2[$iterasi]) * ($GBest['xAverage'] - $partikelAwal[$i]['xAverage']));
                    $xAverage = $partikelAwal[$i]['xAverage'] + $vAverage;

                    //Complex
                    $vComplex = ($w * $vInitial) + (($this->C1 * $R1[$iterasi]) * ($SPbest[$i]['xComplex'] - $partikelAwal[$i]['xComplex'])) + (($this->C2 * $R2[$iterasi]) * ($GBest['xComplex'] - $partikelAwal[$i]['xComplex']));
                    $xComplex = $partikelAwal[$i]['xComplex'] + $vComplex;

                    //exceeding limit
                    if ($xSimple < $arrLimit['xSimple']['xSimpleMin']) {
                        $xSimple = $arrLimit['xSimple']['xSimpleMin'];
                    }
                    if ($xSimple > $arrLimit['xSimple']['xSimpleMax']) {
                        $xSimple = $arrLimit['xSimple']['xSimpleMax'];
                    }
                    if ($xAverage < $arrLimit['xAverage']['xAverageMin']) {
                        $xAverage = $arrLimit['xAverage']['xAverageMin'];
                    }
                    if ($xAverage > $arrLimit['xAverage']['xAverageMax']) {
                        $xAverage = $arrLimit['xAverage']['xAverageMax'];
                    }
                    if ($xComplex < $arrLimit['xComplex']['xComplexMin']) {
                        $xComplex = $arrLimit['xComplex']['xComplexMin'];
                    }
                    if ($xComplex > $arrLimit['xComplex']['xComplexMax']) {
                        $xComplex = $arrLimit['xComplex']['xComplexMax'];
                    }

                    $ucSimple = $xSimple * $dataset['simpleUC'];
                    $ucAverage = $xAverage * $dataset['averageUC'];
                    $ucComplex = $xComplex * $dataset['complexUC'];

                    $UUCW = $ucSimple + $ucAverage + $ucComplex;
                    $UUCP = $UUCW + $dataset['uaw'];
                    $UCP = $UUCP * $dataset['tcf'] * $dataset['ecf'];
                    $estEffort = $UCP * $this->PRODUCTIVITY_FACTOR;

                    $partikel[$iterasi][$i]['estimatedEffort'] = $estEffort;
                    $partikel[$iterasi][$i]['ae'] = abs($estEffort - $dataset['actualEffort']);
                    $partikel[$iterasi][$i]['xSimple'] = $xSimple;
                    $partikel[$iterasi][$i]['xAverage'] = $xAverage;
                    $partikel[$iterasi][$i]['xComplex'] = $xComplex;
                    $partikel[$iterasi][$i]['vSimple'] = $vSimple;
                    $partikel[$iterasi][$i]['vAverage'] = $vAverage;
                    $partikel[$iterasi][$i]['vComplex'] = $vComplex;
                }

                //bandingan Partikel_i(t) dengan PBest_i(t-1)
                foreach ($partikel as $val) {
                    foreach ($val as $key => $x) {
                        if ($Pbest[$key]['ae'] > $x['ae']) {
                            $Pbest[$key] = $x;
                        }
                    }
                }
                $GBest = $this->minimalAE($Pbest);

                //Fungsi SPbest
                $CPbestIndex1 = array_rand($Pbest);
                $CPbestIndex2 = array_rand($Pbest);
                $CPbest1 = $Pbest[$CPbestIndex1];
                $CPbest2 = $Pbest[$CPbestIndex2];

                while ($counter < $max_counter) {
                    if ($CPbestIndex1 == $CPbestIndex2) {
                        $CPbestIndex1 = array_rand($Pbest);
                        $CPbestIndex2 = array_rand($Pbest);
                        $CPbest1 = $Pbest[$CPbestIndex1];
                        $CPbest2 = $Pbest[$CPbestIndex2];
                        $counter = 0;
                    } else {
                        break;
                    }
                }

                if ($CPbestIndex1 != $CPbestIndex2) {
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
                    foreach ($SPbest as $key => $val) {
                        if ($CPbest['ae'] < $val['ae']) {
                            $Pbest[$key] = $CPbest;
                        }
                    }
                }
                $SPbest = $Pbest;
            } // End of iterasi==0
            if ($iterasi != 0) {
                $R1[$iterasi] = $this->chaoticR1R2($R1[$iterasi - 1]);
                $R2[$iterasi] = $this->chaoticR1R2($R2[$iterasi - 1]);

                //Inertia weight
                $r[$iterasi] = 1.07 * ((7.86 * $r[$iterasi - 1]) - (23.31 * POW($r[$iterasi - 1], 2)) + (28.75 * POW($r[$iterasi - 1], 3)) - (13.302875 * POW($r[$iterasi - 1], 4)));
                $w = $r[$iterasi] * $this->INERTIA_MIN + ((($this->INERTIA_MAX - $this->INERTIA_MIN) * $iterasi) / $max_iter);

                //Update Velocity dan X_Posisi
                for ($i = 0; $i <= $swarm_size - 1; $i++) {
                    //Simple
                    $vSimple = ($w * $partikel[$iterasi - 1][$i]['vSimple']) + ($this->C1 * $R1[$iterasi]) * ($SPbest[$i]['xSimple'] - $partikel[$iterasi - 1][$i]['xSimple']) + ($this->C2 * $R2[$iterasi]) * ($GBest['xSimple'] - $partikel[$iterasi - 1][$i]['xSimple']);
                    $xSimple = $partikel[$iterasi - 1][$i]['xSimple'] + $vSimple;

                    //Average
                    $vAverage = ($w * $partikel[$iterasi - 1][$i]['vAverage']) + ($this->C1 * $R1[$iterasi]) * ($SPbest[$i]['xAverage'] - $partikel[$iterasi - 1][$i]['xAverage']) + ($this->C2 * $R2[$iterasi]) * ($GBest['xAverage'] - $partikel[$iterasi - 1][$i]['xAverage']);
                    $xAverage = $partikel[$iterasi - 1][$i]['xAverage'] + $vAverage;

                    //Complex
                    $vComplex = ($w * $partikel[$iterasi - 1][$i]['vComplex']) + ($this->C1 * $R1[$iterasi]) * ($SPbest[$i]['xComplex'] - $partikel[$iterasi - 1][$i]['xComplex']) + ($this->C2 * $R2[$iterasi]) * ($GBest['xComplex'] - $partikel[$iterasi - 1][$i]['xComplex']);
                    $xComplex = $partikel[$iterasi - 1][$i]['xComplex'] + $vComplex;

                    //exceeding limit
                    if ($xSimple < $arrLimit['xSimple']['xSimpleMin']) {
                        $xSimple = $arrLimit['xSimple']['xSimpleMin'];
                    }
                    if ($xSimple > $arrLimit['xSimple']['xSimpleMax']) {
                        $xSimple = $arrLimit['xSimple']['xSimpleMax'];
                    }
                    if ($xAverage < $arrLimit['xAverage']['xAverageMin']) {
                        $xAverage = $arrLimit['xAverage']['xAverageMin'];
                    }
                    if ($xAverage > $arrLimit['xAverage']['xAverageMax']) {
                        $xAverage = $arrLimit['xAverage']['xAverageMax'];
                    }
                    if ($xComplex < $arrLimit['xComplex']['xComplexMin']) {
                        $xComplex = $arrLimit['xComplex']['xComplexMin'];
                    }
                    if ($xComplex > $arrLimit['xComplex']['xComplexMax']) {
                        $xComplex = $arrLimit['xComplex']['xComplexMax'];
                    }
                    $ucSimple = $xSimple * $dataset['simpleUC'];
                    $ucAverage = $xAverage * $dataset['averageUC'];
                    $ucComplex = $xComplex * $dataset['complexUC'];

                    $UUCW = $ucSimple + $ucAverage + $ucComplex;
                    $UUCP = $UUCW + $dataset['uaw'];
                    $UCP = $UUCP * $dataset['tcf'] * $dataset['ecf'];
                    $estEffort = $UCP * $this->PRODUCTIVITY_FACTOR;

                    $partikel[$iterasi][$i]['estimatedEffort'] = $estEffort;
                    $partikel[$iterasi][$i]['ae'] = abs($estEffort - $dataset['actualEffort']);
                    $partikel[$iterasi][$i]['xSimple'] = $xSimple;
                    $partikel[$iterasi][$i]['xAverage'] = $xAverage;
                    $partikel[$iterasi][$i]['xComplex'] = $xComplex;
                    $partikel[$iterasi][$i]['vSimple'] = $vSimple;
                    $partikel[$iterasi][$i]['vAverage'] = $vAverage;
                    $partikel[$iterasi][$i]['vComplex'] = $vComplex;
                }
                //echo 'Iterasi: '.$iterasi;

                //bandingan Partikel_i(t) dengan PBest_i(t-1)
                foreach ($partikel as $val) {
                    foreach ($val as $key => $x) {
                        if ($Pbest[$key]['ae'] > $x['ae']) {
                            $Pbest[$key] = $x;
                        }
                    }
                }
                $GBest = $this->minimalAE($Pbest);

                //Fungsi SPbest
                $CPbestIndex1 = array_rand($Pbest);
                $CPbestIndex2 = array_rand($Pbest);
                $CPbest1 = $Pbest[$CPbestIndex1];
                $CPbest2 = $Pbest[$CPbestIndex2];

                while ($counter < $max_counter) {
                    if ($CPbestIndex1 == $CPbestIndex2) {
                        $CPbestIndex1 = array_rand($Pbest);
                        $CPbestIndex2 = array_rand($Pbest);
                        $CPbest1 = $Pbest[$CPbestIndex1];
                        $CPbest2 = $Pbest[$CPbestIndex2];
                        $counter = 0;
                    } else {
                        break;
                    }
                }

                if ($CPbestIndex1 != $CPbestIndex2) {
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
                    foreach ($SPbest as $key => $val) {
                        if ($CPbest['ae'] < $val['ae']) {
                            $Pbest[$key] = $CPbest;
                        }
                    }
                    //echo 'Tidak sama &nbsp<br>';
                }
                $SPbest = $Pbest;
            } // End of iterasi > 0

            //Fitness value evaluation
            if ($GBest['ae'] > $this->FITNESS_VALUE_BASELINE['polynomial']) {
                $temps[] = $GBest;
            } else {
                return $GBest;
            }
            $iterasi++;
        } // End of iterasi

        if (!empty($temps)) {
            $minAE = (min(array_column($temps, 'ae')));
            return $temps[array_search($minAE, $temps)];
        }
    } // End of main()

    function finishing($dataset, $max_iter, $swarm_size, $max_counter, $limit_percentage)
    {
        foreach ($dataset as $val) {
            $ret[] = $this->Main($val, $max_iter, $swarm_size, $max_counter, $limit_percentage);
        }
        return $ret;
    }

    function mae($data)
    {
        $sumMAE = array_sum(array_column($data, 'ae'));
        return $sumMAE / count($data);
    }

    function controllingPosition($predicted_datasets)
    {
        $flag = [];
        if ($predicted_datasets['xSimple'] < $this->simpleMin || $predicted_datasets['xSimple'] > $this->simpleMax) {
            $flag[] = 1;
        }
        if ($predicted_datasets['xAverage'] < $this->averageMin || $predicted_datasets['xAverage'] > $this->averageMax) {
            $flag[] = 1;
        }
        if ($predicted_datasets['xComplex'] < $this->complexMin || $predicted_datasets['xComplex'] > $this->complexMax) {
            $flag[] = 1;
        }
        return $flag;
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

//MEDIUM
// $dataset = array(
//     array('simpleUC' => 0, 'averageUC' => 17, 'complexUC' => 8, 'uaw' => 7, 'tcf' => 0.94, 'ecf' => 1.02, 'actualEffort' => 6474),
//     array('simpleUC' => 1, 'averageUC' => 13, 'complexUC' => 10, 'uaw' => 7, 'tcf' => 0.78, 'ecf' => 0.79, 'actualEffort' => 6416),
//     array('simpleUC' => 0, 'averageUC' => 14, 'complexUC' => 10, 'uaw' => 8, 'tcf' => 0.94, 'ecf' => 1.02, 'actualEffort' => 6412),
//     array('simpleUC' => 1, 'averageUC' => 10, 'complexUC' => 12, 'uaw' => 7, 'tcf' => 0.71, 'ecf' => 0.73, 'actualEffort' => 6360),
//     array('simpleUC' => 1, 'averageUC' => 11, 'complexUC' => 11, 'uaw' => 7, 'tcf' => 0.78, 'ecf' => 0.51, 'actualEffort' => 6232),
//     array('simpleUC' => 1, 'averageUC' => 14, 'complexUC' => 9, 'uaw' => 7, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 6173),
//     array('simpleUC' => 2, 'averageUC' => 13, 'complexUC' => 9, 'uaw' => 7, 'tcf' => 0.75, 'ecf' => 0.81, 'actualEffort' => 6062), 
//     array('simpleUC' => 1, 'averageUC' => 19, 'complexUC' => 5, 'uaw' => 6, 'tcf' => 0.965, 'ecf' => 0.755, 'actualEffort' => 6024),
//     array('simpleUC' => 0, 'averageUC' => 14, 'complexUC' => 8, 'uaw' => 6, 'tcf' => 0.98, 'ecf' => 0.97, 'actualEffort' => 5927),
//     array('simpleUC' => 5, 'averageUC' => 15, 'complexUC' => 5, 'uaw' => 6, 'tcf' => 1, 'ecf' => 0.92, 'actualEffort' => 5778),
// );

$MAX_ITER = 40;
$MAX_TRIAL = 1000;
$numDataset = count($dataset);
$swarm_size = 70;
$max_counter = 100000;
$limit_percentage = 0.35;

for ($max_iter = 1; $max_iter <= $MAX_ITER; $max_iter++) {
    $mpucwPSO = new MPUCWPSO();
    for ($trial = 0; $trial <= $MAX_TRIAL; $trial++) {
        $result = $mpucwPSO->finishing($dataset, $max_iter, $swarm_size, $max_counter, $limit_percentage);
        //calculcate MAE
        $mae = $mpucwPSO->mae($result);
        //save to array
        $maes[] = $mae;
        $results[] = $result;
    }
    //define best MAE
    $bestMAE = min($maes);
    //find index $bestMAE
    $bestMAEIndex = array_search($bestMAE, $maes);
    //save to final results
    $finalResults[] = $results[$bestMAEIndex];
    //clear array
    $maes = [];
    $results = [];
}
//Final Results
foreach ($finalResults as $val) {
    //calculate each MAE
    $mae = $mpucwPSO->mae($val);
    //save to array
    $maes[] = $mae;
    $results[] = $val;
}
//define best MAE
$bestMAE = min($maes);
//find index bestMAE
$bestMAEIndex = array_search($bestMAE, $maes);
//print final result and save to txt
echo 'Best MAE: ' . $bestMAE;
echo '<br>';
foreach ($results[$bestMAEIndex] as $key => $val) {
    echo $key . ' | ';
    $velocity_explotion = $mpucwPSO->controllingPosition($val);

    if (!empty($velocity_explotion)) {
        $total[] = 1;
        foreach ($velocity_explotion as $index => $use_case_weight) {
            if ($use_case_weight) {
                if ($index == 0) {
                    $counter['simple'][] = $use_case_weight;
                }
                if ($index == 1) {
                    $counter['average'][] = $use_case_weight;
                }
                if ($index == 2) {
                    $counter['complex'][] = $use_case_weight;
                }
            }
        }
    }

    echo $val['estimatedEffort'] . ' | ' . $val['ae'] . ' Simple: ' . $val['xSimple'];
    echo '<br>';
    $data = array($dataset[$key]['actualEffort'], $val['estimatedEffort'], $val['xSimple'], $val['xAverage'], $val['xComplex']);
    $fp = fopen('hasil_cmpso_singer_origin.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
}
echo 'Sum: ' . array_sum($total) . ' Percentage: ' . array_sum($total) / count($dataset);
echo '<br>';
foreach ($counter as $index => $value) {
    //print_r($value);
    if ($index == 0) {
        echo 'Simple: ' . array_sum($value);
    }
    if ($index == 1) {
        echo 'Average: ' . array_sum($value);
    }
    if ($index == 2) {
        echo 'Complex: ' . array_sum($value);
    }
    echo '<br>';
}
$maes = [];
$results = [];
