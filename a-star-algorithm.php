<?php

interface AlgorithmInterface
{
    public function handle(array $sourceData, array $exampleData): int;
}

/**
 * Concrete heuristic function
 */
class HammingDistance implements AlgorithmInterface
{
    public function handle(array $sourceData, array $exampleData): int
    {
        $dist = 0;
        $cnt = count($sourceData);

        for ($i = 0; $i < $cnt; $i++) {
            $src = $sourceData[$i];
            $exp = $exampleData[$i];
            if ($src > 0 && $src !== $exp) {
                $dist++;
            }
        }

        return $dist;
    }
}

/**
 * Concrete heuristic function
 */
class ManhattanDistance implements AlgorithmInterface
{
    private function getX(array $data, int $findValue): int
    {
        $searchIndex = array_search($findValue, $data);
        $result = ($searchIndex + 1) % 3;
        return $result === 0 ? 3 : $result;
    }

    private function getY(array $data, int $findValue): int
    {
        $searchIndex = array_search($findValue, $data);
        $result = ($searchIndex + 1) / 3;
        return ceil($result);
    }

    public function handle(array $sourceData, array $exampleData): int
    {
        $result = 0;
        $cnt = count($sourceData);

        for ($i = 0; $i < $cnt; $i++) {
            $src = $sourceData[$i];
            $exp = $exampleData[$i];
            if ($src > 0 && $src !== $exp) {
                $distX = abs(
                    $this->getX($sourceData, $src) - $this->getX($exampleData, $src)
                );

                $distY = abs(
                    $this->getY($sourceData, $src) - $this->getY($exampleData, $src)
                );

                $result += $distX + $distY;
            }
        }

        return $result;
    }
}

class Puzzle
{
    private AlgorithmInterface $algorithm;

    private array $startData;
    private array $goalData;
    public array $relations;

    public function setStartData(array $sourceData)
    {
        $this->startData = $sourceData;
    }

    public function setGoalData(array $exampleData)
    {
        $this->goalData = $exampleData;
    }

    /**
     * set Strategy
     */
    public function setAlgorithm(AlgorithmInterface $algorithm): void
    {
        $this->algorithm = $algorithm;
    }

    private function getBestCase(array $processCases, array $scores): array
    {
        $bestCase = $processCases[0];
        $minScore = $scores[$this->toStr($bestCase)];

        foreach ($processCases as $item) {
            if ($scores[$this->toStr($item)] < $minScore) {
                $minScore = $scores[$this->toStr($item)];
                $bestCase = $item;
            }
        }
        return $bestCase;
    }

    private function getMoved(array $case): array
    {
        $results = [];
        $index = array_search(0, $case, true);

        if ($index >= 3) {
            $topMove = $case;

            $topMove[$index - 3] = $case[$index];
            $topMove[$index] = $case[$index - 3];

            $results[] = $topMove;
        }

        if ($index <= 5) {
            $bottomMove = $case;

            $bottomMove[$index + 3] = $case[$index];
            $bottomMove[$index] = $case[$index + 3];

            $results[] = $bottomMove;
        }

        if (!in_array($index, [0, 3, 6])) {
            $leftMove = $case;

            $leftMove[$index - 1] = $case[$index];
            $leftMove[$index] = $case[$index - 1];

            $results[] = $leftMove;
        }

        if (!in_array($index, [2, 5, 8])) {
            $rightMove = $case;

            $rightMove[$index + 1] = $case[$index];
            $rightMove[$index] = $case[$index + 1];

            $results[] = $rightMove;
        }

        return $results;
    }

    private function toStr(array $array): string
    {
        return implode(',', $array);
    }

    private function restorePath(): array
    {
        $result = [$this->goalData];
        $key = $this->toStr($this->goalData);
        do{
            $value = $this->relations[$key];
            $key = $this->toStr($value);
            $result[] = $value;
        }while($value!=$this->startData);

        return array_reverse($result);
    }

    public function execute(): array
    {
        $archiveCase = [];
        $processCases = [$this->startData];

        $costs = [$this->toStr($this->startData) => 0];
        $scores = [$this->toStr($this->startData) => $this->algorithm->handle($this->startData, $this->goalData)];

        while (count($processCases) > 0) {
            $case = $this->getBestCase($processCases, $scores);

            if ($case == $this->goalData) {
                return $this->restorePath();
            }

            $find = array_search($case, $processCases);
            unset($processCases[$find]);
            $processCases = array_values($processCases);

            $archiveCase[] = $case;

            foreach ($this->getMoved($case) as $moved) {
                if (!$moved || in_array($moved, $archiveCase)) {
                    continue;
                }

                $cost = $costs[$this->toStr($case)] + 1;

                if (!in_array($moved, $processCases)) {
                    $processCases[] = $moved;
                } else {
                    if ($cost >= $costs[$this->toStr($moved)]) {
                        continue;
                    }
                }

                $costs[$this->toStr($moved)] = $cost;
                $scores[$this->toStr($moved)] = $costs[$this->toStr($moved)]
                    + $this->algorithm->handle(
                        $moved,
                        $this->goalData
                    );

                //result
                $this->relations[$this->toStr($moved)] = $case;
            }
        }
    }
}
$startTime = microtime(true);

// Config
$start = [
    6, 2, 8,
    4, 0, 5,
    1, 7, 3
];
$goal = [
    1, 2, 3,
    4, 5, 6,
    7, 8, 0
];
//$algorithm = new ManhattanDistance();
$algorithm = new HammingDistance();
$showLimit = 10;
$br = "<br>";
//$br = "\n"; //for console

// Execute
$puzzle = new Puzzle();
$puzzle->setStartData($start);
$puzzle->setGoalData($goal);
$puzzle->setAlgorithm($algorithm);
$result = $puzzle->execute();

// Report
echo 'Algorithm: '.get_class($algorithm).$br;
echo 'Total case: '.count($puzzle->relations).$br;
echo 'Total step: '.count($result).$br;
echo 'Running time: '. (microtime(true) - $startTime) .$br;
echo $br;

// Render result
foreach ($result as $key=>$value) {
    if ($value == 'separator') {
        echo '...'.$br.$br;
        continue;
    }

    foreach (array_chunk($value, 3) as $item) {
        echo implode('|', $item).$br;
    }
    echo $br;
}