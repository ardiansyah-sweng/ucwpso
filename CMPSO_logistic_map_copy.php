<?php
set_time_limit(500);

class MPUCWPSO
{
    private $SWARM_SIZE = 30;
    private $PRODUCTIVITY_FACTOR = 20;
    private $MAX_ITERATION = 40;
    private $FITNESS_VALUE_BASELINE = array(
        'azzeh1' => 1219.8,
        'azzeh2' => 201.1,
        'azzeh3' => 1564.8,
        'silhavy' => 240.19,
        'karner' => 1820,
        'nassif' => 1712,
        'ardiansyah' => 404.85
    );
    private $AVOIDED_RANDOM_VALUE = array(0.00, 0.25, 0.50, 0.75, 1.00);
    private $INERTIA_MAX = 0.9;
    private $INERTIA_MIN = 0.4;
    private $C1 = 2.8;
    private $C2 = 1.3;

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
     * Menghitung estimasi effort
     * parameter: arr[UComplexity]
     * return: arr[Y']
     */
    function effortEstimation($UAW, $UUCW, $tcf, $ecf)
    {
        $UUCP = $UAW + $UUCW;
        $UCP  = $UUCP * $tcf * $ecf;
        $ret['size'] = $UCP;
        $ret['estimation'] = $UCP * $this->PRODUCTIVITY_FACTOR;
        return $ret;
        //return $UCP * $this->PRODUCTIVITY_FACTOR;
    }

    /**
     * Hitung absolute error
     */
    function absoluteError($arrEstimatedEffort, $actualEffort)
    {
        foreach ($arrEstimatedEffort as $key => $val) {
            $absoluteError[] = abs($val - $actualEffort);
        }
        return $absoluteError;
    }

    /**
     * Fungsi pengecekan $r0 != AVOIDED_RANDOM_VALUE
     * Jika sama ulangi random hingga tidak sama
     */
    function randomNumber($r0_baru)
    {
        $i = 0;
        while ($i < count($this->AVOIDED_RANDOM_VALUE)) {
            if ($this->AVOIDED_RANDOM_VALUE[$i] == $r0_baru) {
                $r0_baru = number_format($this->randomZeroToOne(), 2);
                $i = 0;
            }
            if ($this->AVOIDED_RANDOM_VALUE[$i] != $r0_baru) {
                $i++;
            }
        }
        return $r0_baru;
    }

    /**
     * Fungsi Stochastic PBest
     * Parameter: arrPartikel
     * Return: arrSPBest[posisi, ae, estimated effort]
     */
    function SPBest($partikel)
    {
        $i = 0;
        $CPbestIndex1 = array_rand($partikel);
        $CPbestIndex2 = array_rand($partikel);
        $CPbest1 = $partikel[$CPbestIndex1];
        $CPbest2 = $partikel[$CPbestIndex2];
        // echo '<p>Master<br>';
        // print_r($CPbest1);
        // echo '<br>';
        // print_r($CPbest2);
        // echo '<p>';

        if ($CPbestIndex1 != $CPbestIndex2) {
            if ($CPbest1['ae'] < $CPbest2['ae']) {
                $CPbest = $CPbest1;
            }
            if ($CPbest1['ae'] > $CPbest2['ae']) {
                $CPbest = $CPbest2;
            }
        }
        // echo 'Tingkat 1<br>';
        // print_r($CPbest);
        // echo '<br>';

        for ($i = 0; $i <= 10; $i++) {
            $CPbestIndex1 = array_rand($partikel);
            $CPbestIndex2 = array_rand($partikel);
            $CPbest1 = $partikel[$CPbestIndex1];
            $CPbest2 = $partikel[$CPbestIndex2];
            if ($CPbestIndex1 != $CPbestIndex2) {
                if ($CPbest1['ae'] < $CPbest2['ae']) {
                    $CPbest = $CPbest1;
                }
                if ($CPbest1['ae'] > $CPbest2['ae']) {
                    $CPbest = $CPbest2;
                }
                break;
            }
            // echo '<br>Tingkat 2<br>';
            // print_r($CPbest);
            // echo '<br>';
            // print_r($CPbest1);
            // echo '<br>';
            // print_r($CPbest2);
            // echo '<p>';
        }



        //while ($i < count($partikel)) {
        //Ambil acak 2 partikel dari populasi
        //Pilih yang AE terkecil
        //Yang terpilih menjadi CPbest
        //     if ($CPbestIndex1 == $CPbestIndex2) {
        //         $CPbestIndex1 = array_rand($partikel);
        //         $CPbestIndex2 = array_rand($partikel);
        //         $CPbest1 = $partikel[$CPbestIndex1];
        //         $CPbest2 = $partikel[$CPbestIndex2];
        //         $i = 0;
        //         echo $CPbest1.'gak ada'.$CPbest2;
        //     }
        //     if ($CPbestIndex1 != $CPbestIndex2) {
        //         if ($CPbest1['ae'] < $CPbest2['ae']) {
        //             $CPbest = $CPbest1;
        //         }
        //         if ($CPbest1['ae'] > $CPbest2['ae']) {
        //             $CPbest = $CPbest2;
        //         }
        //         break;
        //     }
        // }
        //print_r($CPbest);

        //Bandingkan CPbest dengan Pbest tiap partikel
        foreach ($partikel as $val) {
            if ($CPbest['ae'] < $val['ae']) {
                $ret[] = $CPbest;
            }
            if ($CPbest['ae'] > $val['ae'] || ($CPbest['ae'] == $val['ae'])) {
                $ret[] = $val;
            }
        }
        return $ret;
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

    function Main($dataset)
    {
        ##Generate Population
        for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
            $posisi[$i]['simple'] = $this->randomSimpleUCWeight();
            $posisi[$i]['average'] = $this->randomAverageUCWeight();
            $posisi[$i]['complex'] = $this->randomComplexUCWeight();

            $UComplexity[$i]['simple'] = $posisi[$i]['simple'] * $dataset['simpleUC'];
            $UComplexity[$i]['average'] = $posisi[$i]['average'] * $dataset['averageUC'];
            $UComplexity[$i]['complex'] = $posisi[$i]['complex'] * $dataset['complexUC'];

            $UUCW = $UComplexity[$i]['simple'] + $UComplexity[$i]['average'] + $UComplexity[$i]['complex'];

            $partikelAwal[$i]['estimatedEffort'] = $this->effortEstimation($dataset['uaw'], $UUCW, $dataset['tcf'], $dataset['ecf'], $dataset['actualEffort'])['estimation'];
            $partikelAwal[$i]['size'] = $this->effortEstimation($dataset['uaw'], $UUCW, $dataset['tcf'], $dataset['ecf'], $dataset['actualEffort'])['size'];
            $partikelAwal[$i]['ae'] = abs($partikelAwal[$i]['estimatedEffort'] - $dataset['actualEffort']);
            $partikelAwal[$i]['xSimple'] = $posisi[$i]['simple'];
            $partikelAwal[$i]['xAverage'] = $posisi[$i]['average'];
            $partikelAwal[$i]['xComplex'] = $posisi[$i]['complex'];
        }
        //print_r($partikelAwal);
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
        if ($CPbestIndex1 != $CPbestIndex2) {
            if ($CPbest1['ae'] < $CPbest2['ae']) {
                $CPbest = $CPbest1;
            }
            if ($CPbest1['ae'] > $CPbest2['ae']) {
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
        // echo '<p>';
        // print_r($GBest);

        ##End Generate Population



        //check if there are particles exceeds the lower or upper limit
        $limit_percentage = 0.1;
        $arrLimit = array(
            'xSimple' => array('xSimpleMin' => (5 - (5 * $limit_percentage)), 'xSimpleMax' => (7.49 + (7.49 * $limit_percentage))),
            'xAverage' => array('xAverageMin' => (7.5 - (7.5 * $limit_percentage)), 'xAverageMax' => (12.49 + (12.49 * $limit_percentage))),
            'xComplex' => array('xComplexMin' => (12.5 - (12.5 * $limit_percentage)), 'xComplexMax' => (15 + (15 * $limit_percentage))),
        );


        ##Masuk Iterasi
        $iterasi = 0;
        while ($iterasi <= $this->MAX_ITERATION - 1) {
            if ($iterasi == 0) {
                //Inertia weight
                //inisialisasi logistic map chaotic inertia weight
                $r0 = $this->randomNumber(number_format($this->randomZeroToOne(), 2));
                $r[$iterasi] = (4 * $r0) * (1 - $r0);
                $w = $r[$iterasi] * $this->INERTIA_MIN + ((($this->INERTIA_MAX - $this->INERTIA_MIN) * $iterasi) / $this->MAX_ITERATION);

                //Update Velocity dan X_Posisi
                for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
                    //Simple
                    $velocity[$iterasi][$i]['vSimple'] = $w * $this->randomZeroToOne() + $this->C1 * $this->randomZeroToOne() * ($SPbest[$i]['xSimple'] - $partikelAwal[$i]['xSimple']) + $this->C2 * $this->randomZeroToOne() * ($GBest['xSimple'] - $partikelAwal[$i]['xSimple']);
                    $posisi[$iterasi][$i]['xSimple'] = $partikelAwal[$i]['xSimple'] + $velocity[$iterasi][$i]['vSimple'];
                    $UComplexity[$iterasi][$i]['ucSimple'] = $posisi[$iterasi][$i]['xSimple'] * $dataset['simpleUC'];
                    //exceeding limit
                    if ($posisi[$iterasi][$i]['xSimple'] < $arrLimit['xSimple']['xSimpleMin']) {
                        $posisi[$iterasi][$i]['xSimple'] = $arrLimit['xSimple']['xSimpleMin'];
                    }
                    if ($posisi[$iterasi][$i]['xSimple'] > $arrLimit['xSimple']['xSimpleMax']) {
                        $posisi[$iterasi][$i]['xSimple'] = $arrLimit['xSimple']['xSimpleMax'];
                    }

                    //Average
                    $velocity[$iterasi][$i]['vAverage'] = $w * $this->randomZeroToOne() + $this->C1 * $this->randomZeroToOne() * ($SPbest[$i]['xAverage'] - $partikelAwal[$i]['xAverage']) + $this->C2 * $this->randomZeroToOne() * ($GBest['xAverage'] - $partikelAwal[$i]['xAverage']);
                    $posisi[$iterasi][$i]['xAverage'] = $partikelAwal[$i]['xAverage'] + $velocity[$iterasi][$i]['vAverage'];
                    $UComplexity[$iterasi][$i]['ucAverage'] = $posisi[$iterasi][$i]['xAverage'] * $dataset['averageUC'];
                    //exceeding limit
                    if ($posisi[$iterasi][$i]['xAverage'] < $arrLimit['xAverage']['xAverageMin']) {
                        $posisi[$iterasi][$i]['xAverage'] = $arrLimit['xAverage']['xAverageMin'];
                    }
                    if ($posisi[$iterasi][$i]['xAverage'] > $arrLimit['xAverage']['xAverageMax']) {
                        $posisi[$iterasi][$i]['xAverage'] = $arrLimit['xAverage']['xAverageMax'];
                    }

                    //Complex
                    $velocity[$iterasi][$i]['vComplex'] = $w * $this->randomZeroToOne() + $this->C1 * $this->randomZeroToOne() * ($SPbest[$i]['xComplex'] - $partikelAwal[$i]['xComplex']) + $this->C2 * $this->randomZeroToOne() * ($GBest['xComplex'] - $partikelAwal[$i]['xComplex']);
                    $posisi[$iterasi][$i]['xComplex'] = $partikelAwal[$i]['xComplex'] + $velocity[$iterasi][$i]['vComplex'];
                    $UComplexity[$iterasi][$i]['ucComplex'] = $posisi[$iterasi][$i]['xComplex'] * $dataset['complexUC'];
                    //exceeding limit
                    if ($posisi[$iterasi][$i]['xComplex'] < $arrLimit['xComplex']['xComplexMin']) {
                        $posisi[$iterasi][$i]['xComplex'] = $arrLimit['xComplex']['xComplexMin'];
                    }
                    if ($posisi[$iterasi][$i]['xComplex'] > $arrLimit['xComplex']['xComplexMax']) {
                        $posisi[$iterasi][$i]['xComplex'] = $arrLimit['xComplex']['xComplexMax'];
                    }

                    //UUCW
                    $UUCW = $UComplexity[$iterasi][$i]['ucSimple'] + $UComplexity[$iterasi][$i]['ucAverage'] + $UComplexity[$iterasi][$i]['ucComplex'];

                    $partikel[$iterasi][$i]['estimatedEffort'] = $this->effortEstimation($dataset['uaw'], $UUCW, $dataset['tcf'], $dataset['ecf'], $dataset['actualEffort'])['estimation'];
                    $partikel[$iterasi][$i]['size'] = $this->effortEstimation($dataset['uaw'], $UUCW, $dataset['tcf'], $dataset['ecf'], $dataset['actualEffort'])['size'];
                    $partikel[$iterasi][$i]['ae'] = abs($partikel[$iterasi][$i]['estimatedEffort'] - $dataset['actualEffort']);
                    $partikel[$iterasi][$i]['xSimple'] = $posisi[$iterasi][$i]['xSimple'];
                    $partikel[$iterasi][$i]['xAverage'] = $posisi[$iterasi][$i]['xAverage'];
                    $partikel[$iterasi][$i]['xComplex'] = $posisi[$iterasi][$i]['xComplex'];
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
                // print_r($partikel);
                // echo '<p>';
                // print_r($Pbest);
                // echo '<p>';
                // print_r($GBest);
                //Fungsi SPbest
                $CPbestIndex1 = array_rand($Pbest);
                $CPbestIndex2 = array_rand($Pbest);
                $CPbest1 = $Pbest[$CPbestIndex1];
                $CPbest2 = $Pbest[$CPbestIndex2];

                if ($CPbestIndex1 != $CPbestIndex2) {
                    if ($CPbest1['ae'] < $CPbest2['ae']) {
                        $CPbest = $CPbest1;
                    }
                    if ($CPbest1['ae'] > $CPbest2['ae']) {
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

                //jika index sama
                if ($CPbestIndex1 == $CPbestIndex2) {
                    //acak pbest lagi
                    $CPbestIndex1 = array_rand($Pbest);
                    $CPbestIndex2 = array_rand($Pbest);
                    $CPbest1 = $Pbest[$CPbestIndex1];
                    $CPbest2 = $Pbest[$CPbestIndex2];

                    //cek apakah masih sama atau tidak
                    if ($CPbestIndex1 != $CPbestIndex2) {
                        if ($CPbest1['ae'] < $CPbest2['ae']) {
                            $CPbest = $CPbest1;
                        }
                        if ($CPbest1['ae'] > $CPbest2['ae']) {
                            $CPbest = $CPbest2;
                        }
                        //compared CPbest with all Pbest_i(t)
                        foreach ($Pbest as $key => $val) {
                            if ($CPbest['ae'] < $val['ae']) {
                                $Pbest[$key] = $CPbest;
                            }
                        }
                    }
                    if ($CPbestIndex1 == $CPbestIndex2) {
                        for ($i = 0; $i <= 10; $i++) {
                            $CPbestIndex1 = array_rand($Pbest);
                            $CPbestIndex2 = array_rand($Pbest);
                            $CPbest1 = $Pbest[$CPbestIndex1];
                            $CPbest2 = $Pbest[$CPbestIndex2];
                            if ($CPbestIndex1 != $CPbestIndex2) {
                                break;
                                if ($CPbest1['ae'] < $CPbest2['ae']) {
                                    $CPbest = $CPbest1;
                                }
                                if ($CPbest1['ae'] > $CPbest2['ae']) {
                                    $CPbest = $CPbest2;
                                }
                            }
                            //compared CPbest with all Pbest_i(t)
                            foreach ($SPbest as $key => $val) {
                                if ($CPbest['ae'] < $val['ae']) {
                                    $Pbest[$key] = $CPbest;
                                }
                            }
                        }
                    }
                    //echo 'Sama';
                }
                $SPbest = $Pbest;
            } // End of iterasi==0
            if ($iterasi != 0) {
                //Inertia weight
                $r[$iterasi] = (4 * $r[$iterasi - 1]) * (1 - $r[$iterasi - 1]);
                $w = $r[$iterasi] * $this->INERTIA_MIN + ((($this->INERTIA_MAX - $this->INERTIA_MIN) * $iterasi) / $this->MAX_ITERATION);
                //Update Velocity dan X_Posisi
                for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
                    //Simple
                    $velocity[$iterasi][$i]['vSimple'] = ($w * $velocity[$iterasi - 1][$i]['vSimple']) + ($this->C1 * $this->randomZeroToOne()) * ($SPbest[$i]['xSimple'] - $partikel[$iterasi - 1][$i]['xSimple']) + ($this->C2 * $this->randomZeroToOne()) * ($GBest['xSimple'] - $partikel[$iterasi - 1][$i]['xSimple']);
                    $posisi[$iterasi][$i]['xSimple'] = $partikel[$iterasi - 1][$i]['xSimple'] + $velocity[$iterasi][$i]['vSimple'];
                    $UComplexity[$iterasi][$i]['ucSimple'] = $posisi[$iterasi][$i]['xSimple'] * $dataset['simpleUC'];
                    //exceeding limit
                    if ($posisi[$iterasi][$i]['xSimple'] < $arrLimit['xSimple']['xSimpleMin']) {
                        $posisi[$iterasi][$i]['xSimple'] = $arrLimit['xSimple']['xSimpleMin'];
                    }
                    if ($posisi[$iterasi][$i]['xSimple'] > $arrLimit['xSimple']['xSimpleMax']) {
                        $posisi[$iterasi][$i]['xSimple'] = $arrLimit['xSimple']['xSimpleMax'];
                    }

                    //Average
                    $velocity[$iterasi][$i]['vAverage'] = ($w * $velocity[$iterasi - 1][$i]['vAverage']) + ($this->C1 * $this->randomZeroToOne()) * ($SPbest[$i]['xAverage'] - $partikel[$iterasi - 1][$i]['xAverage']) + ($this->C2 * $this->randomZeroToOne()) * ($GBest['xAverage'] - $partikel[$iterasi - 1][$i]['xAverage']);
                    $posisi[$iterasi][$i]['xAverage'] = $partikel[$iterasi - 1][$i]['xAverage'] + $velocity[$iterasi][$i]['vAverage'];
                    $UComplexity[$iterasi][$i]['ucAverage'] = $posisi[$iterasi][$i]['xAverage'] * $dataset['averageUC'];
                    //exceeding limit
                    if ($posisi[$iterasi][$i]['xAverage'] < $arrLimit['xAverage']['xAverageMin']) {
                        $posisi[$iterasi][$i]['xAverage'] = $arrLimit['xAverage']['xAverageMin'];
                    }
                    if ($posisi[$iterasi][$i]['xAverage'] > $arrLimit['xAverage']['xAverageMax']) {
                        $posisi[$iterasi][$i]['xAverage'] = $arrLimit['xAverage']['xAverageMax'];
                    }

                    //Complex
                    $velocity[$iterasi][$i]['vComplex'] = ($w * $velocity[$iterasi - 1][$i]['vComplex']) + ($this->C1 * $this->randomZeroToOne()) * ($SPbest[$i]['xComplex'] - $partikel[$iterasi - 1][$i]['xComplex']) + ($this->C2 * $this->randomZeroToOne()) * ($GBest['xComplex'] - $partikel[$iterasi - 1][$i]['xComplex']);
                    $posisi[$iterasi][$i]['xComplex'] = $partikel[$iterasi - 1][$i]['xComplex'] + $velocity[$iterasi][$i]['vComplex'];
                    $UComplexity[$iterasi][$i]['ucComplex'] = $posisi[$iterasi][$i]['xComplex'] * $dataset['complexUC'];
                    //exceeding limit
                    if ($posisi[$iterasi][$i]['xComplex'] < $arrLimit['xComplex']['xComplexMin']) {
                        $posisi[$iterasi][$i]['xComplex'] = $arrLimit['xComplex']['xComplexMin'];
                    }
                    if ($posisi[$iterasi][$i]['xComplex'] > $arrLimit['xComplex']['xComplexMax']) {
                        $posisi[$iterasi][$i]['xComplex'] = $arrLimit['xComplex']['xComplexMax'];
                    }

                    //UUCW
                    $UUCW = $UComplexity[$iterasi][$i]['ucSimple'] + $UComplexity[$iterasi][$i]['ucAverage'] + $UComplexity[$iterasi][$i]['ucComplex'];

                    //Estimated Effort
                    $partikel[$iterasi][$i]['estimatedEffort'] = $this->effortEstimation($dataset['uaw'], $UUCW, $dataset['tcf'], $dataset['ecf'], $dataset['actualEffort'])['estimation'];
                    $partikel[$iterasi][$i]['size'] = $this->effortEstimation($dataset['uaw'], $UUCW, $dataset['tcf'], $dataset['ecf'], $dataset['actualEffort'])['size'];
                    $partikel[$iterasi][$i]['ae'] = abs($partikel[$iterasi][$i]['estimatedEffort'] - $dataset['actualEffort']);
                    $partikel[$iterasi][$i]['xSimple'] = $posisi[$iterasi][$i]['xSimple'];
                    $partikel[$iterasi][$i]['xAverage'] = $posisi[$iterasi][$i]['xAverage'];
                    $partikel[$iterasi][$i]['xComplex'] = $posisi[$iterasi][$i]['xComplex'];
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
                // print_r($partikel);
                // echo '<p>';
                // print_r($Pbest);
                // echo '<p>';
                // print_r($GBest);
                //Fungsi SPbest
                $CPbestIndex1 = array_rand($Pbest);
                $CPbestIndex2 = array_rand($Pbest);
                $CPbest1 = $Pbest[$CPbestIndex1];
                $CPbest2 = $Pbest[$CPbestIndex2];

                if ($CPbestIndex1 != $CPbestIndex2) {
                    if ($CPbest1['ae'] < $CPbest2['ae']) {
                        $CPbest = $CPbest1;
                    }
                    if ($CPbest1['ae'] > $CPbest2['ae']) {
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

                //jika index sama
                if ($CPbestIndex1 == $CPbestIndex2) {
                    //acak pbest lagi
                    $CPbestIndex1 = array_rand($Pbest);
                    $CPbestIndex2 = array_rand($Pbest);
                    $CPbest1 = $Pbest[$CPbestIndex1];
                    $CPbest2 = $Pbest[$CPbestIndex2];

                    //cek apakah masih sama atau tidak
                    if ($CPbestIndex1 != $CPbestIndex2) {
                        if ($CPbest1['ae'] < $CPbest2['ae']) {
                            $CPbest = $CPbest1;
                        }
                        if ($CPbest1['ae'] > $CPbest2['ae']) {
                            $CPbest = $CPbest2;
                        }
                        //compared CPbest with all Pbest_i(t)
                        foreach ($Pbest as $key => $val) {
                            if ($CPbest['ae'] < $val['ae']) {
                                $Pbest[$key] = $CPbest;
                            }
                        }
                    }
                    if ($CPbestIndex1 == $CPbestIndex2) {
                        for ($i = 0; $i <= 10; $i++) {
                            $CPbestIndex1 = array_rand($Pbest);
                            $CPbestIndex2 = array_rand($Pbest);
                            $CPbest1 = $Pbest[$CPbestIndex1];
                            $CPbest2 = $Pbest[$CPbestIndex2];
                            if ($CPbestIndex1 != $CPbestIndex2) {
                                break;
                                if ($CPbest1['ae'] < $CPbest2['ae']) {
                                    $CPbest = $CPbest1;
                                }
                                if ($CPbest1['ae'] > $CPbest2['ae']) {
                                    $CPbest = $CPbest2;
                                }
                            }
                            //compared CPbest with all Pbest_i(t)
                            foreach ($SPbest as $key => $val) {
                                if ($CPbest['ae'] < $val['ae']) {
                                    $Pbest[$key] = $CPbest;
                                }
                            }
                        }
                    }
                    // echo 'Sama';
                }
                $SPbest = $Pbest;
            } // End of iterasi > 0
            if ($GBest['ae'] > $this->FITNESS_VALUE_BASELINE['azzeh2']) {
                $temp[] = $GBest;
            } else {
                //echo '<br>Ono: ';
                //print_r($GBest);
                //$ae[] = $GBest['ae'];
                //echo '<br>';
                return $GBest;
                break;
            }
            $iterasi++;
        } // End of iterasi
        // print_r(!empty($temp));
        if (!empty($temp)) {
            return $temp;
            //     echo ' Ora ono : ';
            //     $minAE = (min(array_column($temp, 'ae')));
            //     //$ae[] = $minAE;
            //     //echo $minAE;
            //     print_r($temp[array_search($minAE, $temp)]);
        }
    } // End of main()

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

$start = microtime(true);
$mpucwPSO = new MPUCWPSO();
$numDataset = count($dataset);

$grandMAE = 0;
echo 'Jumlah data:' . $numDataset . '<br>';
for ($i = 0; $i <= 50 - 1; $i++) {
    echo '<b>Percobaan ke-' . $i . '</b><br> ';
    foreach ($dataset as $key => $val) {
        //print_r($mpucwPSO->Main($dataset[$key]));
        //echo '<br>';
        //if ($key <= 2) {
        //echo '<b>Dataset: ' . $key . '</b>';
        //echo '<br>';
        //print_r($dataset[$key]);
        //echo '<br>';
        // echo ' Estimated ';
        $result = $mpucwPSO->Main($dataset[$key]);

        if (count($result) == 40) {
            $minAE = min(array_column($result, 'ae'));
            //print_r($result[array_search($minAE, $result)]);
            echo $result[array_search($minAE, $result)]['estimatedEffort'];
            $arrAE[] = $minAE;
        } else {
            //print_r($result);
            $arrAE[] = $result['ae'];
            echo $result['estimatedEffort'];
        }
        // $size = $mpucwPSO->Main($dataset[$key])['size'];
        // $ae = $mpucwPSO->Main($dataset[$key])['ae'];
        // echo $key . ' ' . $size . ';' . $ae;
        //$arrSize[] = $size;
        //$arrAE[] = $ae;
        echo '<br>';
        //}
    }
    //$avgSize = array_sum($ae) / $numDataset;
    $avgAE = array_sum($arrAE) / $numDataset;
    $grandMAE = $grandMAE + $avgAE;
    echo 'MAE: ' . $avgAE;
    echo '<br>';
    //     $arrSize = [];
    $arrAE = [];
}
echo '<p>';
echo 'Grand MAE = ' . ($grandMAE / 50);
