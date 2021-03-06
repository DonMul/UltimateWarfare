<?php

namespace FrankProjects\UltimateWarfare\Command\Maintenance;

use FrankProjects\UltimateWarfare\Entity\Player;
use FrankProjects\UltimateWarfare\Repository\PlayerRepository;
use FrankProjects\UltimateWarfare\Repository\WorldRepository;
use FrankProjects\UltimateWarfare\Util\IncomeCalculator;
use FrankProjects\UltimateWarfare\Util\UpkeepCalculator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePlayerIncomeCommand extends Command
{
    protected static $defaultName = 'game:maintenance:update:income';

    /**
     * @var PlayerRepository
     */
    private $playerRepository;

    /**
     * @var WorldRepository
     */
    private $worldRepository;

    /**
     * @var IncomeCalculator
     */
    private $incomeCalculator;

    /**
     * @var UpkeepCalculator
     */
    private $upkeepCalculator;

    /**
     * UpdatePlayerIncomeCommand constructor.
     *
     * @param PlayerRepository $playerRepository
     * @param WorldRepository $worldRepository
     * @param IncomeCalculator $incomeCalculator
     * @param UpkeepCalculator $upkeepCalculator
     */
    public function __construct(
        PlayerRepository $playerRepository,
        WorldRepository $worldRepository,
        IncomeCalculator $incomeCalculator,
        UpkeepCalculator $upkeepCalculator
    ) {
        $this->playerRepository = $playerRepository;
        $this->worldRepository = $worldRepository;
        $this->incomeCalculator = $incomeCalculator;
        $this->upkeepCalculator = $upkeepCalculator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Update resource income of all players')
            ->setHelp('Fix inconsistencies by updating resource income of all players...')
            ->addOption(
                'commit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Should I save the changes?',
                false
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln([
            'Updating income resources of all players',
            '============',
            '',
        ]);

        $commit = $input->getOption('commit');
        $commit = ($commit !== false);

        foreach ($this->worldRepository->findAll() as $world) {
            $output->writeln("Processing World: {$world->getName()}");

            foreach ($world->getPlayers() as $player) {
                $this->processPlayer($output, $player, $commit);
            }
        }

        if (!$commit) {
            $output->writeln('Use --commit to actually save the changes!');
        }

        $output->writeln('Done!');
    }

    /**
     * @param OutputInterface $output
     * @param Player $player
     * @param bool $commit
     */
    private function processPlayer(OutputInterface $output, Player $player, bool $commit): void
    {
        $output->writeln("Processing Player: {$player->getName()}");

        $income = $this->incomeCalculator->calculateIncomeForPlayer($player);
        $upkeep = $this->upkeepCalculator->calculateUpkeepForPlayer($player);

        if (!$player->getIncome()->equals($income) || !$player->getUpkeep()->equals($upkeep)) {
            $output->writeln("Mismatch found: {$player->getName()}");
            $output->writeln(print_r($income));
            $output->writeln(print_r($player->getIncome()));
            $output->writeln(print_r($upkeep));
            $output->writeln(print_r($player->getUpkeep()));

            $player->setIncome($income);
            $player->setUpkeep($upkeep);

            if ($commit) {
                $this->playerRepository->save($player);
            }
        }
    }
}
