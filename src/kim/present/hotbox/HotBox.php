<?php

declare(strict_types=1);

namespace kim\present\hotbox;

use kim\present\hotbox\lang\PluginLang;
use pocketmine\command\{
	Command, CommandSender, PluginCommand
};
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

		$this->command = new PluginCommand($config->getNested("command.name"), $this);
		$this->command->setAliases($config->getNested("command.aliases"));
		$this->command->setUsage($this->language->translateString("commands.hotbox.usage"));
		$this->command->setDescription($this->language->translateString("commands.hotbox.description"));
		$this->getServer()->getCommandMap()->register($this->getName(), $this->command);
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
		return false;
	}

	/**
	 * @return PluginLang
	 */
	public function getLanguage() : PluginLang{
		return $this->language;
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