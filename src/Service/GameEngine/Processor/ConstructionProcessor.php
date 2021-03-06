<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\GameEngine\Processor;

use FrankProjects\UltimateWarfare\Entity\Construction;
use FrankProjects\UltimateWarfare\Entity\Player;
use FrankProjects\UltimateWarfare\Entity\Report;
use FrankProjects\UltimateWarfare\Entity\WorldRegionUnit;
use FrankProjects\UltimateWarfare\Repository\ConstructionRepository;
use FrankProjects\UltimateWarfare\Repository\PlayerRepository;
use FrankProjects\UltimateWarfare\Repository\ReportRepository;
use FrankProjects\UltimateWarfare\Repository\WorldRegionUnitRepository;
use FrankProjects\UltimateWarfare\Service\GameEngine\Processor;
use FrankProjects\UltimateWarfare\Service\NetworthUpdaterService;

final class ConstructionProcessor implements Processor
{
    /**
     * @var ConstructionRepository
     */
    private $constructionRepository;

    /**
     * @var PlayerRepository
     */
    private $playerRepository;

    /**
     * @var ReportRepository
     */
    private $reportRepository;

    /**
     * @var WorldRegionUnitRepository
     */
    private $worldRegionUnitRepository;

    /**
     * @var NetworthUpdaterService
     */
    private $networthUpdaterService;

    /**
     * ConstructionProcessor constructor.
     *
     * @param ConstructionRepository $constructionRepository
     * @param PlayerRepository $playerRepository
     * @param ReportRepository $reportRepository
     * @param WorldRegionUnitRepository $worldRegionUnitRepository
     * @param NetworthUpdaterService $networthUpdaterService
     */
    public function __construct(
        ConstructionRepository $constructionRepository,
        PlayerRepository $playerRepository,
        ReportRepository $reportRepository,
        WorldRegionUnitRepository $worldRegionUnitRepository,
        NetworthUpdaterService $networthUpdaterService
    ) {
        $this->constructionRepository = $constructionRepository;
        $this->playerRepository = $playerRepository;
        $this->reportRepository = $reportRepository;
        $this->worldRegionUnitRepository = $worldRegionUnitRepository;
        $this->networthUpdaterService = $networthUpdaterService;
    }

    /**
     * @param int $timestamp
     */
    public function run(int $timestamp): void
    {
        $constructions = $this->constructionRepository->getCompletedConstructions($timestamp);

        foreach ($constructions as $construction) {
            $worldRegion = $construction->getWorldRegion();

            if ($worldRegion->getPlayer()->getId() !== $construction->getPlayer()->getId()) {
                // Never process construction queue items for a region that no longer belongs to this player
                $this->constructionRepository->remove($construction);
                continue;
            }

            $this->processConstruction($construction);
        }
    }

    /**
     * @param Player $player
     * @param Construction $construction
     * @return Player
     */
    private function updatePlayerResources(Player $player, Construction $construction): Player
    {
        $upkeepCash = $construction->getNumber() * $construction->getGameUnit()->getUpkeep()->getCash();
        $upkeepFood = $construction->getNumber() * $construction->getGameUnit()->getUpkeep()->getFood();
        $upkeepWood = $construction->getNumber() * $construction->getGameUnit()->getUpkeep()->getWood();
        $upkeepSteel = $construction->getNumber() * $construction->getGameUnit()->getUpkeep()->getSteel();

        $incomeCash = $construction->getNumber() * $construction->getGameUnit()->getIncome()->getCash();
        $incomeFood = $construction->getNumber() * $construction->getGameUnit()->getIncome()->getFood();
        $incomeWood = $construction->getNumber() * $construction->getGameUnit()->getIncome()->getWood();
        $incomeSteel = $construction->getNumber() * $construction->getGameUnit()->getIncome()->getSteel();

        $income = $player->getIncome();
        $upkeep = $player->getUpkeep();

        $upkeep->addCash($upkeepCash);
        $upkeep->addFood($upkeepFood);
        $upkeep->addWood($upkeepWood);
        $upkeep->addSteel($upkeepSteel);

        $income->addCash($incomeCash);
        $income->addFood($incomeFood);
        $income->addWood($incomeWood);
        $income->addSteel($incomeSteel);

        $player->setIncome($income);
        $player->setUpkeep($upkeep);

        return $player;
    }

    /**
     * @param Construction $construction
     */
    private function processConstruction(Construction $construction): void
    {
        // XXX TODO: Process income before processing construction...
        //$this->processPlayerIncome($construction->getPlayer(), $timestamp);

        $worldRegionUnit = $this->getWorldRegionUnit($construction);

        if ($worldRegionUnit !== null) {
            $worldRegionUnit->setAmount($construction->getNumber());
        } else {
            $worldRegionUnit = WorldRegionUnit::create($construction->getWorldRegion(), $construction->getGameUnit(), $construction->getNumber());
        }

        $player = $this->updatePlayerResources($construction->getPlayer(), $construction);
        $this->createConstructionReport($construction);

        $this->worldRegionUnitRepository->save($worldRegionUnit);
        $this->playerRepository->save($player);
        $this->constructionRepository->remove($construction);

        $this->networthUpdaterService->updateNetworthForPlayer($player);
    }

    /**
     * @param Construction $construction
     */
    private function createConstructionReport(Construction $construction): void
    {
        $reportType = Report::TYPE_GENERAL;
        if ($construction->getNumber() > 1) {
            $message = "You completed {$construction->getNumber()} {$construction->getGameUnit()->getNameMulti()}!";
        } else {
            $message = "You completed {$construction->getNumber()} {$construction->getGameUnit()->getName()}!";
        }

        $finishedConstructionTime = $construction->getTimestamp() + $construction->getGameUnit()->getTimestamp();
        $report = Report::createForPlayer($construction->getPlayer(), $finishedConstructionTime, $reportType, $message);
        $this->reportRepository->save($report);
    }

    /**
     * @param Construction $construction
     * @return WorldRegionUnit|null
     */
    private function getWorldRegionUnit(Construction $construction): ?WorldRegionUnit
    {
        $worldRegion = $construction->getWorldRegion();
        $worldRegionUnit = null;
        foreach ($worldRegion->getWorldRegionUnits() as $worldRegionUnitObject) {
            if ($worldRegionUnitObject->getGameUnit()->getId() == $construction->getGameUnit()->getId()) {
                $worldRegionUnit = $worldRegionUnitObject;
                break;
            }
        }

        return $worldRegionUnit;
    }
}
