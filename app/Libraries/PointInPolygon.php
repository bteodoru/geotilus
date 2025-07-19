<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Class optimized for determining the position of a point relative to a polygon
 * Uses the ray casting algorithm for point-in-polygon checks
 *
 * Optimized for soil classification in ternary diagrams
 * @author B. Teodoru
 * @license MIT
 * @version 2.0
 */

class PointInPolygon
{
    /**
     * Constants for point position results
     */

    public const INSIDE = 'inside';
    public const OUTSIDE = 'outside';
    public const ON_BOUNDARY = 'on_boundary';
    public const ON_VERTEX = 'on_vertex';

    /**
     * Floating point comparison tolerance
     */
    private const EPSILON = 1e-9;

    private bool $checkPointOnVertex;
    private bool $checkPointOnBoundary;
    private float $tolerance;

    /**
     * Constructor for PointInPolygon
     * 
     * @param bool $checkPointOnVertex If true, checks if the point is exactly on a vertex
     * @param bool $checkPointOnBoundary If true, checks if the point is on the boundary of the polygon
     * @param float $tolerance The tolerance for floating point comparisons
     */
    public function __construct(
        bool $checkPointOnVertex = true,
        bool $checkPointOnBoundary = true,
        float $tolerance = self::EPSILON
    ) {
        $this->checkPointOnVertex = $checkPointOnVertex;
        $this->checkPointOnBoundary = $checkPointOnBoundary;
        $this->tolerance = $tolerance;
    }

    /**
     * Determines the position of a point relative to a polygon
     * 
     * @param array<float> $point The point to check [x, y]
     * @param array<array<float>> $polygonPoints The vertices of the polygon [[x1,y1], [x2,y2], ...]
     * @return string One of the class constants (INSIDE, OUTSIDE, ON_BOUNDARY, ON_VERTEX)
     * 
     * @throws \InvalidArgumentException for invalid input
     */
    public function pointInPolygon(array $point, array $polygonPoints): string
    {
        $this->validateInput($point, $polygonPoints);

        // Check if the point is exactly on a vertex
        if ($this->checkPointOnVertex && $this->isPointOnVertex($point, $polygonPoints)) {
            return self::ON_VERTEX;
        }

        // Check if the point is on the boundary of the polygon
        if ($this->checkPointOnBoundary && $this->isPointOnBoundary($point, $polygonPoints)) {
            return self::ON_BOUNDARY;
        }

        // Raycast algorithm to determine if the point is inside the polygon
        // If the point is on a vertex or boundary, it is not considered inside
        return $this->raycastCheck($point, $polygonPoints) ? self::INSIDE : self::OUTSIDE;
    }

    /**
     * Checks if a point is inside a polygon using the ray casting algorithm without checking for boundaries or vertices.
     * This method is optimized for batch processing and does not throw exceptions for invalid input.
     * 
     * @param array<float> $point The point to check [x, y]
     * @param array<array<float>> $polygonPoints The vertices of the polygon [[x1,y1], [x2,y2], ...]
     * @return bool True if the point is inside the polygon, false otherwise
     */
    public function isPointInside(array $point, array $polygonPoints): bool
    {
        $this->validateInput($point, $polygonPoints);
        return $this->raycastCheck($point, $polygonPoints);
    }

    /**
     * Compute the minimum distance from a point to the edges of a polygon
     * Useful for calculating the confidence score in soil classification
     * 
     * @param array<float> $point The point to check [x, y]
     * @param array<array<float>> $polygonPoints The vertices of the polygon [[x1,y1], [x2,y2], ...]
     * @return float The minimum distance
     */
    public function getMinDistanceToPolygon(array $point, array $polygonPoints): float
    {
        $this->validateInput($point, $polygonPoints);

        $minDistance = PHP_FLOAT_MAX;
        $vertexCount = count($polygonPoints);

        for ($i = 0; $i < $vertexCount; $i++) {
            $vertex1 = $polygonPoints[$i];
            $vertex2 = $polygonPoints[($i + 1) % $vertexCount];

            $distance = $this->pointToLineDistance($point, $vertex1, $vertex2);
            $minDistance = min($minDistance, $distance);
        }

        return $minDistance;
    }

