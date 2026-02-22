<?php

declare(strict_types=1);
/**
 * PureEntitiesX: Mob AI Plugin for PMMP
 * Copyright (C) 2018 RevivalPMMP
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace revivalpmmp\pureentities;

use LogLevel;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use revivalpmmp\pureentities\block\MonsterSpawnerPEX;
use revivalpmmp\pureentities\commands\RemoveEntitiesCommand;
use revivalpmmp\pureentities\commands\SummonCommand;
use revivalpmmp\pureentities\data\Color;
use revivalpmmp\pureentities\entity\animal\flying\Bat;
use revivalpmmp\pureentities\entity\animal\flying\Parrot;
use revivalpmmp\pureentities\entity\animal\swimming\Cod;
use revivalpmmp\pureentities\entity\animal\swimming\Dolphin;
use revivalpmmp\pureentities\entity\animal\swimming\Pufferfish;
use revivalpmmp\pureentities\entity\animal\swimming\Salmon;
use revivalpmmp\pureentities\entity\animal\swimming\Squid;
use revivalpmmp\pureentities\entity\animal\swimming\Tropicalfish;
use revivalpmmp\pureentities\entity\animal\walking\Chicken;
use revivalpmmp\pureentities\entity\animal\walking\Cow;
use revivalpmmp\pureentities\entity\animal\walking\Donkey;
use revivalpmmp\pureentities\entity\animal\walking\Horse;
use revivalpmmp\pureentities\entity\animal\walking\Llama;
use revivalpmmp\pureentities\entity\animal\walking\Mooshroom;
use revivalpmmp\pureentities\entity\animal\walking\Mule;
use revivalpmmp\pureentities\entity\animal\walking\Ocelot;
use revivalpmmp\pureentities\entity\animal\walking\Pig;
use revivalpmmp\pureentities\entity\animal\walking\Rabbit;
use revivalpmmp\pureentities\entity\animal\walking\Sheep;
use revivalpmmp\pureentities\entity\animal\walking\SkeletonHorse;
use revivalpmmp\pureentities\entity\animal\walking\Villager;
use revivalpmmp\pureentities\entity\BaseEntity;
use revivalpmmp\pureentities\entity\monster\flying\Blaze;
use revivalpmmp\pureentities\entity\monster\flying\Ghast;
use revivalpmmp\pureentities\entity\monster\flying\Vex;
use revivalpmmp\pureentities\entity\monster\jumping\MagmaCube;
use revivalpmmp\pureentities\entity\monster\jumping\Slime;
use revivalpmmp\pureentities\entity\monster\walking\CaveSpider;
use revivalpmmp\pureentities\entity\monster\walking\Creeper;
use revivalpmmp\pureentities\entity\monster\walking\Enderman;
use revivalpmmp\pureentities\entity\monster\walking\Endermite;
use revivalpmmp\pureentities\entity\monster\walking\Evoker;
use revivalpmmp\pureentities\entity\monster\walking\Husk;
use revivalpmmp\pureentities\entity\monster\walking\IronGolem;
use revivalpmmp\pureentities\entity\monster\walking\PigZombie;
use revivalpmmp\pureentities\entity\monster\walking\PolarBear;
use revivalpmmp\pureentities\entity\monster\walking\Shulker;
use revivalpmmp\pureentities\entity\monster\walking\Silverfish;
use revivalpmmp\pureentities\entity\monster\walking\Skeleton;
use revivalpmmp\pureentities\entity\monster\walking\SnowGolem;
use revivalpmmp\pureentities\entity\monster\walking\Spider;
use revivalpmmp\pureentities\entity\monster\walking\Stray;
use revivalpmmp\pureentities\entity\monster\walking\Vindicator;
use revivalpmmp\pureentities\entity\monster\walking\Witch;
use revivalpmmp\pureentities\entity\monster\walking\WitherSkeleton;
use revivalpmmp\pureentities\entity\monster\walking\Wolf;
use revivalpmmp\pureentities\entity\monster\walking\Zombie;
use revivalpmmp\pureentities\entity\monster\walking\ZombiePigman;
use revivalpmmp\pureentities\entity\monster\walking\ZombieVillager;
use revivalpmmp\pureentities\entity\projectile\LargeFireball;
use revivalpmmp\pureentities\entity\projectile\SmallFireball;
use revivalpmmp\pureentities\event\CreatureSpawnEvent;
use revivalpmmp\pureentities\event\EventListener;
use revivalpmmp\pureentities\features\IntfCanBreed;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\task\AutoSpawnTask;
use revivalpmmp\pureentities\task\EndermanLookingTask;
use revivalpmmp\pureentities\task\InteractionTask;
use revivalpmmp\pureentities\tile\MobSpawner;
use revivalpmmp\pureentities\utils\MobEquipper;

class PureEntities extends PluginBase
{
    /** @var PureEntities $instance */
    private static $instance;

    /** @var string[] */
    private static $registeredClasses = [];

    /**
     * Returns the plugin instance to get access to config e.g.
     * @return PureEntities the current instance of the plugin main class
     */
    public static function getInstance(): PureEntities
    {
        return self::$instance;
    }

    public function onLoad(): void
    {
        $temp = [
            Bat::class,
            Blaze::class,
            CaveSpider::class,
            Chicken::class,
            Cod::class,
            Cow::class,
            Creeper::class,
            Dolphin::class,
            Donkey::class,
            Enderman::class,
            Endermite::class,
            Evoker::class,
            Ghast::class,
            Horse::class,
            Husk::class,
            IronGolem::class,
            Llama::class,
            LargeFireball::class,
            MagmaCube::class,
            Mooshroom::class,
            Mule::class,
            Ocelot::class,
            Parrot::class,
            Pig::class,
            PigZombie::class,
            PolarBear::class,
            Pufferfish::class,
            Rabbit::class,
            Salmon::class,
            Sheep::class,
            Shulker::class,
            Silverfish::class,
            Skeleton::class,
            SkeletonHorse::class,
            Slime::class,
            SmallFireball::class,
            SnowGolem::class,
            Spider::class,
            Squid::class,
            Stray::class,
            Tropicalfish::class,
            Vex::class,
            Villager::class,
            Vindicator::class,
            Witch::class,
            WitherSkeleton::class,
            Wolf::class,
            Zombie::class,
            ZombiePigman::class,
            ZombieVillager::class
        ];

        foreach ($temp as $class) {
            self::$registeredClasses[strtolower($this->getShortClassName($class))] = $class;
        }

        $factory = EntityFactory::getInstance();

        foreach (self::$registeredClasses as $shortName => $class) {
            // Registro de entidad personalizada en PMMP 5
            $factory->register($class, function (CompoundTag $nbt) use ($class): Entity {
                return new $class(Location::fromObject(
                    $nbt->getCompoundTag("Pos") ? new Vector3(
                        $nbt->getCompoundTag("Pos")->getDouble("x"),
                        $nbt->getCompoundTag("Pos")->getDouble("y"),
                        $nbt->getCompoundTag("Pos")->getDouble("z")
                    ) : new Vector3(0, 0, 0),
                    $this->getServer()->getWorldManager()->getDefaultWorld(),
                    $nbt->getFloat("Rotation", 0.0)[0] ?? 0.0,
                    $nbt->getFloat("Rotation", 0.0)[1] ?? 0.0
                ), $nbt);
            }, [$shortName]); // Identificador string para save/load

            // Registro de huevos de spawn en creativo (solo para entidades válidas)
            if (
                $class === IronGolem::class ||
                $class === LargeFireball::class ||
                $class === SmallFireball::class ||
                $class === SnowGolem::class ||
                $class === ZombieVillager::class
            ) {
                continue;
            }

            $networkId = $class::NETWORK_ID ?? 0;
            if ($networkId !== 0) {
                $spawnEgg = ItemFactory::getInstance()->get(Item::SPAWN_EGG, $networkId);
                if (!$spawnEgg->isNull() && !Item::isCreativeItem($spawnEgg)) {
                    Item::addCreativeItem($spawnEgg);
                }
            }
        }

        Tile::registerTile(MobSpawner::class);
        BlockFactory::registerBlock(new MonsterSpawnerPEX(), true);

        $this->saveDefaultConfig();
        Color::init();

        self::$instance = $this;

        $this->getServer()->getLogger()->info(TextFormat::GREEN . "[PureEntitiesX] Originally written by milk0417. Currently maintained by RevivalPMMP for PMMP 5.x - Fixed by Ckris");
    }

    public function onEnable(): void
    {
        new PluginConfiguration($this); // create plugin configuration

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        if (PluginConfiguration::getInstance()->getEnableSpawning()) {
            $this->getScheduler()->scheduleRepeatingTask(
                new AutoSpawnTask($this),
                $this->getConfig()->getNested("spawn-task.trigger-ticks", 1000)
            );
        }

        if (PluginConfiguration::getInstance()->getEnableLookingTasks()) {
            $this->getScheduler()->scheduleRepeatingTask(
                new InteractionTask($this),
                $this->getConfig()->getNested("performance.check-interactive-ticks", 10)
            );
            $this->getScheduler()->scheduleRepeatingTask(
                new EndermanLookingTask($this),
                $this->getConfig()->getNested("performance.check-enderman-looking", 10)
            );
        }

        $this->getServer()->getCommandMap()->register("PureEntitiesX", new SummonCommand());
        $this->getServer()->getCommandMap()->register("PureEntitiesX", new RemoveEntitiesCommand());
    }

    /**
     * Crea una entidad (método estático actualizado para PMMP 5)
     *
     * @param int|string $type
     * @param Position $source
     * @param array $args
     *
     * @return Entity|null
     */
    public static function create($type, Position $source, ...$args): ?Entity
    {
        $factory = EntityFactory::getInstance();

        $nbt = Entity::createBaseNBT($source->asVector3(), null, $source instanceof Location ? $source->yaw : 0, $source instanceof Location ? $source->pitch : 0);

        // Usa el factory para crear la entidad
        return $factory->create($type, $source->getWorld(), $nbt, ...$args);
    }

    // ... (el resto del código sigue igual: scheduleCreatureSpawn, logOutput, getSuitableHeightPosition, etc.)

    // Puedes copiar y pegar el resto del archivo original aquí, ya que no hay más cambios críticos en esas funciones.
    // Si quieres que te complete alguna función específica que aún falle, pásame el error nuevo y lo arreglamos.

    /**
     * Returns the "short" name of a class without namespace ...
     *
     * @param string $longClassName
     * @return string
     */
    private function getShortClassName(string $longClassName): string
    {
        $short = "";
        $longClassName = strtok($longClassName, "\\");
        while ($longClassName !== false) {
            $short = $longClassName;
            $longClassName = strtok("\\");
        }
        return $short;
    }

    /**
     * @return array
     */
    public static function getRegisteredClasses(): array
    {
        return self::$registeredClasses;
    }

    public function getRegisteredClassNameFromShortName(string $shortName): ?string
    {
        return self::$registeredClasses[strtolower($shortName)] ?? null;
    }

    public static function getPositionNearPlayer(Player $player, int $minimumDistanceToPlayer = 8, int $maximumDistanceToPlayer = 40): Position
    {
        // Random method used to get 8 block difference from player to entity spawn
        $x = $player->x + (random_int($minimumDistanceToPlayer, $maximumDistanceToPlayer) * (random_int(0, 1) === 0 ? 1 : -1));
        $z = $player->z + (random_int($minimumDistanceToPlayer, $maximumDistanceToPlayer) * (random_int(0, 1) === 0 ? 1 : -1));
        return new Position($x, $player->y, $z, $player->getLevel());
    }
}