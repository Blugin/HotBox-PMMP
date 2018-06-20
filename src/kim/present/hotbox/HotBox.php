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
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0.0
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace kim\present\hotbox;

use kim\present\hotbox\command\{
	DisableSubcommand, EditSubcommand, EnableSubcommand, OpenSubcommand, Subcommand
};
use kim\present\hotbox\inventory\HotBoxInventory;
use kim\present\hotbox\lang\PluginLang;
use kim\present\hotbox\listener\InventoryEventListener;
use pocketmine\command\{
	Command, CommandSender, PluginCommand
};
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class HotBox extends PluginBase{
	public const OPEN = 0;
	public const EDIT = 1;
	public const ON = 2;
	public const OFF = 3;

	public const LAST_TIME_TAG = "LastTime";
	public const INVENTORY_TAG = "HotBoxInventory";
	public const IS_HOT_TIME_TAG = "IsHotTime";

	/**
	 * @var HotBox
	 */
	private static $instance;

	/**
	 * @return HotBox
	 */
	public static function getInstance() : HotBox{
		return self::$instance;
	}

	/**
	 * @var PluginLang
	 */
	private $language;

	/**
	 * @var PluginCommand
	 */
	private $command;

	/**
	 * @var bool
	 */
	private $isHotTime;

	/**
	 * @var int
	 */
	private $lastTime;

	/**
	 * @var HotBoxInventory
	 */
	private $inventory;

	/**
	 * @var Subcommand[]
	 */
	private $subcommands;

	public function onLoad() : void{
		self::$instance = $this;
	}

	public function onEnable() : void{
		//Save default resources
		$this->saveResource("lang/eng/lang.ini", false);
		$this->saveResource("lang/kor/lang.ini", false);
		$this->saveResource("lang/language.list", false);

		//Load config file
		$this->saveDefaultConfig();
		$this->reloadConfig();

		//Load language file
		$config = $this->getConfig();
		$this->language = new PluginLang($this, $config->getNested("settings.language"));
		$this->getLogger()->info($this->language->translateString("language.selected", [$this->language->getName(), $this->language->getLang()]));

		//Load hot-time reward data
		if(file_exists($file = "{$this->getDataFolder()}HotBoxInventory.dat")){
			$namedTag = (new BigEndianNBTStream())->readCompressed(file_get_contents($file));
			if($namedTag instanceof CompoundTag){
				$this->lastTime = $namedTag->getInt(HotBox::LAST_TIME_TAG);
				$this->inventory = HotBoxInventory::nbtDeserialize($namedTag->getListTag(HotBox::INVENTORY_TAG));
				$this->isHotTime = (bool) $namedTag->getInt(HotBox::IS_HOT_TIME_TAG, 0);
			}else{
				$this->getLogger()->error("The file is not in the NBT-CompoundTag format : $file");
			}
		}else{
			$this->lastTime = -1;
			$this->inventory = new HotBoxInventory();
			$this->isHotTime = false;
		}

		//Register main command
		$this->command = new PluginCommand($config->getNested("command.name"), $this);
		$this->command->setPermission("hotbox.cmd");
		$this->command->setAliases($config->getNested("command.aliases"));
		$this->command->setUsage($this->language->translateString("commands.hotbox.usage"));
		$this->command->setDescription($this->language->translateString("commands.hotbox.description"));
		$this->getServer()->getCommandMap()->register($this->getName(), $this->command);

		//Register subcommands
		$this->subcommands = [
			self::OPEN => new OpenSubcommand($this),
			self::EDIT => new EditSubcommand($this),
			self::ON => new EnableSubcommand($this),
			self::OFF => new DisableSubcommand($this)
		];
		$this->getServer()->getPluginManager()->registerEvents(new InventoryEventListener(), $this);
	}

	public function onDisable() : void{
		//Save hot-time reward data
		$namedTag = new CompoundTag("HotBox", [
			new IntTag(HotBox::LAST_TIME_TAG, $this->lastTime),
			$this->inventory->nbtSerialize(HotBox::INVENTORY_TAG),
			new IntTag(HotBox::IS_HOT_TIME_TAG, (int) $this->isHotTime)
		]);
		file_put_contents("{$this->getDataFolder()}HotBoxInventory.dat", (new BigEndianNBTStream())->writeCompressed($namedTag));
	}

	/**
	 * @param CommandSender $sender
	 * @param Command       $command
	 * @param string        $label
	 * @param string[]      $args
	 *
	 * @return bool
	 */
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if($sender instanceof Player){
			if(empty($args[0])){
				$targetSubcommand = null;
				foreach($this->subcommands as $key => $subcommand){
					if($sender->hasPermission($subcommand->getPermission())){
						if($targetSubcommand === null){
							$targetSubcommand = $subcommand;
						}else{
							//Filter out cases where more than two command has permission
							return false;
						}
					}
				}
				$targetSubcommand->handle($sender);
			}
		}
		if(isset($args[0])){
			foreach($this->subcommands as $key => $subcommand){
				if($subcommand->checkLabel($args[0])){
					$subcommand->handle($sender);
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @return PluginLang
	 */
	public function getLanguage() : PluginLang{
		return $this->language;
	}

	/**
	 * @return bool
	 */
	public function isHotTime() : bool{
		return $this->isHotTime;
	}

	/**
	 * @param bool $enable = true
	 */
	public function setHotTime(bool $enable = true) : void{
		$this->isHotTime = $enable;
	}

	/**
	 * @return int
	 */
	public function getLastTime() : int{
		return $this->lastTime;
	}

	/**
	 * @param int $lastTime
	 */
	public function setLastTime(int $lastTime) : void{
		$this->lastTime = $lastTime;
	}

	/**
	 * @return HotBoxInventory
	 */
	public function getInventory() : HotBoxInventory{
		return $this->inventory;
	}

	/**
	 * @return Subcommand[]
	 */
	public function getSubcommands() : array{
		return $this->subcommands;
	}

	/**
	 * @Override for multilingual support of the config file
	 *
	 * @return bool
	 */
	public function saveDefaultConfig() : bool{
		$resource = $this->getResource("lang/{$this->getServer()->getLanguage()->getLang()}/config.yml");
		if($resource === null){
			$resource = $this->getResource("lang/" . PluginLang::FALLBACK_LANGUAGE . "/config.yml");
		}

		if(!file_exists($configFile = $this->getDataFolder() . "config.yml")){
			$ret = stream_copy_to_stream($resource, $fp = fopen($configFile, "wb")) > 0;
			fclose($fp);
			fclose($resource);
			return $ret;
		}
		return false;
	}
}