#!/bin/bash

# Fix specific PM5 API changes that the generic script didn't handle

echo "Applying PM5-specific fixes..."

# Fix Item::get() calls - need to use ItemFactory and ItemIds
find src/ -name "*.php" -exec sed -i 's/Item::get(Item::\([A-Z_]*\)/ItemFactory::getInstance()->get(ItemIds::\1/g' {} \;

# Fix specific Item constant usages in comparisons
find src/ -name "*.php" -exec sed -i 's/->getId() === Item::\([A-Z_]*\)/->getTypeId() === ItemIds::\1/g' {} \;
find src/ -name "*.php" -exec sed -i 's/->getId() !== Item::\([A-Z_]*\)/->getTypeId() !== ItemIds::\1/g' {} \;
find src/ -name "*.php" -exec sed -i 's/\$itemInHand === Item::\([A-Z_]*\)/\$itemInHand->getTypeId() === ItemIds::\1/g' {} \;

# Fix BlockIds usage
find src/ -name "*.php" -exec sed -i 's/BlockIds::/BlockTypeIds::/g' {} \;

# Fix $this->level references that weren't caught
find src/ -name "*.php" -exec sed -i 's/\$this->level\b/\$this->getWorld()/g' {} \;

# Fix Entity::createEntity calls
find src/ -name "*.php" -exec sed -i 's/Entity::createEntity(/EntityFactory::getInstance()->create(/g' {} \;

# Fix Player gamemode constants  
find src/ -name "*.php" -exec sed -i 's/Player::SURVIVAL/GameMode::SURVIVAL/g' {} \;
find src/ -name "*.php" -exec sed -i 's/Player::ADVENTURE/GameMode::ADVENTURE/g' {} \;
find src/ -name "*.php" -exec sed -i 's/Player::CREATIVE/GameMode::CREATIVE/g' {} \;
find src/ -name "*.php" -exec sed -i 's/Player::SPECTATOR/GameMode::SPECTATOR/g' {} \;

# Fix isSurvival() calls - PM5 changed this
find src/ -name "*.php" -exec sed -i 's/->isSurvival()/->getGamemode() === GameMode::SURVIVAL || \$1->getGamemode() === GameMode::ADVENTURE/g' {} \;

# Fix Tile registration
find src/ -name "*.php" -exec sed -i 's/Tile::registerTile/TileFactory::getInstance()->register/g' {} \;

# Fix BlockFactory usage
find src/ -name "*.php" -exec sed -i 's/BlockFactory::registerBlock/BlockFactory::getInstance()->register/g' {} \;

# Fix Explosion class location
find src/ -name "*.php" -exec sed -i 's/use pocketmine\\world\\Explosion/use pocketmine\\world\\Explosion/g' {} \;

# Fix Location imports
find src/ -name "*.php" -exec sed -i 's/use pocketmine\\world\\Location/use pocketmine\\entity\\Location/g' {} \;

# Fix Position/Biome/Chunk imports
find src/ -name "*.php" -exec sed -i 's/use pocketmine\\world\\biome/use pocketmine\\world\\biome/g' {} \;
find src/ -name "*.php" -exec sed -i 's/use pocketmine\\world\\format\\Chunk/use pocketmine\\world\\format\\Chunk/g' {} \;

echo "PM5-specific fixes complete!"