    /**
     * Validates the input for the class methods
     * 
     * @param array<float> $point The point to check [x, y]
     * @param array<array<float>> $polygonPoints The vertices of the polygon [[x1,y1], [x2,y2], ...]
     * @throws \InvalidArgumentException
     */
    private function validateInput(array $point, array $polygonPoints): void
    {
        if (count($point) !== 2) {
            throw new \InvalidArgumentException('Punctul trebuie să aibă exact 2 coordonate [x, y]');
        }

        if (count($polygonPoints) < 3) {
            throw new \InvalidArgumentException('Poligonul trebuie să aibă cel puțin 3 vârfuri');
        }

        foreach ($polygonPoints as $index => $vertex) {
            if (count($vertex) !== 2) {
                throw new \InvalidArgumentException("Vârful {$index} trebuie să aibă exact 2 coordonate [x, y]");
            }
        }

        // Validate if the point coordinates are numeric
        if (!is_numeric($point[0]) || !is_numeric($point[1])) {
            throw new \InvalidArgumentException('Coordonatele punctului trebuie să fie numerice');
        }
    }

    /**
     * Checks if the point is exactly on a vertex of the polygon
     * 
     * @param array<float> $point The point to check [x, y]
     * @param array<array<float>> $polygonPoints The vertices of the polygon [[x1,y1], [x2,y2], ...]
     * @return bool True if the point is on a vertex, false otherwise
     */
    private function isPointOnVertex(array $point, array $polygonPoints): bool
    {
        foreach ($polygonPoints as $vertex) {
            if (
                $this->isFloatEqual($point[0], $vertex[0]) &&
                $this->isFloatEqual($point[1], $vertex[1])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the point is on the boundary of the polygon
     * 
     * @param array<float> $point The point to check [x, y]
     * @param array<array<float>> $polygonPoints The vertices of the polygon [[x1,y1], [x2,y2], ...]
     * @return bool True if the point is on the boundary, false otherwise
     */
    private function isPointOnBoundary(array $point, array $polygonPoints): bool
    {
        $vertexCount = count($polygonPoints);

        for ($i = 0; $i < $vertexCount; $i++) {
            $vertex1 = $polygonPoints[$i];
            $vertex2 = $polygonPoints[($i + 1) % $vertexCount];

            if ($this->isPointOnLineSegment($point, $vertex1, $vertex2)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Raycast algorithm to determine if the point is inside the polygon
     * 
     * @param array<float> $point The point to check [x, y]
     * @param array<array<float>> $polygonPoints The vertices of the polygon [[x1,y1], [x2,y2], ...]
     * @return bool True if the point is inside the polygon, false otherwise
     */
    private function raycastCheck(array $point, array $polygonPoints): bool
    {
        $intersections = 0;
        $vertexCount = count($polygonPoints);

        for ($i = 0; $i < $vertexCount; $i++) {
            $vertex1 = $polygonPoints[$i];
            $vertex2 = $polygonPoints[($i + 1) % $vertexCount];

            // Checks if the horizontal ray intersects the edge
            if ($this->rayIntersectsSegment($point, $vertex1, $vertex2)) {
                $intersections++;
            }
        }

        // Inside point if the number of intersections is odd
        return ($intersections % 2) === 1;
    }

    /** 
     * Checks if the horizontal ray from the point intersects the segment
     * 
     * @param array<float> $point The point to check [x, y]
     * @param array<float> $vertex1 The first vertex of the segment [x1, y1]
     * @param array<float> $vertex2 The second vertex of the segment [x2, y2]
     * @return bool True if the ray intersects the segment, false otherwise
     */
    private function rayIntersectsSegment(array $point, array $vertex1, array $vertex2): bool
    {
        // The segment is horizontal at the same Y level as the point
        if (
            $this->isFloatEqual($vertex1[1], $vertex2[1]) &&
            $this->isFloatEqual($vertex1[1], $point[1])
        ) {
            return false; // Managed in the boundary check
        }

        //The point is outside the Y range of the segment
        if (
            $point[1] < min($vertex1[1], $vertex2[1]) ||
            $point[1] >= max($vertex1[1], $vertex2[1])
        ) {
            return false;
        }

        // Compute the X coordinate of the intersection
        $xIntersection = $vertex1[0] +
            ($point[1] - $vertex1[1]) * ($vertex2[0] - $vertex1[0]) / ($vertex2[1] - $vertex1[1]);

        return $point[0] < $xIntersection;
    }

    /**
     * Checks if a point is on a line segment defined by two vertices
     * 
     * @param array<float> $point The point to check [x, y]
     * @param array<float> $vertex1 The first vertex of the segment [x1, y1]
     * @param array<float> $vertex2 The second vertex of the segment [x2, y2]
     * @return bool True if the point is on the segment, false otherwise
     */
    private function isPointOnLineSegment(array $point, array $vertex1, array $vertex2): bool
    {
        // Check if the point is collinear with the segment
        $crossProduct = ($point[1] - $vertex1[1]) * ($vertex2[0] - $vertex1[0]) -
            ($point[0] - $vertex1[0]) * ($vertex2[1] - $vertex1[1]);

        if (!$this->isFloatEqual($crossProduct, 0)) {
            return false; // Nu este coliniar
        }

        // Check if the point is within the bounding box of the segment
        $dotProduct = ($point[0] - $vertex1[0]) * ($vertex2[0] - $vertex1[0]) +
            ($point[1] - $vertex1[1]) * ($vertex2[1] - $vertex1[1]);

        $segmentLengthSquared = pow($vertex2[0] - $vertex1[0], 2) + pow($vertex2[1] - $vertex1[1], 2);

        return $dotProduct >= 0 && $dotProduct <= $segmentLengthSquared;
    }

    /**
     * Compute the distance from a point to a line segment defined by two vertices
     * 
     * @param array<float> $point The point to check [x, y]
     * @param array<float> $vertex1 The first vertex of the segment [x1, y1]
     * @param array<float> $vertex2 The second vertex of the segment [x2, y2]
     * @return float The distance from the point to the segment
     */
    private function pointToLineDistance(array $point, array $vertex1, array $vertex2): float
    {
        $segmentLengthSquared = pow($vertex2[0] - $vertex1[0], 2) + pow($vertex2[1] - $vertex1[1], 2);

        if ($this->isFloatEqual($segmentLengthSquared, 0)) {
            // Vertex1 and vertex2 are the same point
            return sqrt(pow($point[0] - $vertex1[0], 2) + pow($point[1] - $vertex1[1], 2));
        }

        $t = max(0, min(1, (($point[0] - $vertex1[0]) * ($vertex2[0] - $vertex1[0]) +
            ($point[1] - $vertex1[1]) * ($vertex2[1] - $vertex1[1])) / $segmentLengthSquared));

        $projectionX = $vertex1[0] + $t * ($vertex2[0] - $vertex1[0]);
        $projectionY = $vertex1[1] + $t * ($vertex2[1] - $vertex1[1]);

        return sqrt(pow($point[0] - $projectionX, 2) + pow($point[1] - $projectionY, 2));
    }

    /**
     * Compares two floating point numbers with a tolerance
     * 
     * @param float $a
     * @param float $b
     * @return bool True if the numbers are considered equal within the tolerance, false otherwise
     */
    private function isFloatEqual(float $a, float $b): bool
    {
        return abs($a - $b) < $this->tolerance;
    }
}
