<?php
/**
 * Neevo - Tiny database layer for PHP. (http://neevo.smasty.net)
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file license.txt.
 *
 * Copyright (c) 2012 Smasty (http://smasty.net)
 *
 */

namespace Neevo\Observable;


/**
 * Neevo observable subject interface.
 * @author Smasty
 */
interface SubjectInterface {


	/**
	 * Attaches given observer to given event.
	 * @param ObserverInterface $observer
	 * @param int $event
	 */
	public function attachObserver(ObserverInterface $observer, $event);


	/**
	 * Detaches given observer.
	 * @param ObserverInterface $observer
	 */
	public function detachObserver(ObserverInterface $observer);


	/**
	 * Notifies all observers attached to given event.
	 * @param int $event
	 */
	public function notifyObservers($event);


}