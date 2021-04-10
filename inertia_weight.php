<?php

$max_iteration  = 500;
$max_inertia = 0.9;
$min_inertia = 0.4;
$initial_value = (float) rand() / (float) getrandmax();
// $initial_value = 0.7;
$r = [];
for ($i = 0; $i < $max_iteration; $i++) {

    // LDW inertia
    // $w = $max_inertia - ((($max_inertia - $min_inertia) * $i) / $max_iteration);
    // echo $w. '<br>';
    $w = ($min_inertia - $max_inertia) * (($max_inertia - $i) / $max_iteration) + $min_inertia;
    echo $w. '<br>';

    // if ($i === 0){
    //     if ($random_zeroToOne > 0 && $random_zeroToOne <= (1 - (1 / 2))) {
    //         $r[$i] = $random_zeroToOne / (1 - (1 / 2));
    //     }
    //     // 1-alpha < r[iterasi] < 1
    //     if ($random_zeroToOne > (1 - (1 / 2)) && $random_zeroToOne < 1) {
    //         $r[$i] = ($random_zeroToOne - (1 - (1 / 2))) / (1 / 2);
    //     }
    // }
    // if ($r[$i] > 0 && $r[$i] <= (1 - (1 / 2))) {
    //     $r[$i+1] = $r[$i] / (1 - (1 / 2));
    // }
    // // 1-alpha < r[iterasi] < 1
    // if ($r[$i] > (1 - (1 / 2)) && $r[$i] < 1) {
    //     $r[$i+1] = ($r[$i] - (1 - (1 / 2))) / (1 / 2);
    // }
    // echo $r[$i] . '<br>';
    
    //gauss
    // if ($i === 0){
    //     $r[$i] = fmod(1/$initial_value,1);        
    // }
    // $r[$i+1] = fmod(1/$r[$i],1);   
    // echo $r[$i] . '<br>';

    // Liebovitch
    // $P1 = (float) rand() / (float) getrandmax();
    // $P2 = (float) rand() / (float) getrandmax();
    // if ($i == 0){
    //     $counter = 0;
    //     while ($counter < 10) {
    //         if ($P1 > $P2) {
    //             $P1 = (float) rand() / (float) getrandmax();
    //             $P2 = (float) rand() / (float) getrandmax();
    //             $counter = 0;
    //         }
    //         if ($P1 < $P2) {
    //             $counter++;
    //         }
    //     }
    //     $alpha = ($P2 / $P1) * (1 - ($P2 - $P1));
    //     $betha = (1 / ($P2 - 1)) * (($P2 - 1) - ($P1 * ($P2 - $P1)));
    //     //0 < r[iterasi] <= P1
    //     if ($random_zeroToOne > 0 && $random_zeroToOne <= $P1) {
    //         $r[$i] = $alpha * $random_zeroToOne;
    //     }
    //     //P1 < r[iterasi] <= P2
    //     if ($random_zeroToOne > $P1 && $random_zeroToOne <= $P2) {
    //         $r[$i] = ($P2 - $random_zeroToOne) / ($P2 - $P1);
    //     }
    //     //P2 < r[iterasi] <= 1
    //     if ($random_zeroToOne > $P2 && $random_zeroToOne <= 1) {
    //         $r[$i] = 1 - ($betha * (1 - $random_zeroToOne));
    //     }
    // }
    // $counter = 0;
    // while ($counter < 10) {
    //     if ($P1 > $P2) {
    //         $P1 = (float) rand() / (float) getrandmax();
    //         $P2 = (float) rand() / (float) getrandmax();
    //         $counter = 0;
    //     }
    //     if ($P1 < $P2) {
    //         $counter++;
    //     }
    // }
    // $alpha = ($P2 / $P1) * (1 - ($P2 - $P1));
    // $betha = (1 / ($P2 - 1)) * (($P2 - 1) - ($P1 * ($P2 - $P1)));
    // //0 < r[iterasi] <= P1
    // if ($r[$i] > 0 && $r[$i] <= $P1) {
    //     $r[$i+1] = $alpha * $r[$i];
    // }
    // //P1 < r[iterasi] <= P2
    // if ($r[$i] > $P1 && $r[$i] <= $P2) {
    //     $r[$i+1] = ($P2 - $r[$i]) / ($P2 - $P1);
    // }
    // //P2 < r[iterasi] <= 1
    // if ($r[$i] > $P2 && $r[$i] <= 1) {
    //     $r[$i+1] = 1 - ($betha * (1 - $r[$i]));
    // }

    // iterative
    // if ($i == 0){
    //     $r[$i] = sin(($random_zeroToOne * pi()) / $random_zeroToOne);
    // }
    // $r[$i+1] = sin(($random_zeroToOne * pi()) / $r[$i]);

    // Piecewise
    // $P = mt_rand(0.01 * 100, 0.5 * 100) / 100;
    // if ($i == 0) {
    //     //P >= r[iterasi] >= 0
    //     if ($initial_value >= 0 && $initial_value <= $P) {
    //         $r[$i] = $initial_value / $P;
    //     }
    //     // 0.5 >= r[$iterasi] >= P
    //     if ($initial_value >= $P && $initial_value <= 0.5) {
    //         $r[$i] = ($initial_value - $P) / (0.5 - $P);
    //     }
    //     //1-P >= r[$iterasi] >= 0.5
    //     if ($initial_value >= 0.5 && $initial_value <= (1 - $P)) {
    //         $r[$i] = (1 - $P - $initial_value) / (0.5 - $P);
    //     }
    //     //1 >= r[iterasi] >= 1-P
    //     if ($initial_value >= (1 - $P) && $initial_value <= 1) {
    //         $r[$i] = (1 - $initial_value) / $P;
    //     }
    // }
    // //P >= r[iterasi] >= 0
    // if ($r[$i] >= 0 && $r[$i] <= $P) {
    //     $r[$i+1] = $r[$i] / $P;
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
    //     $r[$i] = cos($i * cos(pow($initial_value,-1)));
    // }
    // $r[$i+1] = cos($i * cos(pow($r[$i],-1)));
    // echo $r[$i] * $min_inertia + ((($max_inertia - $min_inertia) * $i) / $max_iteration). '<br>';

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

    //Sine
    // if ($i === 0){
    //     $r[$i] = sin(pi()*$initial_value);
    // }
    // $r[$i+1] = sin(pi()*$r[$i]);
    // echo $r[$i].'<br>';

    //Sinusoidal
    // if ($i === 0){
    //     $r[$i] = 2.3 * pow($initial_value,2) * sin(pi()*$initial_value);
    // }
    // $r[$i+1] = 2.3 * pow($r[$i],2) * sin(pi()*$r[$i]);

    // echo $r[$i] * $min_inertia + ((($max_inertia - $min_inertia) * $i) / $max_iteration). '<br>';

    // Singer map
    // if ($i==0){
    //     $r[$i] = 1.07 * ((7.86 * 0.7) - (23.31 * POW(0.7,2)) + (28.75 * POW(0.7,3)) - (13.302875 * POW(0.7,4)));
    //     echo $r[$i];
    //     echo '<br>';
    // }
    // $r[$i+1] = 1.07 * ((7.86 * $r[$i]) - (23.31 * POW($r[$i],2)) + (28.75 * POW($r[$i],3)) - (13.302875 * POW($r[$i],4)));
    // echo $r[$i+1];
    // echo '<br>';
}
