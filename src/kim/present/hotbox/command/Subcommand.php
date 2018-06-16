<?php

declare(strict_types=1);

namespace kim\present\hotbox\command;

use kim\present\hotbox\HotBox;
use pocketmine\command\CommandSender;

abstract class Subcommand{
	/**
	 * @var HotBox
	 */
	protected $plugin;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string[]
	 */
	private $aliases;

	/**
	 * @var string
	 */
	private $permission;

	/**
	 * Subcommand constructor.
	 *
	 * @param HotBox $plugin
	 * @param string $label
	 */
	public function __construct(HotBox $plugin, string $label){
		$this->plugin = $plugin;

		$config = $plugin->getConfig();
		$this->name = $config->getNested("command.children.{$label}.name");
		$this->aliases = $config->getNested("command.children.{$label}.aliases");
		$this->permission = "hotbox.cmd.{$label}";
	}


	/**
	 * @param string $label
	 *
	 * @return bool
	 */
	public function checkLabel(string $label) : bool{
		var_dump($label . " -> " . $this->name);
		return strcasecmp($label, $this->name) === 0 || in_array($label, $this->aliases);
	}

	/**
	 * @param CommandSender $sender
	 */
	public function handle(CommandSender $sender) : void{
		if($sender->hasPermission($this->permission)){
			$this->execute($sender);
		}else{
			$sender->sendMessage($this->plugin->getLanguage()->translateString("commands.generic.permission"));
		}
	}

	/**
	 * @param CommandSender $sender
	 */
	public abstract function execute(CommandSender $sender) : void;

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name) : void{
		$this->name = $name;
	}

	/**
	 * @return string[]
	 */
	public function getAliases() : array{
		return $this->aliases;
	}

	/**
	 * @param string[] $aliases
	 */
	public function setAliases(array $aliases) : void{
		$this->aliases = $aliases;
	}

	/**
	 * @return string
	 */
	public function getPermission() : string{
		return $this->permission;
	}

	/**
	 * @param string $permission
	 */
	public function setPermission(string $permission) : void{
		$this->permission = $permission;
	}
}