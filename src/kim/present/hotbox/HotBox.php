<?php

declare(strict_types=1);

namespace kim\present\hotbox;

use kim\present\hotbox\command\{
	DisableSubcommand, EditSubcommand, EnableSubcommand, OpenSubcommand, Subcommand
};
use kim\present\hotbox\form\SubcommandSelectForm;
use kim\present\hotbox\inventory\HotBoxInventory;
use kim\present\hotbox\inventory\HotBoxRewardInventory;
use kim\present\hotbox\lang\PluginLang;
use kim\present\hotbox\listener\InventoryEventListener;
use kim\present\hotbox\listener\PlayerEventListener;
use pocketmine\command\{
	Command, CommandSender, PluginCommand
};
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class HotBox extends PluginBase{
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

	/**
	 * @var SubcommandSelectForm
	 */
	private $menuForm;

	public function onLoad() : void{
		self::$instance = $this;
	}

	public function onEnable() : void{
		$this->saveResource("lang/eng/lang.ini", false);
		$this->saveResource("lang/kor/lang.ini", false);
		$this->saveResource("lang/language.list", false);

		$this->saveDefaultConfig();
		$this->reloadConfig();
		$config = $this->getConfig();
		$this->language = new PluginLang($this, $config->getNested("settings.language"));
		$this->getLogger()->info($this->language->translateString("language.selected", [$this->language->getName(), $this->language->getLang()]));

		//Load hot time reward data
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

		$this->command = new PluginCommand($config->getNested("command.name"), $this);
		$this->command->setAliases($config->getNested("command.aliases"));
		$this->command->setUsage($this->language->translateString("commands.hotbox.usage"));
		$this->command->setDescription($this->language->translateString("commands.hotbox.description"));
		$this->getServer()->getCommandMap()->register($this->getName(), $this->command);

		$this->subcommands = [
			new OpenSubcommand($this),
			new EditSubcommand($this),
			new EnableSubcommand($this),
			new DisableSubcommand($this)
		];
		$this->menuForm = new SubcommandSelectForm($this);
		$this->getServer()->getPluginManager()->registerEvents(new InventoryEventListener(), $this);
		$this->getServer()->getPluginManager()->registerEvents(new PlayerEventListener($this), $this);
	}

	public function onDisable() : void{
		//Save hot time reward data
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
				if($sender->hasPermission("hotbox.cmd.open") && !$sender->hasPermission("hotbox.cmd.edit")){
					$sender->addWindow(new HotBoxRewardInventory($sender));
				}else{
					$this->menuForm->sendForm($sender);
				}
				return true;
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
	 * @return SubcommandSelectForm
	 */
	public function getMenuForm() : SubcommandSelectForm{
		return $this->menuForm;
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