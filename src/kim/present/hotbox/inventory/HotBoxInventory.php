<?php

declare(strict_types=1);

namespace kim\present\hotbox\inventory;

use kim\present\hotbox\HotBox;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\CustomInventory;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\{
	CompoundTag, IntTag, ListTag, StringTag
};
use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\tile\Spawnable;

class HotBoxInventory extends CustomInventory{
	/**
	 * @var CompoundTag
	 */
	protected $nbt;

	/**
	 * @var Vector3[]
	 */
	protected $vectors = [];

	/**
	 * HotBoxInventory constructor.
	 *
	 * @param Item[] $items
	 */
	public function __construct($items = []){
		parent::__construct(new Vector3(0, 0, 0), $items, 27);
		$this->nbt = new CompoundTag('', [
			new StringTag('id', 'Chest'),
			new IntTag('x', 0),
			new IntTag('y', 0),
			new IntTag('z', 0),
			new StringTag('CustomName', HotBox::getInstance()->getLanguage()->translateString('hotbox.chest.name.edit'))
		]);
	}

	/**
	 * @param Player $who
	 */
	public function onOpen(Player $who) : void{
		BaseInventory::onOpen($who);

		$this->vectors[$key = $who->getLowerCaseName()] = $who->subtract(0, 3, 0)->floor();
		if($this->vectors[$key]->y < 0){
			$this->vectors[$key]->y = 0;
		}

		$pk = new UpdateBlockPacket();
		$pk->x = $this->vectors[$key]->x;
		$pk->y = $this->vectors[$key]->y;
		$pk->z = $this->vectors[$key]->z;
		$pk->blockRuntimeId = BlockFactory::toStaticRuntimeId(Block::CHEST);
		$pk->flags = UpdateBlockPacket::FLAG_NONE;
		$who->sendDataPacket($pk);


		$this->nbt->setInt('x', $this->vectors[$key]->x);
		$this->nbt->setInt('y', $this->vectors[$key]->y);
		$this->nbt->setInt('z', $this->vectors[$key]->z);

		$pk = new BlockEntityDataPacket();
		$pk->x = $this->vectors[$key]->x;
		$pk->y = $this->vectors[$key]->y;
		$pk->z = $this->vectors[$key]->z;
		$pk->namedtag = (new NetworkLittleEndianNBTStream())->write($this->nbt);
		$who->sendDataPacket($pk);


		$pk = new ContainerOpenPacket();
		$pk->type = WindowTypes::CONTAINER;
		$pk->entityUniqueId = -1;
		$pk->x = $this->vectors[$key]->x;
		$pk->y = $this->vectors[$key]->y;
		$pk->z = $this->vectors[$key]->z;
		$pk->windowId = $who->getWindowId($this);
		$who->sendDataPacket($pk);

		$this->sendContents($who);
	}

	/**
	 * @param Player $who
	 */
	public function onClose(Player $who) : void{
		BaseInventory::onClose($who);

		$block = $who->getLevel()->getBlock($this->vectors[$key = $who->getLowerCaseName()]);

		$pk = new UpdateBlockPacket();
		$pk->x = $this->vectors[$key]->x;
		$pk->y = $this->vectors[$key]->y;
		$pk->z = $this->vectors[$key]->z;
		$pk->blockRuntimeId = BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage());
		$pk->flags = UpdateBlockPacket::FLAG_NONE;
		$who->sendDataPacket($pk);

		$tile = $who->getLevel()->getTile($this->vectors[$key]);
		if($tile instanceof Spawnable){
			$who->sendDataPacket($tile->createSpawnPacket());
		}
		unset($this->vectors[$key]);
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "HotBoxInventory";
	}

	/**
	 * @return int
	 */
	public function getDefaultSize() : int{
		return 27;
	}

	/**
	 * @return int
	 */
	public function getNetworkType() : int{
		return WindowTypes::CONTAINER;
	}

	/**
	 * @param string $tagName
	 *
	 * @return ListTag
	 */
	public function nbtSerialize(string $tagName = 'Inventory') : ListTag{
		$tag = new ListTag($tagName, [], NBT::TAG_Compound);
		for($slot = 0; $slot < 27; ++$slot){
			$item = $this->getItem($slot);
			if(!$item->isNull()){
				$tag->push($item->nbtSerialize($slot));
			}
		}
		return $tag;
	}

	/**
	 * @param ListTag $tag
	 *
	 * @return HotBoxInventory
	 */
	public static function nbtDeserialize(ListTag $tag) : HotBoxInventory{
		$inventory = new HotBoxInventory();
		/** @var CompoundTag $itemTag */
		foreach($tag as $i => $itemTag){
			$inventory->setItem($itemTag->getByte("Slot"), Item::nbtDeserialize($itemTag));
		}
		return $inventory;
	}
}