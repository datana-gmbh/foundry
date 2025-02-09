<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use <?= $entity->getName() ?>;
<?php if ($repository): ?>use <?= $repository->getName() ?>;
use Zenstruck\Foundry\RepositoryProxy;
<?php endif ?>
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<<?= $entity->getShortName() ?>>
 *
 * @method <?= $entity->getShortName() ?>|Proxy create(array|callable $attributes = [])
 * @method static <?= $entity->getShortName() ?>|Proxy createOne(array $attributes = [])
 * @method static <?= $entity->getShortName() ?>|Proxy find(object|array|mixed $criteria)
 * @method static <?= $entity->getShortName() ?>|Proxy findOrCreate(array $attributes)
 * @method static <?= $entity->getShortName() ?>|Proxy first(string $sortedField = 'id')
 * @method static <?= $entity->getShortName() ?>|Proxy last(string $sortedField = 'id')
 * @method static <?= $entity->getShortName() ?>|Proxy random(array $attributes = [])
 * @method static <?= $entity->getShortName() ?>|Proxy randomOrCreate(array $attributes = [])
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] all()
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] createSequence(array|callable $sequence)
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] findBy(array $attributes)
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] randomSet(int $number, array $attributes = [])
<?php if ($repository): ?> * @method static <?= $repository->getShortName() ?>|RepositoryProxy repository()
<?php endif ?>
<?php if ($phpstanEnabled): ?> *
 * @phpstan-method Proxy<<?= $entity->getShortName() ?>> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<<?= $entity->getShortName() ?>> createOne(array $attributes = [])
 * @phpstan-method static Proxy<<?= $entity->getShortName() ?>> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<<?= $entity->getShortName() ?>> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<<?= $entity->getShortName() ?>> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<<?= $entity->getShortName() ?>> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<<?= $entity->getShortName() ?>> random(array $attributes = [])
 * @phpstan-method static Proxy<<?= $entity->getShortName() ?>> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Proxy<<?= $entity->getShortName() ?>>> all()
 * @phpstan-method static list<Proxy<<?= $entity->getShortName() ?>>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<<?= $entity->getShortName() ?>>> createSequence(array|callable $sequence)
 * @phpstan-method static list<Proxy<<?= $entity->getShortName() ?>>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<<?= $entity->getShortName() ?>>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<<?= $entity->getShortName() ?>>> randomSet(int $number, array $attributes = [])
<?php if ($repository): ?> * @phpstan-method static RepositoryProxy<<?= $repository->getShortName() ?>> repository()
<?php endif ?>
<?php endif ?>
 */
final class <?= $class_name ?> extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
    {
        return [
<?php
foreach ($defaultProperties as $fieldname => $type) {
        echo "            '".$fieldname."' => ".$type."\n";
}
?>
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(<?= $entity->getShortName() ?> $<?= lcfirst($entity->getShortName()) ?>): void {})
        ;
    }

    protected static function getClass(): string
    {
        return <?= $entity->getShortName() ?>::class;
    }
}
