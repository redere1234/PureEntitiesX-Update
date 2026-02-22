<?php

declare(strict_types=1);

namespace revivalpmmp\pureentities\entity;

use pocketmine\block\Block;
use pocketmine\block\Water;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Level;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use revivalpmmp\pureentities\components\IdlingComponent;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\entity\monster\flying\Blaze;
use revivalpmmp\pureentities\entity\monster\Monster;
use revivalpmmp\pureentities\entity\monster\walking\Wolf;
use revivalpmmp\pureentities\features\IntfCanPanic;
use revivalpmmp\pureentities\features\IntfTameable;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;

abstract class BaseEntity extends Living implements IntfCanBreed, IntfTameable
{
    public $stayTime = 0;
    protected $moveTime = 0;

    /** @var Vector3|Entity|null */
    private $baseTarget = null;

    private $movement = true;
    protected $friendly = false;
    private $wallcheck = true;
    protected $fireProof = false;
    public $jumper = false;

    /**
     * Default is 1.2 blocks because entities need to be able to jump
     * just higher than the block to land on top of it.
     *
     * For Horses (and its variants) this should be 2.2
     *
     * @var float $maxJumpHeight
     */
    protected $maxJumpHeight = 1.2;

    protected $checkTargetSkipTicks = 1; // default: no skip
    public $width = 1.0;
    public $height = 1.0;
    public $speed = 1.0;

    /**
     * @var int
     */
    private $checkTargetSkipCounter = 0;

    protected $damagedByPlayer = false;

    /**
     * @var IdlingComponent
     */
    protected $idlingComponent;

    protected $maxAge = 0;
    protected $xpDropAmount = 0;

    public function __destruct()
    {
        parent::__destruct();
    }

    public function __construct(Level $level, CompoundTag $nbt)
    {
        $this->width = Data::WIDTHS[static::NETWORK_ID] ?? 1.0;
        $this->height = Data::HEIGHTS[static::NETWORK_ID] ?? 1.8;
        $this->idlingComponent = new IdlingComponent($this);
        $this->checkTargetSkipTicks = PluginConfiguration::getInstance()->getCheckTargetSkipTicks();
        $this->maxAge = PluginConfiguration::getInstance()->getMaxAge();

        parent::__construct($level, $nbt);

        if (!$this->isFlaggedForDespawn()) {
            $this->namedtag->setByte("generatedByPEX", 1, true);
        }
    }

    public abstract function updateMove($tickDiff);

    public function updateXpDropAmount(): void
    {
        $this->xpDropAmount = 0;
    }

    /**
     * Should return the experience dropped by the entity when killed
     * @return int
     */
    public function getXpDropAmount(): int
    {
        if (!$this->damagedByPlayer) {
            return 0;
        }
        $this->updateXpDropAmount();
        return $this->xpDropAmount;
    }

    public function getSaveId(): string
    {
        $class = new \ReflectionClass(get_class($this));
        return $class->getShortName();
    }

    public function isMovement(): bool
    {
        return $this->movement;
    }

    public function isFriendly(): bool
    {
        return $this->friendly;
    }

    public function isKnockback(): bool
    {
        return $this->attackTime > 0;
    }

    public function isWallCheck(): bool
    {
        return $this->wallcheck;
    }

    public function setMovement(bool $value): void
    {
        $this->movement = $value;
    }

    public function setFriendly(bool $bool): void
    {
        $this->friendly = $bool;
    }

    public function setWallCheck(bool $value): void
    {
        $this->wallcheck = $value;
    }

    /**
     * Sets the base target for the entity. If this method is called
     * and the baseTarget is the same, nothing is set
     *
     * @param Entity|Vector3|null $baseTarget
     */
    public function setBaseTarget($baseTarget): void
    {
        if ($baseTarget instanceof Player && !in_array($baseTarget->getGamemode(), [Player::SURVIVAL, Player::ADVENTURE])) {
            return;
        }
        if ($baseTarget !== $this->baseTarget) {
            PureEntities::logOutput("$this: setBaseTarget to $baseTarget");
            $this->baseTarget = $baseTarget;
        }
    }

