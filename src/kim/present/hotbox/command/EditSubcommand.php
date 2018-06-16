<?php

declare(strict_types=1);

namespace kim\present\hotbox\command;

use kim\present\hotbox\HotBox;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class EditSubcommand extends Subcommand{
	/**
	 * EditSubcommand constructor.
	 *
	 * @param HotBox $plugin
	 */
	public function __construct(HotBox $plugin){
		parent::__construct($plugin, "edit");
	}

	/**
	 * @param CommandSender $sender
	 */
	public function execute(CommandSender $sender) : void{
		if($sender instanceof Player){
			$sender->addWindow($this->plugin->getHotBoxInventory());
		}else{
			$sender->sendMessage($this->plugin->getLanguage()->translateString("commands.generic.onlyPlayer"));
		}
	}
}