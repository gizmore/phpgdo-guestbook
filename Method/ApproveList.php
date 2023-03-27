<?php
namespace GDO\Guestbook\Method;

use GDO\Admin\MethodAdmin;
use GDO\Core\GDO;
use GDO\Core\GDT;
use GDO\Core\GDT_Object;
use GDO\DB\Query;
use GDO\Guestbook\GDO_Guestbook;
use GDO\Guestbook\GDO_GuestbookMessage;
use GDO\Guestbook\Module_Guestbook;
use GDO\Table\MethodQueryList;
use GDO\User\GDO_User;

/**
 * List entries which await approval.
 * If no id is parameterized, all entires are shown, but you need staff to do that.
 *
 * @version 6.10
 * @since 6.10
 * @author gizmore
 */
final class ApproveList extends MethodQueryList
{

	use MethodAdmin;

	public function gdoParameters(): array
	{
		return array_merge(parent::gdoParameters(), [
			GDT_Object::make('id')->table(GDO_Guestbook::table()),
		]);
	}

	public function gdoTable(): GDO
	{
		return GDO_GuestbookMessage::table();
	}

	public function getQuery(): Query
	{
		$query = parent::getQuery()->where('gbm_approved IS NULL')->where('gbm_deleted IS NULL');
		if ($gb = $this->getGuestbook())
		{
			$query->where('gbm_guestbook=' . $gb->getID());
		}
		return $query;
	}

	/**
	 * @return GDO_Guestbook
	 */
	public function getGuestbook()
	{
		return $this->gdoParameterValue('id');
	}

	public function execute(): GDT
	{
		if ($gb = $this->getGuestbook())
		{
			if (!$gb->canModerate(GDO_User::current()))
			{
				return $this->error('err_permission_required');
			}
		}
		$mod = Module_Guestbook::instance();
		return $mod->guestbookViewBar($gb)->addField(parent::execute());
	}

	public function getPermission(): ?string
	{
		return $this->getGuestbook() ? 'staff' : null;
	}

}
