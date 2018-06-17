<?php

declare(strict_types=1);

namespace kim\present\hotbox\form;

use kim\present\hotbox\HotBox;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Player;
use pocketmine\Server;

class SubcommandSelectForm extends MenuForm{
	public const OPTION_OPEN = 0;
	public const OPTION_EDIT = 1;
	public const OPTION_ON = 2;
	public const OPTION_OFF = 3;

	/**
	 * @var SubcommandSelectForm
	 */
	private static $instance;

	/**
	 * @return SubcommandSelectForm
	 */
	public static function getInstance() : SubcommandSelectForm{
		if(self::$instance === null){
			self::$instance = new SubcommandSelectForm(HotBox::getInstance());
		}
		return self::$instance;
	}

	/**
	 * @var HotBox
	 */
	private $plugin;

	/**
	 * @var string[]
	 */
	private $commands;

	/**
	 * SubcommandSelectForm constructor.
	 *
	 * @param HotBox $plugin
	 */
	public function __construct(HotBox $plugin){
		$this->plugin = $plugin;

		$config = $this->plugin->getConfig();

		$command = "/" . $config->getNested("command.name") . " ";
		$this->commands = [
			self::OPTION_OPEN => $command . $config->getNested("command.children.open.name"),
			self::OPTION_EDIT => $command . $config->getNested("command.children.edit.name"),
			self::OPTION_ON => $command . $config->getNested("command.children.enable.name"),
			self::OPTION_OFF => $command . $config->getNested("command.children.disable.name")
		];


		$lang = $plugin->getLanguage();
		parent::__construct($lang->translateString("hotbox.menu.title"), $lang->translateString("hotbox.menu.text"), [
			new MenuOption($lang->translateString("hotbox.menu.option.open.text")),
			new MenuOption($lang->translateString("hotbox.menu.option.edit.text")),
			new MenuOption($lang->translateString("hotbox.menu.option.enable.text")),
			new MenuOption($lang->translateString("hotbox.menu.option.disable.text")),
		]);
	}

	/**
	 * @param Player $player
	 *
	 * @return null|Form
	 */
	public function onSubmit(Player $player) : ?Form{
		if(!isset($this->commands[$this->selectedOption])){
			return null;
		}
		$event = new PlayerCommandPreprocessEvent($player, $this->commands[$this->selectedOption]);
		$server = Server::getInstance();
		$server->getPluginManager()->callEvent($event);
		if(!$event->isCancelled()){
			$server->dispatchCommand($player, substr($event->getMessage(), 1));
		}

		return null;
	}

	/**
	 * @param Player $player
	 */
	public function sendForm(Player $player) : void{
		$formPacket = new ModalFormRequestPacket();
		$formPacket->formId = (int) $this->plugin->getConfig()->getNested("settings.formId");
		$formPacket->formData = json_encode($this->jsonSerialize());
		$player->dataPacket($formPacket);
	}
}