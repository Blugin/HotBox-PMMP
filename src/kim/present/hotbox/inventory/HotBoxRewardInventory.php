<?php

/*
 *
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License. see <https://opensource.org/licenses/MIT>.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://opensource.org/licenses/MIT MIT License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace kim\present\hotbox\inventory;

use kim\present\hotbox\HotBox;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

class HotBoxRewardInventory extends HotBoxInventory{
	/** @var Player */
	private $player;

	/** @var bool */
	private $opened;

	/**
	 * HotBoxRewardInventory constructor.
	 *
	 * @param Player $player
	 */
	public function __construct(Player $player){
		$this->player = $player;

		$plugin = HotBox::getInstance();
		$namedTag = $player->namedtag->getCompoundTag("HotBox");
		$this->opened = $namedTag instanceof CompoundTag && $namedTag->getInt(HotBox::START_TIME_TAG, 0) === $plugin->getStartTime();
		if($this->opened){
			$items = [];
			/** @var CompoundTag $itemTag */
			foreach($namedTag->getListTag(HotBox::INVENTORY_TAG) as $i => $itemTag){
				$items[] = Item::nbtDeserialize($itemTag);
			}
		}else{
			$items = $plugin->getInventory()->getContents();
		}
		parent::__construct($items);
		$this->nbt->setString("CustomName", $plugin->getLanguage()->translate("hotbox.chest.name.open"));
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
		$namedTag->setInt(HotBox::START_TIME_TAG, HotBox::getInstance()->getStartTime());
		$namedTag->setTag($this->nbtSerialize(HotBox::INVENTORY_TAG));
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "HotBoxRewardInventory";
	}

	/**
	 * @return bool
	 */
	public function isOpened() : bool{
		return $this->opened;
	}
}