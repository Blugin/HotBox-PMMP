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
use kim\present\hotbox\task\CheckUpdateAsyncTask;
use pocketmine\command\{
	Command, CommandSender, PluginCommand
};
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\permission\Permission;
use pocketmine\plugin\PluginBase;

class HotBox extends PluginBase{
	public const OPEN = 0;
	public const EDIT = 1;
	public const ENABLE = 2;
	public const DISABLE = 3;

	public const START_TIME_TAG = "StartTime";
	public const END_TIME_TAG = "EndTime";
	public const INVENTORY_TAG = "HotBoxInventory";

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
	 * @var Subcommand[]
	 */
	private $subcommands;

	/**
	 * @var HotBoxInventory
	 */
	private $inventory;

	/**
	 * @var int
	 */
	private $startTime, $endTime;

	public function onLoad() : void{
		self::$instance = $this;

		//Check latest version
		$this->getServer()->getAsyncPool()->submitTask(new CheckUpdateAsyncTask());
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
				$this->startTime = $namedTag->getInt(HotBox::START_TIME_TAG, -1);
				$this->endTime = $namedTag->getInt(HotBox::END_TIME_TAG, -1);
				$this->inventory = HotBoxInventory::nbtDeserialize($namedTag->getListTag(HotBox::INVENTORY_TAG));
			}else{
				$this->getLogger()->error("The file is not in the NBT-CompoundTag format : $file");
			}
		}else{
			$this->startTime = -1;
			$this->endTime = -1;
			$this->inventory = new HotBoxInventory();
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
			self::ENABLE => new EnableSubcommand($this),
			self::DISABLE => new DisableSubcommand($this)
		];

		//Load permission's default value from config
		$permissions = $this->getServer()->getPluginManager()->getPermissions();
		$defaultValue = $config->getNested("permission.main");
		if($defaultValue !== null){
			$permissions["hotbox.cmd"]->setDefault(Permission::getByName($config->getNested("permission.main")));
		}
		foreach($this->subcommands as $key => $subcommand){
			$label = $subcommand->getLabel();
			$defaultValue = $config->getNested("permission.children.{$label}");
			if($defaultValue !== null){
				$permissions["hotbox.cmd.{$label}"]->setDefault(Permission::getByName($defaultValue));
			}
		}

		//Register event listeners
		$this->getServer()->getPluginManager()->registerEvents(new InventoryEventListener(), $this);
	}

	public function onDisable() : void{
		//Save hot-time reward data
		$namedTag = new CompoundTag("HotBox", [
			new IntTag(HotBox::START_TIME_TAG, $this->startTime),
			new IntTag(HotBox::END_TIME_TAG, $this->endTime),
			$this->inventory->nbtSerialize(HotBox::INVENTORY_TAG),
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
		}else{
			$label = array_shift($args);
			foreach($this->subcommands as $key => $subcommand){
				if($subcommand->checkLabel($label)){
					$subcommand->handle($sender, $args);
					return true;
				}
			}
		}
		return false;
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

	/**
	 * @return PluginLang
	 */
	public function getLanguage() : PluginLang{
		return $this->language;
	}

	/**
	 * @return Subcommand[]
	 */
	public function getSubcommands() : array{
		return $this->subcommands;
	}

	/**
	 * @return HotBoxInventory
	 */
	public function getInventory() : HotBoxInventory{
		return $this->inventory;
	}

	/**
	 * @return int
	 */
	public function getStartTime() : int{
		return $this->startTime;
	}

	/**
	 * @return int
	 */
	public function getEndTime() : int{
		return $this->endTime;
	}

	/**
	 * @return bool
	 */
	public function isHotTime() : bool{
		return time() < $this->endTime;
	}

	/**
	 * Start hot-time (Support duration)
	 *
	 * @param int $duration
	 *
	 * @return bool it same as `!$this->isStarted()`
	 */
	public function startHotTime(int $duration = null) : bool{
		if($this->isHotTime()){
			return false;
		}
		$this->startTime = time();
		if($duration === null){
			$this->endTime = 0x7FFFFFFF;
		}else{
			$this->endTime = $this->startTime + $duration;
		}
		return true;
	}

	/**
	 * Stop hot-time
	 *
	 * @return bool it same as `$this->isStarted()`
	 */
	public function stopHotTime() : bool{
		if($this->isHotTime()){
			$this->startTime = -1;
			$this->endTime = -1;
			return true;
		}
		return false;
	}
}