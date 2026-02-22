#!/bin/bash

# Add missing PM5 imports where needed

echo "Adding missing PM5 imports..."

# Add GameMode import where needed
for file in $(grep -l "GameMode::" src/revivalpmmp/pureentities/entity/*.php 2>/dev/null); do
    if ! grep -q "use pocketmine\\\\player\\\\GameMode" "$file"; then
        sed -i '/^use pocketmine\\player\\Player/a use pocketmine\\player\\GameMode;' "$file"
    fi
done

# Add ItemIds import where Item constants are used
for file in $(grep -l "ItemIds::" src/revivalpmmp/pureentities/**/*.php 2>/dev/null); do
    if ! grep -q "use pocketmine\\\\item\\\\ItemIds" "$file"; then
        sed -i '/^use pocketmine\\item\\Item/a use pocketmine\\item\\ItemIds;' "$file"
    fi
done

# Add BlockTypeIds import where used
for file in $(grep -l "BlockTypeIds::" src/revivalpmmp/pureentities/**/*.php 2>/dev/null); do
    if ! grep -q "use pocketmine\\\\block\\\\BlockTypeIds" "$file"; then
        sed -i '/^namespace/a use pocketmine\\block\\BlockTypeIds;' "$file"
    fi
done

# Add TileFactory import where needed
for file in $(grep -l "TileFactory::" src/revivalpmmp/pureentities/**/*.php 2>/dev/null); do
    if ! grep -q "use pocketmine\\\\block\\\\tile\\\\TileFactory" "$file"; then
        sed -i '/^namespace/a use pocketmine\\block\\tile\\TileFactory;' "$file"
    fi
done

echo "Import additions complete!"
