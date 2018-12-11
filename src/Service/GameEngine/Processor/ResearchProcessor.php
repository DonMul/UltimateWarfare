<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\GameEngine\Processor;

use FrankProjects\UltimateWarfare\Entity\Report;
use FrankProjects\UltimateWarfare\Repository\ReportRepository;
use FrankProjects\UltimateWarfare\Repository\ResearchPlayerRepository;
use FrankProjects\UltimateWarfare\Service\GameEngine\Processor;
use FrankProjects\UltimateWarfare\Service\NetworthUpdaterService;

final class ResearchProcessor implements Processor
{
    /**
     * @var ResearchPlayerRepository
     */
    private $researchPlayerRepository;

    /**
     * @var ReportRepository
     */
    private $reportRepository;

    /**
     * @var NetworthUpdaterService
     */
    private $networthUpdaterService;

    /**
     * ResearchProcessor constructor.
     *
     * @param ResearchPlayerRepository $researchPlayerRepository
     * @param ReportRepository $reportRepository
     * @param NetworthUpdaterService $networthUpdaterService
     */
    public function __construct(
        ResearchPlayerRepository $researchPlayerRepository,
        ReportRepository $reportRepository,
        NetworthUpdaterService $networthUpdaterService
    ) {
        $this->researchPlayerRepository = $researchPlayerRepository;
        $this->reportRepository = $reportRepository;
        $this->networthUpdaterService = $networthUpdaterService;
    }

    /**
     * @param int $timestamp
     */
    public function run(int $timestamp): void
    {
        $researches = $this->researchPlayerRepository->getNonActiveCompletedResearch($timestamp);

        foreach ($researches as $researchPlayer) {
            $researchPlayer->setActive(true);

            $player = $researchPlayer->getPlayer();

            $research = $researchPlayer->getResearch();
            $finishedTimestamp = $researchPlayer->getTimestamp() + $research->getTimestamp();
            $message = "You successfully researched a new technology: {$research->getName()}";
            $report = Report::createForPlayer($player, $finishedTimestamp, 2, $message);

            $this->reportRepository->save($report);
            $this->researchPlayerRepository->save($researchPlayer);

            $this->networthUpdaterService->updateNetworthForPlayer($player);
        }
    }
}
