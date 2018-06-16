<?php

declare(strict_types=1);

namespace kim\present\hotbox\listener;

use kim\present\hotbox\HotBox;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;

class PlayerEventListener implements Listener{
	/**
	 * @var HotBox
	 */
	private $owner;

	/**
	 * PlayerEventListener constructor.
	 *
	 * @param HotBox $owner
	 */
	public function __construct(HotBox $owner){
		$this->owner = $owner;
	}

	/**
	 * @priority HIGHEST
	 *
	 * @param DataPacketReceiveEvent $event
	 */
	public function onDataPacketReceiveEvent(DataPacketReceiveEvent $event) : void{
		$pk = $event->getPacket();
		if($pk instanceof ModalFormResponsePacket && $pk->formId === (int) $this->owner->getConfig()->getNested("settings.formId")){
			$this->owner->getSubcommandSelectForm()->handleResponse($event->getPlayer(), json_decode($pk->formData));
			$event->setCancelled();
		}
	}
}