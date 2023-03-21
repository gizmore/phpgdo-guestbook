<?php
namespace GDO\Guestbook\Method;

use GDO\Core\GDO;
use GDO\Form\MethodCrud;
use GDO\Guestbook\GDO_GuestbookMessage;

final class Edit extends MethodCrud
{

	public function hrefList(): string
	{
		return href('Guestbook', 'View', "id={$this->gdo->getID()}");
	}

	public function gdoTable(): GDO
	{
		return GDO_GuestbookMessage::table();
	}


}
