<?php

declare(strict_types=1);

namespace kim\present\hotbox\command;

use kim\present\hotbox\HotBox;
use kim\present\hotbox\inventory\HotBoxRewardInventory;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class OpenSubcommand extends Subcommand{
	/**
	 * OpenSubcommand constructor.
	 *
	 * @param HotBox $plugin
	 */
	public function __construct(HotBox $plugin){
		parent::__construct($plugin, "open");
	}

	/**
	 * @param CommandSender $sender
	 */
	public function execute(CommandSender $sender) : void{
		if($sender instanceof Player){
			$sender->addWindow(new HotBoxRewardInventory($sender));
		}else{
			$sender->sendMessage($this->plugin->getLanguage()->translateString("commands.generic.onlyPlayer"));
		}
	}
}