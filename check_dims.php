<?php
foreach (glob('uploads/members/*.jpg') as $f) {
    $info = getimagesize($f);
    if ($info) {
        $w = $info[0]; $h = $info[1];
        $ratio = round($w/$h, 2);
        echo basename($f) . " - {$w}x{$h} ratio={$ratio}\n";
    }
}
