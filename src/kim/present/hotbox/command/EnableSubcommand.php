<?php

declare(strict_types=1);

namespace kim\present\hotbox\command;

use kim\present\hotbox\HotBox;
use pocketmine\command\CommandSender;

class EnableSubcommand extends Subcommand{
	/**
	 * EnableSubcommand constructor.
	 *
	 * @param HotBox $plugin
	 */
	public function __construct(HotBox $plugin){
		parent::__construct($plugin, "enable");
	}

	/**
	 * @param CommandSender $sender
	 */
	public function execute(CommandSender $sender) : void{
		if($this->plugin->isHotTime()){
			$sender->sendMessage($this->plugin->getLanguage()->translateString("commands.hotbox.enable.already"));
		}else{
			$this->plugin->setHotTime(true);
			$this->plugin->setLastTime(time());
			$sender->sendMessage($this->plugin->getLanguage()->translateString("commands.hotbox.enable.success"));
		}
	}
}