    /**
     * Returns the base target currently set for this entity
     *
     * @return Entity|Vector3|null
     */
    public function getBaseTarget()
    {
        return $this->baseTarget;
    }

    public function getSpeed(): float
    {
        return $this->speed;
    }

    /**
     * @return float
     */
    public function getMaxJumpHeight(): float
    {
        return $this->maxJumpHeight;
    }

    public function initEntity(): void
    {
        parent::initEntity();
        $this->loadNBT();
        $this->setDataFlag(self::DATA_FLAG_NO_AI, self::DATA_TYPE_BYTE, true);
        $this->idlingComponent->loadFromNBT();
    }

    public function saveNBT(): void
    {
        if (PluginConfiguration::getInstance()->getEnableNBT()) {
            parent::saveNBT();
            $this->namedtag->setByte(NBTConst::NBT_KEY_MOVEMENT, (int)$this->isMovement(), true);
            $this->namedtag->setByte(NBTConst::NBT_KEY_WALL_CHECK, (int)$this->isWallCheck(), true);
            $this->namedtag->setInt(NBTConst::NBT_KEY_AGE_IN_TICKS, $this->ticksLived, true);
            $this->idlingComponent->saveNBT();
        }
    }

    public function loadNBT(): void
    {
        if (PluginConfiguration::getInstance()->getEnableNBT()) {
            if ($this->namedtag->hasTag(NBTConst::NBT_KEY_MOVEMENT)) {
                $movement = $this->namedtag->getByte(NBTConst::NBT_KEY_MOVEMENT, 0, true);
                $this->setMovement((bool)$movement);
            }
            if ($this->namedtag->hasTag(NBTConst::NBT_KEY_WALL_CHECK)) {
                $wallCheck = $this->namedtag->getByte(NBTConst::NBT_KEY_WALL_CHECK, 0, true);
                $this->setWallCheck((bool)$wallCheck);
            }
            if ($this->namedtag->hasTag(NBTConst::NBT_KEY_AGE_IN_TICKS)) {
                $age = $this->namedtag->getInt(NBTConst::NBT_KEY_AGE_IN_TICKS, 0, true);
                $this->ticksLived = $age;
            }
        }
    }

    public function updateMovement(bool $teleport = false): void
    {
        if (!$this->isClosed() && $this->getWorld() !== null) {
            parent::updateMovement($teleport);
        }
    }

    public function isInsideOfSolid(): bool
    {
        if ($this->isClosed() || $this->getWorld() === null) {
            return false;
        }
        $block = $this->getWorld()->getBlockAt((int)floor($this->x), (int)floor($this->y + $this->height - 0.18), (int)floor($this->z));
        $bb = $block->getBoundingBox();
        return $bb !== null && $block->isSolid() && !$block->isTransparent() && $bb->intersectsWith($this->getBoundingBox());
    }

    /**
     * Entity gets attacked by another entity / explosion or something similar
     *
     * @param EntityDamageEvent $source the damage event
     */
    public function attack(EntityDamageEvent $source): void
    {
        if ($this->isClosed() || $source->isCancelled()) {
            return;
        }
        if ($this->isKnockback()) {
            return;
        }

        // "wake up" entity - it gets attacked!
        $this->idlingComponent->stopIdling(1, true);

        parent::attack($source);

        $this->stayTime = 0;
        $this->moveTime = 0;

        if ($source instanceof EntityDamageByEntityEvent) {
            if ($source instanceof EntityDamageByChildEntityEvent && $source->getChild()->getOwningEntity() instanceof Player) {
                $this->damagedByPlayer = true;
            }
            $sourceOfDamage = $source->getDamager();
            if ($sourceOfDamage instanceof Player) {
                $this->damagedByPlayer = true;
            }

            $motion = (new Vector3($this->x - $sourceOfDamage->x, $this->y - $sourceOfDamage->y, $this->z - $sourceOfDamage->z))->normalize();
            $this->motion->x = $motion->x * 0.19;
            $this->motion->z = $motion->z * 0.19;

            if ($this instanceof FlyingEntity && !($this instanceof Blaze)) {
                $this->motion->y = $motion->y * 0.19;
            } else {
                $this->motion->y = 0.6;
            }

            // panic mode
            if ($this instanceof IntfCanPanic && $sourceOfDamage instanceof Player && !$this->isInPanic() && $this->panicEnabled()) {
                $this->setBaseTarget(new Vector3($this->x - ($sourceOfDamage->x * 10), $this->y - $sourceOfDamage->y, $this->z - ($sourceOfDamage->z * 10)));
                $this->setInPanic();
            }
        }

        $this->checkAttackByTamedEntities($source);
    }

