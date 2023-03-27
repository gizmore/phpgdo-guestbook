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
 * Moderate a guestbook entry.
 *
 * @version 6.10
 * @since 6.10
 * @author gizmore
 */
final class Approve extends Method
{

	public function gdoParameters(): array
	{
		return [
			GDT_Token::make('token'),
			GDT_Object::make('id')->table(GDO_GuestbookMessage::table()),
		];
	}

	public function execute(): GDT
	{
		$message = $this->getMessage();
		if ($this->getToken() !== $message->gdoHashcode())
		{
			return $this->error('err_token');
		}
		if ($message->isApproved())
		{
			return $this->error('err_already_approved');
		}

		return $this->approve($message);
	}

	/**
	 * @return GDO_GuestbookMessage
	 */
	public function getMessage() { return $this->gdoParameterValue('id'); }

	public function getToken() { return $this->gdoParameterVar('token'); }

	public function approve(GDO_GuestbookMessage $message)
	{
		$message->saveVars([
			'gbm_approved' => Time::getDate(),
			'gbm_approved_by' => GDO_User::current()->getID(),
		]);

		$href = href('Guestbook', 'ApproveList', '&id=' . $message->getGuestbookID());
		return $this->message('msg_gbm_approved')->addField($this->redirect($href, 12));
	}

}
