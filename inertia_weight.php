<?php

$max_iteration  = 1000;
$max_inertia = 0.9;
$min_inertia = 0.4;
$random_zeroToOne = (float) rand() / (float) getrandmax();
$r = [];
for ($i = 0; $i < 500; $i++) {


    if ($i == 0 && $random_zeroToOne == 0) {
        $r[$i] = 0;
    }
    if ($i == 0 && $random_zeroToOne != 0) {
        $r[$i] = 1/fmod(1,$i);
    }
    if ($r[$i] == 0){
        $r[$i+1] = 0;
    }
    $r[$i+1] = 1/fmod(1,$i);

    // iterative
    // if ($i == 0){
    //     $r[$i] = sin(($random_zeroToOne * pi()) / $random_zeroToOne);
    // }
    // $r[$i+1] = sin(($random_zeroToOne * pi()) / $r[$i]);
    echo $r[$i] . '<br>';

    // $P = mt_rand(0.01 * 100, 0.5 * 100) / 100;
    // // Piecewise
    // if ($i == 0) {
    //     //P >= r[iterasi] >= 0
    //     if ($random_zeroToOne >= 0 && $random_zeroToOne <= $P) {
    //         $r[$i] = $random_zeroToOne / $P;
    //     }
    //     // 0.5 >= r[$iterasi] >= P
    //     if ($random_zeroToOne >= $P && $random_zeroToOne <= 0.5) {
    //         $r[$i] = ($random_zeroToOne - $P) / (0.5 - $P);
    //     }
    //     //1-P >= r[$iterasi] >= 0.5
    //     if ($random_zeroToOne >= 0.5 && $random_zeroToOne <= (1 - $P)) {
    //         $r[$i] = (1 - $P - $random_zeroToOne) / (0.5 - $P);
    //     }
    //     //1 >= r[iterasi] >= 1-P
    //     if ($random_zeroToOne >= (1 - $P) && $random_zeroToOne <= 1) {
    //         $r[$i] = (1 - $random_zeroToOne) / $P;
    //     }
    // }
    // //P >= r[iterasi] >= 0
    // if ($r[$i] >= 0 && $r[$i] <= $P) {
    //     $r[$i+1] = $r{$i} / $P;
    // }
    // // 0.5 >= r[$iterasi] >= P
    // if ($r[$i] >= $P && $r[$i] <= 0.5) {
    //     $r[$i+1] = ($r[$i] - $P) / (0.5 - $P);
    // }
    // //1-P >= r[$iterasi] >= 0.5
    // if ($r[$i] >= 0.5 && $r[$i] <= (1 - $P)) {
    //     $r[$i+1] = (1 - $P - $r[$i]) / (0.5 - $P);
    // }
    // //1 >= r[iterasi] >= 1-P
    // if ($r[$i] >= (1 - $P) && $r[$i] <= 1) {
    //     $r[$i+1] = (1 - $r[$i]) / $P;
    // }
    // echo $r[$i] . '<br>';


    // Logistic
    // if ($i == 0){
    //     $r[$i] = (4 * $random_zeroToOne) * (1 - $random_zeroToOne);
    // }
    // $r[$i+1] = (4 * $r[$i]) * (1 - $r[$i]);
    // echo $r[$i] . '<br>';

    //circle
    // if ($i == 0) {
    //     $r[$i] = fmod($random_zeroToOne + 0.2 - (0.5 / (2 * pi())) * sin(2 * pi() * $random_zeroToOne), 1);
    //     //$r[$i] = fmod(($random_zeroToOne + 0.2 - (0.5 / (2 * pi()))) * sin(2 * pi() * $random_zeroToOne), 1);
    // }
    // $r[$i + 1] = fmod($r[$i] + 0.2 - (0.5 / (2 * pi())) * sin(2 * pi() * $r[$i]), 1);
    // echo $r[$i] . '<br>';

    // chebyshev
    // if ($i == 0){
    //     $r[$i] = cos($i * cos(pow($random_zeroToOne,-1)));
    // }
    // $r[$i+1] = cos($i * cos(pow($r[$i],-1)));
    // echo $r[$i].'<br>';

    // Tent
    // if ($i == 0){
    //     if ($random_zeroToOne < 0.7) {
    //         $r[$i] = $random_zeroToOne / 0.7;
    //     } else {
    //         $r[$i] = (10 / 3) * (1 - $random_value);
    //     }
    // }
    // if ($r[$i] < 0.7) {
    //     $r[$i+1] = $r[$i] / 0.7;
    // } else {
    //     $r[$i+1] = (10 / 3) * (1 - $r[$i]);
    // }
    // echo $r[$i].'<br>';


    //Sinusoidal
    // if ($i == 0){
    //     $r[$i] = (2.3 * POW(0.7, 2)) * sin(pi() * 0.7);
    // }
    // $r[$i+1] = (2.3 * POW($r[$i], 2)) * sin(pi() * $r[$i]);
    // echo $i.' '.$r[$i].' '.$r[$i+1].'<br>';

    // $w = $r * $min_inertia + ((($max_inertia - $min_inertia) * $i) / $max_iteration);
    // echo $w . '<br>';

    //
    // echo (float) rand() / (float) getrandmax();
    // echo '<br>';

    // Singer map
    // if ($i==0){
    //     $r[$i] = 1.07 * ((7.86 * $random_zeroToOne) - (23.31 * POW($random_zeroToOne,2)) + (28.75 * POW($random_zeroToOne,3)) - (13.302875 * POW($random_zeroToOne,4)));
    //     echo $r[$i];
    //     echo '<br>';
    // }
    // $r[$i+1] = 1.07 * ((7.86 * $r[$i]) - (23.31 * POW($r[$i],2)) + (28.75 * POW($r[$i],3)) - (13.302875 * POW($r[$i],4)));
    // echo $r[$i+1];
    // echo '<br>';
}
