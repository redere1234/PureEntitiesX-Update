#!/bin/bash

# PocketMine-MP 4 to 5 Migration Script

echo "Starting PM4 to PM5 migration..."

# Find all PHP files
find src/ -name "*.php" -type f | while read -r file; do
    echo "Processing: $file"
    
    # Replace level namespace with world namespace
    sed -i 's/use pocketmine\\level\\/use pocketmine\\world\\/g' "$file"
    sed -i 's/pocketmine\\level\\/pocketmine\\world\\/g' "$file"
    
    # Replace Level class with World class
    sed -i 's/\bLevel::/World::/g' "$file"
    sed -i 's/instanceof Level/instanceof World/g' "$file"
    sed -i 's/\bLevel /World /g' "$file"
    
    # Replace getLevel() with getWorld()
    sed -i 's/->getLevel()/->getWorld()/g' "$file"
    sed -i 's/\$this->level->/\$this->getWorld()->/g' "$file"
    sed -i 's/\$this->level /\$this->getWorld() /g' "$file"
    
    # Replace getLevels() with getWorldManager()->getWorlds()
    sed -i 's/->getLevels()/->getWorldManager()->getWorlds()/g' "$file"
    
    # Replace getLevelByName() with getWorldManager()->getWorldByName()
    sed -i 's/->getLevelByName(/->getWorldManager()->getWorldByName(/g' "$file"
    
    # Replace BlockFactory with VanillaBlocks
    sed -i 's/BlockFactory::getInstance()/VanillaBlocks::/g' "$file"
    
    # Replace BlockIds usage
    sed -i 's/BlockIds::AIR/BlockTypeIds::AIR/g' "$file"
    sed -i 's/use pocketmine\\block\\BlockIds/use pocketmine\\block\\BlockTypeIds/g' "$file"
    
    # Replace Item::get() with ItemFactory::getInstance()->get()
    # This is complex, so we'll do specific replacements
    sed -i 's/Item::get(/ItemFactory::getInstance()->get(/g' "$file"
    
    # Replace Player constants
    sed -i 's/Player::SURVIVAL/GameMode::SURVIVAL()/g' "$file"
    sed -i 's/Player::ADVENTURE/GameMode::ADVENTURE()/g' "$file"
    
    # Replace Creature with Living
    sed -i 's/instanceof Creature/instanceof Living/g' "$file"
    sed -i 's/\bCreature /Living /g' "$file"
    
    # Replace Entity::createEntity
    sed -i 's/Entity::createEntity(/EntityFactory::getInstance()->create(/g' "$file"
done

echo "Migration complete!"
