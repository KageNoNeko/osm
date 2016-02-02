<?php
namespace KageNoNeko\OSM;

/**
 * Class BoundingBox
 *
 * @package KageNoNeko\OSM
 */
class BoundingBox
{

    /**
     * South latitude coordinate
     *
     * @var float
     */
    protected $south;

    /**
     * West longitude coordinate
     *
     * @var float
     */
    protected $west;

    /**
     * North latitude coordinate
     *
     * @var float
     */
    protected $north;

    /**
     * East longitude coordinate
     *
     * @var float
     */
    protected $east;

    /**
     * Slices of bounding box
     *
     * @var \KageNoNeko\OSM\BoundingBox[]|null
     */
    protected $slices;

    /**
     * Flush slices of bounding box
     *
     * @return void
     */
    protected function flushSlices() {
        $this->slices = null;
    }

    /**
     * Split bounding box to lesser boxes
     *
     *            [NE]
     *    [0][1][2]
     *    [3][4][5]
     *    [6][7][8]
     * [SW]
     *
     * @param int $latitudeDegrees
     * @param int $longitudeDegrees
     *
     * @return \KageNoNeko\OSM\BoundingBox[]|null
     */
    protected function splitByDegrees($latitudeDegrees, $longitudeDegrees) {
        $latitudeDegrees = (int)$latitudeDegrees;
        $longitudeDegrees = (int)$longitudeDegrees;

        $counts = $this->countSlices($latitudeDegrees, $longitudeDegrees, false);
        if (!($counts['latitude'] * $counts['longitude'] > 1)) {
            return null;
        }

        $slices = [];
        $north = $this->north;
        for ($i = 0; $i < $counts['latitude']; $i++) {
            $south = $north - $latitudeDegrees;
            if ($south < $this->south) {
                $south = $this->south;
            }
            $west = $this->west;

            for ($j = 0; $j < $counts['longitude']; $j++) {
                $east = $west + $longitudeDegrees;
                if ($east > $this->east) {
                    $east = $this->east;
                }

                $slices[] = new BoundingBox($south, $west, $north, $east);

                $west = $east;
            }
            $north = $south;
        }

        return $slices;
    }

    public function __set($key, $value) {
        if (!in_array($key, ['south', 'west', 'north', 'east'])) {
            throw new \RuntimeException("Property '{$key}' doesn't exists and cannot be set.");
        }
        $this->{$key} = floatval($value);
        $this->flushSlices();
    }

    public function __get($key) {
        if (!in_array($key, ['south', 'west', 'north', 'east'])) {
            throw new \RuntimeException("Property '{$key}' doesn't exists and cannot be get.");
        }

        return $this->{$key};
    }

    public function __toString() {
        return "{$this->south},{$this->west},{$this->north},{$this->east}";
    }

    public function __construct($south, $west, $north, $east) {
        $this->south = $south;
        $this->west = $west;
        $this->north = $north;
        $this->east = $east;
    }

    public function countSlices($latitudeDegrees, $longitudeDegrees, $total = true) {
        $latitude = ($this->north - $this->south) / (int)$latitudeDegrees;
        $longitude = ($this->east - $this->west) / (int)$longitudeDegrees;

        // floating point fix
        if (($this->north - $this->south) % (int)$latitudeDegrees != 0) {
            $latitude = ceil($latitude);
        }
        $latitude = intval($latitude);
        if (($this->east - $this->west) % (int)$longitudeDegrees != 0) {
            $longitude = ceil($longitude);
        }
        $longitude = intval($longitude);

        return ($total ? $latitude * $longitude : compact('latitude', 'longitude'));
    }

    public function canSplit($latitudeDegrees, $longitudeDegrees) {
        return $this->countSlices($latitudeDegrees, $longitudeDegrees, true) > 1;
    }

    public function slices($latitudeDegrees, $longitudeDegrees) {
        if (is_null($this->slices)) {
            $this->slices = $this->splitByDegrees($latitudeDegrees, $longitudeDegrees);
        }

        return $this->slices;
    }
}