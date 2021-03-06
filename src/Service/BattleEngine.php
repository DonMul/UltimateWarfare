<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service;

use FrankProjects\UltimateWarfare\Entity\Fleet;
use FrankProjects\UltimateWarfare\Service\BattleEngine\BattlePhase;
use FrankProjects\UltimateWarfare\Service\BattleEngine\BattleReportCreator;
use FrankProjects\UltimateWarfare\Service\BattleEngine\BattleResult;
use FrankProjects\UltimateWarfare\Service\BattleEngine\BattleUpdaterService;
use RuntimeException;

final class BattleEngine
{
    /**
     * @var BattleUpdaterService
     */
    private $battleUpdaterService;

    /**
     * @var BattleReportCreator
     */
    private $battleReportCreator;

    /**
     * @var NetworthUpdaterService
     */
    private $networthUpdaterService;

    /**
     * @var IncomeUpdaterService
     */
    private $incomeUpdaterService;

    /**
     * BattleEngine constructor.
     *
     * @param BattleUpdaterService $battleUpdaterService
     * @param BattleReportCreator $battleReportCreator
     * @param NetworthUpdaterService $networthUpdaterService
     * @param IncomeUpdaterService $incomeUpdaterService
     */
    public function __construct(
        BattleUpdaterService $battleUpdaterService,
        BattleReportCreator $battleReportCreator,
        NetworthUpdaterService $networthUpdaterService,
        IncomeUpdaterService $incomeUpdaterService
    ) {
        $this->battleUpdaterService = $battleUpdaterService;
        $this->battleReportCreator = $battleReportCreator;
        $this->networthUpdaterService = $networthUpdaterService;
        $this->incomeUpdaterService = $incomeUpdaterService;
    }

    /**
     * XXX TODO: Add train station/airfield/harbor support
     * XXX TODO: Add battle report
     * XXX TODO: Add battle "lost units" summary
     *
     * @param Fleet $fleet
     * @return BattleResult
     */
    public function battle(Fleet $fleet): BattleResult
    {
        $this->ensureCanAttack($fleet);

        $attackerGameUnits = $fleet->getFleetUnits()->toArray();
        $defenderGameUnits = $fleet->getTargetWorldRegion()->getWorldRegionUnits()->toArray();

        $battlePhaseResults = [];
        foreach ($this->getBattlePhases() as $battlePhaseName) {
            $battlePhase = BattlePhase::factory($battlePhaseName, $attackerGameUnits, $defenderGameUnits);
            $battlePhase->startBattlePhase();

            $attackerGameUnits = $battlePhase->getAttackerGameUnits();
            $defenderGameUnits = $battlePhase->getDefenderGameUnits();

            $battlePhaseResults[] = $battlePhase;
        }

        $battleResults = new BattleResult($battlePhaseResults);

        $this->processResults($battleResults, $fleet, $attackerGameUnits, $defenderGameUnits);

        return $battleResults;
    }

    /**
     * @return array
     */
    private function getBattlePhases(): array
    {
        return [
            BattlePhase::AIR_PHASE,
            BattlePhase::SEA_PHASE,
            BattlePhase::GROUND_PHASE
        ];
    }

    /**
     * @param Fleet $fleet
     */
    private function ensureCanAttack(Fleet $fleet): void
    {
        if ($fleet->getTimestampArrive() > time()) {
            throw new RunTimeException("Fleet not arrived yet");
        }

        $targetPlayer = $fleet->getTargetWorldRegion()->getPlayer();
        if ($targetPlayer === null) {
            throw new RunTimeException("Target region has no owner");
        }

        if ($fleet->getPlayer()->getId() === $targetPlayer->getId()) {
            throw new RunTimeException("You can not attack yourself");
        }

        if (count($targetPlayer->getWorldRegions()) === 1) {
            throw new RunTimeException("Target player has only 1 region left");
        }

        if ($targetPlayer->getTimestampJoined() + 172800 > time()) {
            throw new RunTimeException("You can not attack this player in the first 48 hours");
        }
    }

    /**
     * @param BattleResult $battleResults
     * @param Fleet $fleet
     * @param array $attackerGameUnits
     * @param array $defenderGameUnits
     */
    private function processResults(BattleResult $battleResults, Fleet $fleet, array $attackerGameUnits, array $defenderGameUnits): void
    {
        $defendingPlayer = $fleet->getTargetWorldRegion()->getPlayer();
        $timestamp = time();

        if ($battleResults->hasWon()) {
            $this->battleUpdaterService->updateBattleWon($fleet, $attackerGameUnits);
            $this->battleReportCreator->createBattleWonReports($fleet, $timestamp);
        } else {
            $this->battleUpdaterService->updateBattleLost($fleet, $attackerGameUnits, $defenderGameUnits);
            $this->battleReportCreator->createBattleLostReports($fleet, $timestamp);
        }

        $this->networthUpdaterService->updateNetworthForPlayer($fleet->getPlayer());
        $this->networthUpdaterService->updateNetworthForPlayer($defendingPlayer);

        $this->incomeUpdaterService->updateIncomeForPlayer($fleet->getPlayer());
        $this->incomeUpdaterService->updateIncomeForPlayer($defendingPlayer);
    }
}
