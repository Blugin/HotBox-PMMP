<?php

declare(strict_types=1);

namespace kim\present\hotbox\form;

use kim\present\hotbox\HotBox;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Player;
use pocketmine\Server;

class SubcommandSelectForm extends MenuForm{
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
	 * SubcommandSelectForm constructor.
	 *
	 * @param HotBox $plugin
	 */
	public function __construct(HotBox $plugin){
		$this->plugin = $plugin;

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
		$subcommands = $this->plugin->getSubcommands();
		if(!isset($subcommands[$this->selectedOption])){
			return null;
		}
		$subcommands[$this->selectedOption]->handle($player);

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