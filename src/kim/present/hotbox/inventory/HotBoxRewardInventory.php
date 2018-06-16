<?php

declare(strict_types=1);

namespace kim\present\hotbox\inventory;

use kim\present\hotbox\HotBox;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

class HotBoxRewardInventory extends HotBoxInventory{
	/**
	 * @var Player
	 */
	private $player;

	/**
	 * HotBoxRewardInventory constructor.
	 *
	 * @param Player $player
	 */
	public function __construct(Player $player){
		$this->player = $player;

		$namedTag = $player->namedtag->getCompoundTag("HotBox");
		if($namedTag instanceof CompoundTag && $namedTag->getInt("LastTime") === HotBox::getInstance()->getLastTime()){
			$items = [];
			/** @var CompoundTag $itemTag */
			foreach($namedTag->getListTag("HotBoxInventory") as $i => $itemTag){
				$items[] = Item::nbtDeserialize($itemTag);
			}
		}else{
			$items = HotBox::getInstance()->getHotBoxInventory()->getContents();
		}
		parent::__construct($items);
	}

	/**
	 * @param Player $who
	 */
	public function onClose(Player $who) : void{
		parent::onClose($who);

		$namedTag = $this->player->namedtag->getCompoundTag("HotBox");
		if(!$namedTag instanceof CompoundTag){
			$namedTag = new CompoundTag("HotBox");
			$this->player->namedtag->setTag($namedTag);
		}
		$namedTag->setInt("LastTime", HotBox::getInstance()->getLastTime());
		$namedTag->setTag($this->nbtSerialize("HotBoxInventory"));
	}
}