    public function knockBack(Entity $attacker, float $damage, float $x, float $z, float $base = 0.4): void
    {
        parent::knockBack($attacker, $damage, $x, $z, $base);
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->isClosed() || $this->getWorld() === null) {
            return false;
        }

        $hasUpdate = parent::entityBaseTick($tickDiff);

        if ($this->checkDespawn()) {
            return false;
        }

        if ($this->moveTime > 0) {
            $this->moveTime -= $tickDiff;
        }

        if ($this->isOnFire() && $this->getWorld()->getBlock($this->getPosition()) instanceof Water) {
            $this->extinguish();
        }

        // check panic tick
        if ($this instanceof IntfCanPanic) {
            $this->panicTick($tickDiff);
        }

        return $hasUpdate;
    }

    /**
     * This method checks if an entity should despawn - if so, the entity is closed
     * @return bool
     */
    private function checkDespawn(): bool
    {
        if ($this->ticksLived > $this->maxAge &&
            (!$this instanceof IntfTameable || ($this instanceof IntfTameable && !$this->isTamed()))
        ) {
            PureEntities::logOutput("Despawn entity " . $this->getName());
            $this->flagForDespawn();
            return true;
        }
        return false;
    }

    public function move(float $dx, float $dy, float $dz): void
    {
        $movX = $dx;
        $movY = $dy;
        $movZ = $dz;

        $list = $this->getWorld()->getCollisionBlocks($this->getBoundingBox()->expandedCopy($dx, $dy, $dz));

        if ($this->isWallCheck()) {
            foreach ($list as $bb) {
                $dx = $bb->calculateXOffset($this->getBoundingBox(), $dx);
            }
            $this->getBoundingBox()->offset($dx, 0, 0);

            foreach ($list as $bb) {
                $dz = $bb->calculateZOffset($this->getBoundingBox(), $dz);
            }
            $this->getBoundingBox()->offset(0, 0, $dz);
        }

        foreach ($list as $bb) {
            $dy = $bb->calculateYOffset($this->getBoundingBox(), $dy);
        }
        $this->getBoundingBox()->offset(0, $dy, 0);

        $this->setPosition($this->x + $dx, $this->y + $dy, $this->z + $dz);

        $this->checkChunks();
        $this->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
        $this->updateFallState($dy, $this->onGround);
    }

    public function targetOption(Living $creature, float $distance): bool
    {
        return $this instanceof Monster &&
               (!$creature instanceof Player || ($creature->isSurvival() && $creature->isOnline())) &&
               $creature->isAlive() && !$creature->isClosed() && $distance <= 81;
    }

    /**
     * This is called while moving around. This is specially important for entities like sheep etc. pp
     * which eat grass to grow their wool. This method should check at which block the entity is currently
     * staying / moving. If it is suitable - it should eat grass or something similar
     *
     * @return bool|Block
     */
    public function isCurrentBlockOfInterest()
    {
        return false;
    }

    // ... (el resto del código original sigue igual: checkAttackByTamedEntities, checkTamedMobsAttack, getTamedMobs, isCheckTargetAllowedBySkip, isLootDropAllowed, isFollowingPlayer)

    // Puedes copiar y pegar el resto de métodos originales aquí sin cambios, ya que no usan Creature directamente.
}