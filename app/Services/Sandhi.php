<?php

namespace App\Services;

class Sandhi
{
    public function calc($dataset, $size = 4)
    {
        $oldItem = false;
        $diffs = [];
        foreach ($dataset as $datasetIndex => $data)
        {
            if ($oldItem === false) $oldItem = $data;
            $diffs[$datasetIndex] = $oldItem - $data;
        }

        asort($diffs);
        return array_slice($diffs, $size);
    }

}