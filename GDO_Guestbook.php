<?php
declare(strict_types=1);
namespace GDO\Guestbook;

use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_CreatedAt;
use GDO\UI\GDT_Error;
use GDO\UI\GDT_Message;
use GDO\UI\GDT_Title;
use GDO\User\GDO_User;
use GDO\User\GDT_Level;
use GDO\User\GDT_User;

/**
 * A Guestbook.
 * Mutliple guestbooks are possible. (one per user)
 *
 * @version 7.0.3
 * @since 3.0.0
 * @author gizmore
 */
final class GDO_Guestbook extends GDO
{

	###############
	### Factory ###
	###############
	public static function forSite(): ?GDO_Guestbook { return self::getById('1'); }

	public static function forUser(GDO_User $user): ?GDO_Guestbook { return self::forUserID($user->getID()); }

	public static function forUserID(string $userid): ?GDO_Guestbook { return self::getBy('gb_uid', $userid); }

	###########
	### GDO ###
	###########
	public function gdoColumns(): array
	{
		return [
			GDT_AutoInc::make('gb_id'),
			GDT_User::make('gb_uid')->writeable(false),
			GDT_Title::make('gb_title')->notNull(),
			GDT_Message::make('gb_descr')->label('description'),
			GDT_CreatedAt::make('gb_date'),
			GDT_Level::make('gb_level')->label('guestbook_level'),
			GDT_Checkbox::make('gb_unlocked')->initial('1'),
			GDT_Checkbox::make('gb_moderated')->initial('0'),
			GDT_Checkbox::make('gb_notify_mail')->initial('1'),
			GDT_Checkbox::make('gb_guest_view')->initial('1'),
			GDT_Checkbox::make('gb_guest_sign')->initial('1'),
			GDT_Checkbox::make('gb_allow_url')->initial('0'),
			GDT_Checkbox::make('gb_allow_email')->initial('1'),
		];
	}

	##################
	### Convinient ###
	##################

	public function getDescr(): ?string { return $this->gdoVar('gb_descr'); }

	public function getDate(): ?string { return $this->gdoVar('gb_date'); }

	public function isModerated(): bool { return $this->gdoValue('gb_moderated'); }

	public function isURLAllowed(): bool { return $this->gdoValue('gb_allow_url'); }

	public function isEMailAllowed(): bool { return $this->gdoValue('gb_allow_email'); }

	# Options

	public function isEMailOnSign(): bool { return $this->gdoValue('gb_notify_mail'); }

	public function displayTitle(): string { return html($this->getTitle()); }

	public function getTitle(): ?string { return $this->gdoVar('gb_title'); }

	public function displayDescription(): string { return $this->gdoColumn('gb_descr')->renderHTML(); }

	public function href_gb_edit(): string { return href('Guestbook', 'Crud', '&id=' . $this->getID()); }

	public function href_gb_view(): string { return href('Guestbook', 'View', '&id=' . $this->getID()); }

	public function href_gb_sign(): string { return href('Guestbook', 'Sign', '&id=' . $this->getID()); }

	public function href_gb_approval(): string { return href('Guestbook', 'ApproveList', '&id=' . $this->getID()); }

	##############
	### Render ###
	##############

	/**
	 * Get all users that want to be notified. Staff and owner.
	 *
	 * @return GDO_User[]
	 */
	public function getNotifyUsers(): array
	{
		$users = GDO_User::staff();
		if ($user = $this->getUser())
		{
			if (!in_array($user, $users, true))
			{
				$users[] = $user;
			}
		}
		return $users;
	}

	public function getUser(): GDO_User { return $this->gdoValue('gb_uid'); }

	#############
	### HREFs ###
	#############

	public function canCreate(GDO_User $user): bool
	{
		return $user->isAuthenticated() && ($user->getLevel() >= Module_Guestbook::instance()->cfgLevel());
	}

	public function getLevel(): int { return $this->gdoValue('gb_level'); }

	public function canModerate(GDO_User $user): bool
	{
		return $user->isStaff() || ($user->getID() === $this->getUserID());
	}

	public function getUserID(): ?string { return $this->gdoVar('gb_uid'); }

	##############
	### Notify ###
	##############

	public function canView(GDO_User $user): bool
	{
		if ($user->isMember())
		{
			return true;
		}
		elseif (Module_Guestbook::instance()->cfgAllowGuestView())
		{
			if ($this->isGuestViewable())
			{
				return true;
			}
		}
		return false;
	}

	##################
	### Permission ###
	##################

	public function isGuestViewable(): bool { return $this->gdoValue('gb_guest_view'); }

	public function canSign(GDO_User $user, string &$error = '', array &$args = []): bool
	{
		$mod = Module_Guestbook::instance();

		if ($this->isLocked())
		{
			$error = 'err_guestbook_locked';
		}

		elseif (!$user->isMember())
		{
			if ((!$mod->cfgAllowGuestSign()) || (!$this->isGuestWriteable()))
			{
				$error = 'err_no_guests';
			}
		}

		elseif ($mod->cfgAllowgLevel())
		{
			if ($this->getLevel() > $user->getLevel())
			{
				$error = 'err_level_too_low';
				$args = [$this->getLevel(), $user->getLevel()];
			}
		}

		return !$error;
	}

	public function isLocked(): bool { return !$this->gdoValue('gb_unlocked'); }

	public function isGuestWriteable(): bool { return $this->gdoValue('gb_guest_sign'); }

}
