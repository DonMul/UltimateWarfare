<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Entity;

/**
 * FleetUnit
 */
class FleetUnit
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $amount;

    /**
     * @var Fleet
     */
    private $fleet;

    /**
     * @var GameUnit
     */
    private $gameUnit;

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount(int $amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return Fleet
     */
    public function getFleet(): Fleet
    {
        return $this->fleet;
    }

    /**
     * @param Fleet $fleet
     */
    public function setFleet(Fleet $fleet)
    {
        $this->fleet = $fleet;
    }

    /**
     * @return GameUnit
     */
    public function getGameUnit(): GameUnit
    {
        return $this->gameUnit;
    }

    /**
     * @param GameUnit $gameUnit
     */
    public function setGameUnit(GameUnit $gameUnit)
    {
        $this->gameUnit = $gameUnit;
    }

    /**
     * @param Fleet $fleet
     * @param GameUnit $gameUnit
     * @param int $amount
     * @return FleetUnit
     */
    public static function createForFleet(Fleet $fleet, GameUnit $gameUnit, int $amount): FleetUnit
    {
        $fleetUnit = new FleetUnit();
        $fleetUnit->setGameUnit($gameUnit);
        $fleetUnit->setAmount($amount);
        $fleetUnit->setFleet($fleet);

        return $fleetUnit;
    }
}
