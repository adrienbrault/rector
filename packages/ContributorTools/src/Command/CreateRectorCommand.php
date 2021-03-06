<?php declare(strict_types=1);

namespace Rector\ContributorTools\Command;

use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name\FullyQualified;
use Rector\Console\ConsoleStyle;
use Rector\ContributorTools\Configuration\Configuration;
use Rector\ContributorTools\Configuration\ConfigurationFactory;
use Rector\Printer\BetterStandardPrinter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symplify\PackageBuilder\Console\Command\CommandNaming;
use Symplify\PackageBuilder\Console\ShellCode;
use Symplify\PackageBuilder\FileSystem\FinderSanitizer;
use function Safe\getcwd;
use function Safe\sprintf;

final class CreateRectorCommand extends Command
{
    /**
     * @var string
     */
    private const TEMPLATES_DIRECTORY = __DIR__ . '/../../templates';

    /**
     * @var ConsoleStyle
     */
    private $consoleStyle;

    /**
     * @var ConfigurationFactory
     */
    private $configurationFactory;

    /**
     * @var BetterStandardPrinter
     */
    private $betterStandardPrinter;

    /**
     * @var FinderSanitizer
     */
    private $finderSanitizer;

    public function __construct(
        ConsoleStyle $consoleStyle,
        ConfigurationFactory $configurationFactory,
        BetterStandardPrinter $betterStandardPrinter,
        FinderSanitizer $finderSanitizer
    ) {
        parent::__construct();
        $this->consoleStyle = $consoleStyle;
        $this->configurationFactory = $configurationFactory;
        $this->betterStandardPrinter = $betterStandardPrinter;
        $this->finderSanitizer = $finderSanitizer;
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->setDescription('Create a new Rector, in proper location, with new tests');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configuration = $this->configurationFactory->createFromConfigFile(getcwd() . '/create-rector.yml');
        $data = $this->prepareData($configuration);

        $finder = Finder::create()->files()
            ->in(self::TEMPLATES_DIRECTORY);
        $smartFileInfos = $this->finderSanitizer->sanitize($finder);

        $testCasePath = null;
        foreach ($smartFileInfos as $smartFileInfo) {
            $destination = $smartFileInfo->getRelativeFilePathFromDirectory(self::TEMPLATES_DIRECTORY);
            $destination = $this->applyData($destination, $data);

            $content = FileSystem::read($smartFileInfo->getRealPath());
            $content = $this->applyData($content, $data);

            if (Strings::endsWith($destination, 'Test.php')) {
                $testCasePath = dirname($destination);
            }

            FileSystem::write($destination, $content);

            $this->consoleStyle->note(sprintf('New file "%s" was generated', $destination));
        }

        $this->consoleStyle->success(sprintf('New Rector "%s" is ready!', $configuration->getName()));

        if ($testCasePath) {
            $this->consoleStyle->note(
                sprintf('Now make these tests green:%svendor/bin/phpunit %s', PHP_EOL, $testCasePath)
            );
        }

        return ShellCode::SUCCESS;
    }

    /**
     * @return mixed[]
     */
    private function prepareData(Configuration $configuration): array
    {
        $data = [
            '_Package_' => $configuration->getPackage(),
            '_Category_' => $configuration->getCategory(),
            '_Description_' => $configuration->getDescription(),
            '_Name_' => $configuration->getName(),
            '_CodeBefore_' => $configuration->getCodeBefore(),
            '_CodeAfter_' => $configuration->getCodeAfter(),
        ];

        $nodeTypesPhp = [];
        foreach ($configuration->getNodeTypes() as $nodeType) {
            $nodeTypesPhp[] = new ClassConstFetch(new FullyQualified($nodeType), 'class');
        }
        $data['_NodeTypes_Php_'] = $this->betterStandardPrinter->prettyPrint($nodeTypesPhp);

        $data['_NodeTypes_Doc_'] = '\\' . implode('|\\', $configuration->getNodeTypes());

        return $data;
    }

    /**
     * @param mixed[] $data
     */
    private function applyData(string $content, array $data): string
    {
        return str_replace(array_keys($data), array_values($data), $content);
    }
}
