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

    private array $sourceData;
    private array $exampleData;

    public function setSourceData(array $sourceData)
    {
        $this->sourceData = $sourceData;
    }

    public function setExampleData(array $exampleData)
    {
        $this->exampleData = $exampleData;
    }

    /**
     * set Strategy
     */
    public function setAlgorithm(AlgorithmInterface $algorithm): void
    {
        $this->algorithm = $algorithm;
    }

    public function getBestCase(array $processCases, array $scores): array
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

    public function getMoved(array $case): array
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

    public function toStr(array $array): string
    {
        return implode(',', $array);
    }

    public function execute(): array
    {
        $archiveCase = [];
        $processCases = [$this->sourceData];

        $costs = [$this->toStr($this->sourceData) => 0];
        $scores = [$this->toStr($this->sourceData) => $this->algorithm->handle($this->sourceData, $this->exampleData)];

        $result = [];

        while ($processCases) {

            $case = $this->getBestCase($processCases, $scores);

            if ($case == $this->exampleData) {
                $result[] = $case;
                return $result;
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
                } else if ($cost >= $costs[$this->toStr($moved)]) {
                    continue;
                }

                $result[$this->toStr($case)] = $case;
                $costs[$this->toStr($moved)] = $cost;
                $scores[$this->toStr($moved)] = $costs[$this->toStr($moved)] + $this->algorithm->handle($moved, $this->exampleData);
            }
        }
    }
}

class View
{
    public static function render(array $data, bool $isHtml): void
    {
        $n = $isHtml ? '<br>' : "\n";
        foreach (array_chunk($data, 3) as $value) {
            echo implode('|', $value) . $n;
        }
        echo $n;
    }
}


//$algorithm = new HammingDistance();
$algorithm = new ManhattanDistance();

$puzzle = new Puzzle();
$puzzle->setSourceData([
//    0,2,3,
//    1,5,6,
//    4,7,8
1, 2, 3,
4, 5, 6,
7, 8, 0
]);
$puzzle->setExampleData([
    1, 2, 3,
    4, 0, 6,
    7, 8, 5
]);
$puzzle->setAlgorithm($algorithm);
$result = $puzzle->execute();

print_r(count($result));
exit();

echo 'Algorithm: ' . get_class($algorithm) . "<br>\n";
echo 'TotalCase: ' . count($result) . "<br>\n";
echo "<br>\n";

if (count($result) > 100) {
    $result = array_merge(
        array_slice($result, 0, 49),
        array_slice($result, 50)
    );
}

foreach ($result as $key => $value) {
    View::render($value, false);
}

