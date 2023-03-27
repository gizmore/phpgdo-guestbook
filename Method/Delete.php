<?php
namespace GDO\Guestbook\Method;

use GDO\Core\GDT;
use GDO\Core\GDT_Object;
use GDO\Core\GDT_Token;
use GDO\Core\Method;
use GDO\Date\Time;
use GDO\Guestbook\GDO_GuestbookMessage;
use GDO\User\GDO_User;

/**
 * Delete an entry via token hashcode.
 *
 * @version 6.10
 * @since 6.10
 *
 * @author gizmore
 * @see GDT_Token
 * @see GDT_Object
 * @see Method
 */
final class Delete extends Method
{

	public function gdoParameters(): array
	{
		return [
			GDT_Object::make('id')->table(GDO_GuestbookMessage::table())->notNull(),
			GDT_Token::make('token')->notNull(),
		];
	}

	public function execute(): GDT
	{
		if (!($msg = $this->getMessage()))
		{
			return $this->error('err_gbmsg_not_found');
		}

		if ($msg->isDeleted())
		{
			return $this->error('err_already_deleted');
		}

		if ($this->getToken() !== $msg->gdoHashcode())
		{
			return $this->error('err_token');
		}

		$msg->saveVars([
			'gbm_deleted' => Time::getDate(),
			'gbm_deletor' => GDO_User::current()->getID(),
		]);

		$href = href('Guestbook', 'ApproveList', '&id=' . $msg->getGuestbookID());
		return $this->message('msg_gbmsg_deleted')->addField($this->redirect($href, 12));
	}

	/**
	 * @return GDO_GuestbookMessage
	 */
	public function getMessage() { return $this->gdoParameterValue('id'); }

	public function getToken() { return $this->gdoParameterVar('token'); }

}
