<?php
namespace GDO\Guestbook;

use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_CreatedBy;
use GDO\Core\GDT_DeletedAt;
use GDO\Core\GDT_DeletedBy;
use GDO\Core\GDT_Object;
use GDO\Core\GDT_Template;
use GDO\Date\GDT_DateTime;
use GDO\Mail\GDT_Email;
use GDO\Net\GDT_Url;
use GDO\UI\GDT_Message;
use GDO\User\GDO_User;
use GDO\User\GDT_User;

/**
 * Guestbook messages.
 * Not cached.
 * Approval optional.
 * Email and Website optional.
 *
 * @version 6.10
 * @since 6.09
 * @author gizmore
 */
final class GDO_GuestbookMessage extends GDO
{

	###########
	### GDO ###
	###########
	public function gdoCached(): bool { return false; }

	public function gdoColumns(): array
	{
		return [
			GDT_AutoInc::make('gbm_id'),
			GDT_Object::make('gbm_guestbook')->notNull()->writeable(false)->table(GDO_Guestbook::table())->hidden(),
			GDT_Message::make('gbm_message')->notNull(),
			GDT_Email::make('gbm_email')->searchable(false),
			GDT_Checkbox::make('gbm_email_public')->notNull()->initial('0')->hidden(),
			GDT_Url::make('gbm_website')->reachable()->noFollow(),
			GDT_CreatedBy::make('gbm_user')->writeable(false),
			GDT_CreatedAt::make('gbm_created'),
			GDT_User::make('gbm_approver')->writeable(false)->label('gbm_approver')->hidden(),
			GDT_DateTime::make('gbm_approved')->writeable(false)->hidden(),
			GDT_DeletedBy::make('gbm_deletor'),
			GDT_DeletedAt::make('gbm_deleted'),
		];
	}

	##############
	### Getter ###
	##############
	/**
	 * @return GDO_User
	 */
	public function getUser() { return $this->gdoValue('gbm_user'); }

	/**
	 * @return GDO_Guestbook
	 */
	public function getGuestbook() { return $this->gdoValue('gbm_guestbook'); }

	public function getGuestbookID() { return $this->gdoVar('gbm_guestbook'); }

	public function displayMessage() { return $this->gdoColumn('gbm_message')->renderHTML(); }

	public function displayEmail() { return $this->gdoColumn('gbm_email')->renderHTML(); }

	public function displayWebsite() { return $this->gdoValue('gbm_website') ? $this->gdoColumn('gbm_website')->renderHTML() : ''; }

	public function hrefEdit() { return href('Guestbook', 'Edit', "&id={$this->getID()}"); }

	##############
	### Render ###
	##############

	public function hrefDelete() { return href('Guestbook', 'Delete', "&id={$this->getID()}&token={$this->gdoHashcode()}"); }

	public function hrefApprove() { return href('Guestbook', 'Approve', "&id={$this->getID()}&token={$this->gdoHashcode()}"); }

	public function canDelete(GDO_User $user = null)
	{
		if ($this->isDeleted())
		{
			return false;
		}
		$user = $user ? $user : GDO_User::current();
		return $user->isStaff() || ($user->getID() === $this->getUserID());
	}

	public function isDeleted(): bool { return $this->gdoVar('gbm_deleted') !== null; }

	############
	### HREF ###
	############

	public function getUserID() { return $this->gdoVar('gbm_user'); }

	public function renderList(): string { return GDT_Template::php('Guestbook', 'list/message.php', ['gdo' => $this]); }

	public function canApprove(GDO_User $user = null)
	{
		if ($this->isApproved())
		{
			return false;
		}
		$user = $user ? $user : GDO_User::current();
		return $user->isStaff();
	}

	##################
	### Permission ###
	##################

	public function isApproved() { return $this->gdoVar('gbm_approved') !== null; }

	public function canSeeMail(GDO_User $user)
	{
		if ($user->isStaff())
		{
			return true;
		}
		return $this->isEMailPublic();
	}

	public function isEMailPublic() { return $this->gdoValue('gbm_email_public'); }

}
