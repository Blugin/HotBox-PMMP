<?php

declare(strict_types=1);

namespace kim\present\hotbox;

use kim\present\hotbox\form\SubcommandSelectForm;
use kim\present\hotbox\inventory\HotBoxInventory;
use kim\present\hotbox\inventory\HotBoxRewardInventory;
use kim\present\hotbox\lang\PluginLang;
use pocketmine\command\{
	Command, CommandSender, PluginCommand
};
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class HotBox extends PluginBase{
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
	private $isHotTime = false;

	/**
	 * @var int
	 */
	private $lastTime;

	/**
	 * @var HotBoxInventory
	 */
	private $hotBoxInventory;

	/**
	 * @var SubcommandSelectForm
	 */
	private $subcommandSelectForm;

	public function onLoad() : void{
		self::$instance = $this;
	}

	public function onEnable() : void{
		foreach($this->getResources() as $filename => $fileInfo){
			if(stripos(strrev($filename), strrev("config.yml")) !== 0){
				$this->saveResource($filename, false);
			}
		}
		$this->saveDefaultConfig();
		$this->reloadConfig();
		$config = $this->getConfig();
		$this->language = new PluginLang($this, $config->getNested("settings.language"));
		$this->getLogger()->info($this->language->translateString("language.selected", [$this->language->getName(), $this->language->getLang()]));

		//Load hot time reward data
		if(file_exists($file = "{$this->getDataFolder()}HotBoxInventory.dat")){
			$namedTag = (new BigEndianNBTStream())->readCompressed(file_get_contents($file));
			if($namedTag instanceof CompoundTag){
				$this->lastTime = $namedTag->getInt("LastTime");
				$this->hotBoxInventory = HotBoxInventory::nbtDeserialize($namedTag->getListTag("HotBoxInventory"));
			}else{
				$this->getLogger()->error("The file is not in the NBT-CompoundTag format : $file");
			}
		}else{
			$this->lastTime = -1;
			$this->hotBoxInventory = new HotBoxInventory();
		}

		$this->command = new PluginCommand($config->getNested("command.name"), $this);
		$this->command->setAliases($config->getNested("command.aliases"));
		$this->command->setUsage($this->language->translateString("commands.hotbox.usage"));
		$this->command->setDescription($this->language->translateString("commands.hotbox.description"));
		$this->getServer()->getCommandMap()->register($this->getName(), $this->command);

		$this->subcommandSelectForm = new SubcommandSelectForm($this);
	}

	public function onDisable() : void{
		//Save hot time reward data
		$namedTag = new CompoundTag("HotBox", [
			new IntTag("LastTime", $this->lastTime),
			$this->hotBoxInventory->nbtSerialize("HotBoxInventory")
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
				if($sender->hasPermission("hotbox.cmd.open") && !$sender->hasPermission("hotbox.cmd.edit")){
					$sender->addWindow(new HotBoxRewardInventory($sender));
				}else{
					$this->subcommandSelectForm->sendForm($sender);
				}
			}else{
				$config = $this->getConfig();
				if(strcasecmp($args[0], $config->getNested("command.children.open.name")) == 0){
					if($sender->hasPermission("hotbox.cmd.open")){
						$sender->addWindow(new HotBoxRewardInventory($sender));
					}else{
						$sender->sendMessage($this->language->translateString("commands.generic.permission"));
					}
				}elseif(strcasecmp($args[0], $config->getNested("command.children.edit.name")) == 0){
					if($sender->hasPermission("hotbox.cmd.edit")){
						$sender->addWindow($this->hotBoxInventory);
					}else{
						$sender->sendMessage($this->language->translateString("commands.generic.permission"));
					}
				}elseif(strcasecmp($args[0], $config->getNested("command.children.on.name")) == 0){
					if($sender->hasPermission("hotbox.cmd.on")){
						if($this->isHotTime){
							$sender->sendMessage($this->language->translateString("commands.hotbox.on.already"));
						}else{
							$this->isHotTime = true;
							$this->lastTime = time();
							$sender->sendMessage($this->language->translateString("commands.hotbox.on.success"));
						}
					}else{
						$sender->sendMessage($this->language->translateString("commands.generic.permission"));
					}
				}elseif(strcasecmp($args[0], $config->getNested("command.children.off.name")) == 0){
					if($sender->hasPermission("hotbox.cmd.off")){
						if($this->isHotTime){
							$this->isHotTime = false;
							$sender->sendMessage($this->language->translateString("commands.hotbox.off.success"));
						}else{
							$sender->sendMessage($this->language->translateString("commands.hotbox.off.already"));
						}
						$sender->addWindow($this->hotBoxInventory);
					}else{
						$sender->sendMessage($this->language->translateString("commands.generic.permission"));
					}
				}else{
					return false;
				}
			}
		}
		return true;
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
	public function getHotBoxInventory() : HotBoxInventory{
		return $this->hotBoxInventory;
	}

	/**
	 * @return SubcommandSelectForm
	 */
	public function getSubcommandSelectForm() : SubcommandSelectForm{
		return $this->subcommandSelectForm;
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