<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\Action;

use FrankProjects\UltimateWarfare\Entity\Player;
use FrankProjects\UltimateWarfare\Entity\Research;
use FrankProjects\UltimateWarfare\Entity\ResearchPlayer;
use FrankProjects\UltimateWarfare\Repository\PlayerRepository;
use FrankProjects\UltimateWarfare\Repository\ResearchPlayerRepository;
use FrankProjects\UltimateWarfare\Repository\ResearchRepository;
use RuntimeException;

final class ResearchActionService
{
    /**
     * @var PlayerRepository
     */
    private $playerRepository;

    /**
     * @var ResearchRepository
     */
    private $researchRepository;

    /**
     * @var ResearchPlayerRepository
     */
    private $researchPlayerRepository;

    /**
     * ResearchActionService service
     *
     * @param ResearchRepository $researchRepository
     * @param ResearchPlayerRepository $researchPlayerRepository
     * @param PlayerRepository $playerRepository
     */
    public function __construct(
        ResearchRepository $researchRepository,
        ResearchPlayerRepository $researchPlayerRepository,
        PlayerRepository $playerRepository
    ) {
        $this->researchRepository = $researchRepository;
        $this->researchPlayerRepository = $researchPlayerRepository;
        $this->playerRepository = $playerRepository;
    }

    /**
     * @param int $researchId
     * @param Player $player
     */
    public function performResearch(int $researchId, Player $player): void
    {
        $research = $this->getResearchById($researchId);

        $this->ensureCanResearch($research, $player);

        $researchPlayer = new ResearchPlayer();
        $researchPlayer->setPlayer($player);
        $researchPlayer->setResearch($research);
        $researchPlayer->setTimestamp(time());

        $resources = $player->getResources();
        $resources->setCash($resources->getCash() - $research->getCost());

        $player->setResources($resources);
        $this->playerRepository->save($player);
        $this->researchPlayerRepository->save($researchPlayer);
    }

    /**
     * @param int $researchId
     * @param Player $player
     */
    public function performCancel(int $researchId, Player $player): void
    {
        $research = $this->getResearchById($researchId);

        /** @var ResearchPlayer $playerResearch */
        foreach ($player->getPlayerResearch() as $playerResearch) {
            if ($playerResearch->getResearch()->getId() !== $research->getId()) {
                continue;
            }

            if ($playerResearch->getActive()) {
                throw new RunTimeException('Research project is already completed!');
            }

            $this->researchPlayerRepository->remove($playerResearch);
        }
    }

    /**
     * @param int $researchId
     * @return Research
     */
    private function getResearchById(int $researchId): Research
    {
        $research = $this->researchRepository->find($researchId);

        if ($research === null) {
            throw new RunTimeException('This technology does not exist!');
        }

        if (!$research->getActive()) {
            throw new RunTimeException('This technology is disabled!');
        }

        return $research;
    }

    /**
     * @param Research $research
     * @param Player $player
     */
    private function ensureCanResearch(Research $research, Player $player): void
    {
        $researchArray = [];

        /** @var ResearchPlayer $playerResearch */
        foreach ($player->getPlayerResearch() as $playerResearch) {
            if (!$playerResearch->getActive()) {
                throw new RunTimeException('You can only research 1 technology at a time!');
            }

            if ($playerResearch->getResearch()->getId() === $research->getId()) {
                throw new RunTimeException('This technology has already been researched!');
            }

            $researchArray[$playerResearch->getResearch()->getId()] = $playerResearch->getResearch();
        }

        foreach ($research->getResearchNeeds() as $researchNeed) {
            if (!isset($researchArray[$researchNeed->getRequiredResearch()->getId()])) {
                throw new RunTimeException('You do not have all required technologies!');
            }
        }

        if ($research->getCost() > $player->getResources()->getCash()) {
            throw new RunTimeException('You can not afford that!');
        }
    }
}
