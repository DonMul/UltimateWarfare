<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\Action;

use FrankProjects\UltimateWarfare\Entity\Construction;
use FrankProjects\UltimateWarfare\Entity\GameUnit;
use FrankProjects\UltimateWarfare\Entity\GameUnitType;
use FrankProjects\UltimateWarfare\Entity\Player;
use FrankProjects\UltimateWarfare\Entity\WorldRegion;
use FrankProjects\UltimateWarfare\Repository\ConstructionRepository;
use FrankProjects\UltimateWarfare\Repository\GameUnitRepository;
use FrankProjects\UltimateWarfare\Repository\PlayerRepository;
use FrankProjects\UltimateWarfare\Repository\WorldRegionUnitRepository;
use FrankProjects\UltimateWarfare\Service\NetworthUpdaterService;
use RuntimeException;

final class ConstructionActionService
{
    /**
     * @var ConstructionRepository
     */
    private $constructionRepository;

    /**
     * @var GameUnitRepository
     */
    private $gameUnitRepository;

    /**
     * @var PlayerRepository
     */
    private $playerRepository;

    /**
     * @var WorldRegionUnitRepository
     */
    private $worldRegionUnitRepository;

    /**
     * @var NetworthUpdaterService
     */
    private $networthUpdaterService;

    /**
     * ConstructionActionService constructor.
     *
     * @param ConstructionRepository $constructionRepository
     * @param GameUnitRepository $gameUnitRepository
     * @param PlayerRepository $playerRepository
     * @param WorldRegionUnitRepository $worldRegionUnitRepository
     * @param NetworthUpdaterService $networthUpdaterService
     */
    public function __construct(
        ConstructionRepository $constructionRepository,
        GameUnitRepository $gameUnitRepository,
        PlayerRepository $playerRepository,
        WorldRegionUnitRepository $worldRegionUnitRepository,
        NetworthUpdaterService $networthUpdaterService
    ) {
        $this->constructionRepository = $constructionRepository;
        $this->gameUnitRepository = $gameUnitRepository;
        $this->playerRepository = $playerRepository;
        $this->worldRegionUnitRepository = $worldRegionUnitRepository;
        $this->networthUpdaterService = $networthUpdaterService;
    }

    /**
     * @param WorldRegion $region
     * @param Player $player
     * @param GameUnitType $gameUnitType
     * @param array $constructionData
     * @throws \Exception
     */
    public function constructGameUnits(WorldRegion $region, Player $player, GameUnitType $gameUnitType, array $constructionData): void
    {
        $priceCash = 0;
        $priceWood = 0;
        $priceSteel = 0;
        $totalBuild = 0;
        $constructions = [];

        foreach ($constructionData as $gameUnitId => $amount) {
            $amount = intval($amount);
            if ($amount < 1) {
                continue;
            }

            $gameUnit = $this->gameUnitRepository->find($gameUnitId);
            if ($gameUnit === null) {
                continue;
            }

            if ($gameUnit->getGameUnitType()->getId() !== $gameUnitType->getId()) {
                continue;
            }

            $priceCash = $priceCash + ($amount * $gameUnit->getCost()->getCash());
            $priceWood = $priceWood + ($amount * $gameUnit->getCost()->getWood());
            $priceSteel = $priceSteel + ($amount * $gameUnit->getCost()->getSteel());

            if ($gameUnitType->getId() == 1) {
                $totalBuild = $totalBuild + $amount;
            }

            $constructions[] = Construction::create($region, $player, $gameUnit, $amount);
        }

        if ($gameUnitType->getId() == 1) {
            $buildingsInConstruction = $this->getCountGameUnitsInConstruction($region, $gameUnitType);
            $regionBuildings = $this->getCountGameUnitsInWorldRegion($region, $gameUnitType);
            $totalSpace = $region->getSpace() - $regionBuildings - $buildingsInConstruction;

            if ($totalBuild > $totalSpace) {
                throw new RunTimeException('You do not have that much building space.');
            }
        }

        $resources = $player->getResources();

        if ($priceCash > $resources->getCash()) {
            throw new RunTimeException("You don't have enough cash to build that.");
        }
        if ($priceWood > $resources->getWood()) {
            throw new RunTimeException("You don't have enough wood to build that.");
        }
        if ($priceSteel > $resources->getSteel()) {
            throw new RunTimeException("You don't have enough steel to build that.");
        }

        $resources->setCash($resources->getCash() - $priceCash);
        $resources->setWood($resources->getWood() - $priceWood);
        $resources->setSteel($resources->getSteel() - $priceSteel);

        $player->setResources($resources);
        $this->playerRepository->save($player);

        foreach ($constructions as $construction) {
            $this->constructionRepository->save($construction);
        }
    }

