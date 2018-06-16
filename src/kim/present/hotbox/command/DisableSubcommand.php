<?php

declare(strict_types=1);

namespace kim\present\hotbox\command;

use kim\present\hotbox\HotBox;
use pocketmine\command\CommandSender;

class DisableSubcommand extends Subcommand{
	/**
	 * DisableSubcommand constructor.
	 *
	 * @param HotBox $plugin
	 */
	public function __construct(HotBox $plugin){
		parent::__construct($plugin, "disable");
	}

	/**
	 * @param CommandSender $sender
	 */
	public function execute(CommandSender $sender) : void{
		if($this->plugin->isHotTime()){
			$this->plugin->setHotTime(false);
			$sender->sendMessage($this->plugin->getLanguage()->translateString("commands.hotbox.disable.success"));
		}else{
			$sender->sendMessage($this->plugin->getLanguage()->translateString("commands.hotbox.disable.already"));
		}
	}
}