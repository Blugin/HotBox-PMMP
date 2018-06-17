<?php

declare(strict_types=1);

namespace kim\present\hotbox\listener;

use kim\present\hotbox\inventory\HotBoxRewardInventory;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\transaction\action\SlotChangeAction;

class InventoryEventListener implements Listener{
	/**
	 * @priority MONITOR
	 *
	 * @param InventoryTransactionEvent $event
	 */
	public function onInventoryTransactionEvent(InventoryTransactionEvent $event) : void{
		foreach($event->getTransaction()->getActions() as $key => $action){
			if($action instanceof SlotChangeAction){
				$inventory = $action->getInventory();
				if($inventory instanceof HotBoxRewardInventory && $action->getSourceItem()->count < $action->getTargetItem()->count){
					$event->setCancelled();
				}
			}
		}
	}
}