    /**
     * @param WorldRegion $region
     * @param Player $player
     * @param GameUnitType $gameUnitType
     * @param array $destroyData
     * @throws \Exception
     */
    public function removeGameUnits(WorldRegion $region, Player $player, GameUnitType $gameUnitType, array $destroyData): void
    {
        foreach ($destroyData as $gameUnitId => $amount) {
            $amount = intval($amount);
            if ($amount < 1) {
                continue;
            }

            $gameUnit = $this->gameUnitRepository->find($gameUnitId);
            if ($gameUnit === null) {
                continue;
            }

            if ($gameUnit->getGameUnitType()->getId() !== $gameUnitType->getId()) {
                continue;
            }

            $this->removeGameUnitsFromWorldRegion($region, $gameUnit, $amount);
        }

        $this->networthUpdaterService->updateNetworthForPlayer($player);
    }

    /**
     * @param Player $player
     * @param int $constructionId
     */
    public function cancelConstruction(Player $player, int $constructionId): void
    {
        $construction = $this->constructionRepository->find($constructionId);

        if (!$construction) {
            throw new RunTimeException('This construction queue does not exist!');
        }

        if ($construction->getPlayer()->getId() != $player->getId()) {
            throw new RunTimeException('This is not your construction queue!');
        }

        $this->constructionRepository->remove($construction);
    }

    /**
     * @param GameUnitType $gameUnitType
     * @param WorldRegion $worldRegion
     * @return int
     */
    public function getBuildingSpaceLeft(GameUnitType $gameUnitType, WorldRegion $worldRegion): int
    {
        if ($gameUnitType->getId() != 1) {
            return 0;
        }

        $buildingsInConstruction = $this->getCountGameUnitsInConstruction($worldRegion, $gameUnitType);
        $regionBuildings = $this->getCountGameUnitsInWorldRegion($worldRegion, $gameUnitType);

        return $worldRegion->getSpace() - $regionBuildings - $buildingsInConstruction;
    }

    /**
     * @param WorldRegion $worldRegion
     * @param GameUnitType $gameUnitType
     * @return int
     */
    public function getCountGameUnitsInConstruction(WorldRegion $worldRegion, GameUnitType $gameUnitType): int
    {
        $buildingsInConstruction = 0;
        foreach ($worldRegion->getConstructions() as $regionConstruction) {
            if ($regionConstruction->getGameUnit()->getGameUnitType()->getId() == $gameUnitType->getId()) {
                $buildingsInConstruction += $regionConstruction->getNumber();
            }
        }

        return $buildingsInConstruction;
    }

    /**
     * @param WorldRegion $worldRegion
     * @param GameUnitType $gameUnitType
     * @return int
     */
    public function getCountGameUnitsInWorldRegion(WorldRegion $worldRegion, GameUnitType $gameUnitType): int
    {
        $regionBuildings = 0;
        foreach ($worldRegion->getWorldRegionUnits() as $regionUnit) {
            if ($regionUnit->getGameUnit()->getGameUnitType()->getId() == $gameUnitType->getId()) {
                $regionBuildings += $regionUnit->getAmount();
            }
        }

        return $regionBuildings;
    }

    /**
     * @param WorldRegion $worldRegion
     * @param GameUnit $gameUnit
     * @param int $amount
     */
    private function removeGameUnitsFromWorldRegion(WorldRegion $worldRegion, GameUnit $gameUnit, int $amount): void
    {
        foreach ($worldRegion->getWorldRegionUnits() as $worldRegionUnit) {
            if ($worldRegionUnit->getGameUnit()->getId() !== $gameUnit->getId()) {
                continue;
            }

            if ($amount > $worldRegionUnit->getAmount()) {
                throw new RunTimeException('You do not have that many ' . $gameUnit->getName() . "s!");
            }

            $worldRegionUnit->setAmount($worldRegionUnit->getAmount() - $amount);
            $this->worldRegionUnitRepository->save($worldRegionUnit);
        }
    }
}
