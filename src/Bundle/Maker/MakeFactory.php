<?php

namespace Zenstruck\Foundry\Bundle\Maker;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ODMClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo as ORMClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Zenstruck\Foundry\ModelFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MakeFactory extends AbstractMaker
{
    private const DEFAULTS = [
        'ARRAY' => '[],',
        'ASCII_STRING' => 'self::faker()->text({length}),',
        'BIGINT' => 'self::faker()->randomNumber(),',
        'BLOB' => 'self::faker()->text(),',
        'BOOLEAN' => 'self::faker()->boolean(),',
        'DATE' => 'self::faker()->dateTime(),',
        'DATE_MUTABLE' => 'self::faker()->dateTime(),',
        'DATE_IMMUTABLE' => '\DateTimeImmutable::createFromMutable(self::faker()->dateTime()),',
        'DATETIME_MUTABLE' => 'self::faker()->dateTime(),',
        'DATETIME_IMMUTABLE' => '\DateTimeImmutable::createFromMutable(self::faker()->dateTime()),',
        'DATETIMETZ_MUTABLE' => 'self::faker()->dateTime(),',
        'DATETIMETZ_IMMUTABLE' => '\DateTimeImmutable::createFromMutable(self::faker()->dateTime()),',
        'DECIMAL' => 'self::faker()->randomFloat(),',
        'FLOAT' => 'self::faker()->randomFloat(),',
        'INTEGER' => 'self::faker()->randomNumber(),',
        'JSON' => '[],',
        'JSON_ARRAY' => '[],',
        'SIMPLE_ARRAY' => '[],',
        'SMALLINT' => 'self::faker()->numberBetween(1, 32767),',
        'STRING' => 'self::faker()->text({length}),',
        'TEXT' => 'self::faker()->text({length}),',
        'TIME_MUTABLE' => 'self::faker()->datetime(),',
        'TIME_IMMUTABLE' => '\DateTimeImmutable::createFromMutable(self::faker()->datetime()),',
    ];

    /** @var string[] */
    private array $entitiesWithFactories = [];

    public function __construct(private ManagerRegistry $managerRegistry, \Traversable $factories, private string $projectDir)
    {
        $this->entitiesWithFactories = \array_map(
            static fn(ModelFactory $factory): string => $factory::getEntityClass(),
            \iterator_to_array($factories)
        );
    }

    public static function getCommandName(): string
    {
        return 'make:factory';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a Foundry model factory for a Doctrine entity class';
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // noop
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription(self::getCommandDescription())
            ->addArgument('entity', InputArgument::OPTIONAL, 'Entity class to create a factory for')
            ->addOption('namespace', null, InputOption::VALUE_REQUIRED, 'Customize the namespace for generated factories', 'Factory')
            ->addOption('test', null, InputOption::VALUE_NONE, 'Create in <fg=yellow>tests/</> instead of <fg=yellow>src/</>')
            ->addOption('all-fields', null, InputOption::VALUE_NONE, 'Create defaults for all entity fields, not only required fields')
        ;

        $inputConfig->setArgumentAsNonInteractive('entity');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if ($input->getArgument('entity')) {
            return;
        }

        if (!$input->getOption('test')) {
            $io->text('// Note: pass <fg=yellow>--test</> if you want to generate factories in your <fg=yellow>tests/</> directory');
            $io->newLine();
        }

        if (!$input->getOption('all-fields')) {
            $io->text('// Note: pass <fg=yellow>--all-fields</> if you want to generate default values for all fields, not only required fields');
            $io->newLine();
        }

        $argument = $command->getDefinition()->getArgument('entity');
        $entity = $io->choice($argument->getDescription(), \array_merge($this->entityChoices(), ['All']));

        $input->setArgument('entity', $entity);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $entity = $input->getArgument('entity');
        $classes = 'All' === $entity ? $this->entityChoices() : [$entity];

        foreach ($classes as $class) {
            $this->generateFactory($class, $input, $io, $generator);
        }
    }

    /**
     * Generates a single entity factory.
     */
    private function generateFactory(string $class, InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        if (!\class_exists($class)) {
            $class = $generator->createClassNameDetails($class, 'Entity\\')->getFullName();
        }

        if (!\class_exists($class)) {
            throw new RuntimeCommandException(\sprintf('Entity "%s" not found.', $input->getArgument('entity')));
        }

        $namespace = $input->getOption('namespace');

        // strip maker's root namespace if set
        if (0 === \mb_strpos($namespace, $generator->getRootNamespace())) {
            $namespace = \mb_substr($namespace, \mb_strlen($generator->getRootNamespace()));
        }

        $namespace = \trim($namespace, '\\');

        // if creating in tests dir, ensure namespace prefixed with Tests\
        if ($input->getOption('test') && 0 !== \mb_strpos($namespace, 'Tests\\')) {
            $namespace = 'Tests\\'.$namespace;
        }

        $entity = new \ReflectionClass($class);
        $factory = $generator->createClassNameDetails($entity->getShortName(), $namespace, 'Factory');

        $repository = new \ReflectionClass($this->managerRegistry->getRepository($entity->getName()));

        if (0 !== \mb_strpos($repository->getName(), $generator->getRootNamespace())) {
            // not using a custom repository
            $repository = null;
        }

        $generator->generateClass(
            $factory->getFullName(),
            __DIR__.'/../Resources/skeleton/Factory.tpl.php',
            [
                'entity' => $entity,
                'defaultProperties' => $this->defaultPropertiesFor($entity->getName(), $input->getOption('all-fields')),
                'repository' => $repository,
                'phpstanEnabled' => $this->phpstanEnabled(),
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new factory and set default values/states.',
            'Find the documentation at https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories',
        ]);
    }

    /**
     * @return class-string[]
     */
    private function entityChoices(): array
    {
        $choices = [];

        foreach ($this->managerRegistry->getManagers() as $manager) {
            foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
                if ($metadata->getReflectionClass()->isAbstract()) {
                    continue;
                }

                if (!\in_array($metadata->getName(), $this->entitiesWithFactories, true)) {
                    $choices[] = $metadata->getName();
                }
            }
        }

        \sort($choices);

        if (empty($choices)) {
            throw new RuntimeCommandException('No entities or documents found, or none left to make factories for.');
        }

        return $choices;
    }

    /**
     * @param class-string $class
     */
    private function defaultPropertiesFor(string $class, bool $allFields): iterable
    {
        $em = $this->managerRegistry->getManagerForClass($class);

        if (!$em instanceof ObjectManager) {
            return [];
        }

        /** @var ORMClassMetadata|ODMClassMetadata $metadata */
        $metadata = $em->getClassMetadata($class);
        $ids = $metadata->getIdentifierFieldNames();

        $dbType = $em instanceof EntityManagerInterface ? 'ORM' : 'ODM';

        foreach ($metadata->fieldMappings as $property) {
            // ignore identifiers and nullable fields
            if ((!$allFields && ($property['nullable'] ?? false)) || \in_array($property['fieldName'], $ids, true)) {
                continue;
            }

            $type = \mb_strtoupper($property['type']);
            $value = "null, // TODO add {$type} {$dbType} type manually";
            $length = $property['length'] ?? '';

            if (\array_key_exists($type, self::DEFAULTS)) {
                $value = self::DEFAULTS[$type];
            }

            yield $property['fieldName'] => \str_replace('{length}', (string) $length, $value);
        }
    }

    private function phpstanEnabled(): bool
    {
        return \file_exists("{$this->projectDir}/vendor/phpstan/phpstan/phpstan");
    }
}
