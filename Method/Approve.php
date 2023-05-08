<?php
declare(strict_types=1);
namespace GDO\Guestbook\Method;

use GDO\Core\GDO_ArgError;
use GDO\Core\GDO_DBException;
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
 * @version 7.0.3
 * @since 6.10
 * @author gizmore
 */
final class Approve extends Method
{

	public function gdoParameters(): array
	{
		return [
			GDT_Token::make('token')->notNull(),
			GDT_Object::make('id')->table(GDO_GuestbookMessage::table())->notNull(),
		];
	}

	/**
	 * @throws GDO_DBException
	 * @throws GDO_ArgError
	 */
	public function execute(): GDT
	{
		$message = $this->getMessage();
		if ($message->isApproved())
		{
			return $this->error('err_already_approved');
		}
		if ($this->getToken() !== $message->gdoHashcode())
		{
			return $this->error('err_token');
		}
		return $this->approve($message);
	}

	/**
	 * @throws GDO_ArgError
	 */
	public function getMessage(): GDO_GuestbookMessage { return $this->gdoParameterValue('id'); }

	/**
	 * @throws GDO_ArgError
	 */
	public function getToken(): string { return $this->gdoParameterVar('token'); }

	/**
	 * @throws GDO_DBException
	 */
	public function approve(GDO_GuestbookMessage $message): GDT
	{
		$message->saveVars([
			'gbm_approved' => Time::getDate(),
			'gbm_approved_by' => GDO_User::current()->getID(),
		]);

		$href = href('Guestbook', 'ApproveList', '&id=' . $message->getGuestbookID());
		return $this->message('msg_gbm_approved')->addField($this->redirect($href, 12));
	}

}
