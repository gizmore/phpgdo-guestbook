<?php
declare(strict_types=1);
namespace GDO\Guestbook;

use GDO\Core\GDO_Module;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_Object;
use GDO\Core\GDT_UInt;
use GDO\UI\GDT_Bar;
use GDO\UI\GDT_Card;
use GDO\UI\GDT_Link;
use GDO\UI\GDT_Page;
use GDO\User\GDO_User;
use GDO\User\GDT_Level;


/**
 * Guestbooks for a gdo site and optionally members.
 *
 * - Captcha options
 * - Guest options
 * - more...
 *
 * @version 7.0.3
 * @since 3.0.0
 *
 * @author gizmore
 * @see GDO_Guestbook
 * @see GDO_GuestbookMessage
 */
final class Module_Guestbook extends GDO_Module
{

	######################
	### Site Guestbook ###
	######################
	public int $priority = 45;

	##############
	### Module ###
	##############

	public function onInstall(): void { Install::onInstall(); }

	public function onLoadLanguage(): void { $this->loadLanguage('lang/guestbook'); }

	public function getDependencies(): array
	{
		return [
			'Admin',
		];
	}

	public function getClasses(): array
	{
		return [
			GDO_Guestbook::class,
			GDO_GuestbookMessage::class,
		];
	}

	public function href_administrate_module(): ?string { return href('Guestbook', 'Admin'); }

	#############
	### Admin ###
	#############

	public function getFriendencies(): array
	{
		return [
			'Captcha',
			'HTML',
			'Markdown',
		];
	}

	public function getConfig(): array
	{
		return [
			GDT_UInt::make('gb_ipp')->initial('10')->max(100),
			GDT_Checkbox::make('gb_allow_guest_view')->initial('1'),
			GDT_Checkbox::make('gb_allow_guest_sign')->initial('1'),
			GDT_Checkbox::make('gb_allow_url')->initial('1'),
			GDT_Checkbox::make('gb_allow_email')->initial('1'),
			GDT_Checkbox::make('gb_allow_level')->initial('1'),
			GDT_Checkbox::make('gb_allow_user_gb')->initial('0'),
			GDT_Level::make('gb_user_gb_level'),
			GDT_Checkbox::make('gb_guest_captcha')->initial('1'),
			GDT_Checkbox::make('gb_member_captcha')->initial('0'),
			GDT_Checkbox::make('gb_left_bar')->initial('1'),
			GDT_Checkbox::make('gb_right_bar')->initial('1'),
		];
	}

	public function getUserConfig(): array
	{
		$config = [];
		$config[] = GDT_Object::make('user_guestbook')->table(GDO_Guestbook::table());
		return $config;
	}

	##############
	### Config ###
	##############

	public function getUserSettings(): array
	{
		$config = [];
		if ($this->cfgAllowUserGB())
		{
			if ($gb = $this->getUserGuestbook())
			{
				$config[] = GDT_Link::make('link_edit_guestbook')->href(href('Guestbook', 'Crud', '&id=' . $gb->getID()));
			}
			else
			{
				$config[] = GDT_Link::make('link_create_guestbook')->href(href('Guestbook', 'Crud'));
			}
		}
		return $config;
	}

	public function cfgAllowUserGB(): bool
	{
		return GDO_User::current()->isMember() ?
			$this->getConfigValue('gb_allow_user_gb') : false;
	}

	public function getUserGuestbook(GDO_User $user = null): ?GDO_Guestbook
	{
		$user = $user ?: GDO_User::current();
		return self::instance()->userSettingValue($user, 'user_guestbook');
	}

	public function onInitSidebar(): void
	{
 	    if ($this->cfgLeftBar())
		{
			if ($gb = $this->getSiteGuestbook())
			{
				if ($gb->canView(GDO_User::current()))
				{
					$bar = GDT_Page::instance()->leftBar();
					$bar->addField(GDT_Link::make('link_guestbook')->href(href('Guestbook', 'View', '&id=1')));
				}
			}
		}
 	    if ($this->cfgRightBar())
		{
			if ($this->cfgAllowUserGB())
			{
				if ($gb = $this->getUserGuestbook())
				{
					$bar = GDT_Page::$INSTANCE->rightBar();
					$bar->addField(GDT_Link::make('link_your_guestbook')->href(href('Guestbook', 'View', '&id=' . $gb->getID())));
				}
			}
		}
	}

	public function getSiteGuestbook(): ?GDO_Guestbook
	{
		return $this->isInstalled() ? GDO_Guestbook::getById('1') : null;
	}

	public function guestbookViewBar(GDO_Guestbook $gb = null): GDT_Bar
	{
		if (!$gb)
		{
			return $this->adminBar();
		}
		$user = GDO_User::current();
		$bar = GDT_Bar::make()->horizontal();

		$linkSign = GDT_Link::make('link_sign_guestbook')->enabled($gb->canSign($user))->href($gb->href_gb_sign());
		$bar->addField($linkSign);

		if ($gb->canModerate($user))
		{
			$linkEdit = GDT_Link::make('link_edit_guestbook')->enabled($gb->canModerate($user))->href($gb->href_gb_edit());
			$bar->addField($linkEdit);

			$linkApproval = GDT_Link::make('link_gb_approval_list')->enabled($gb->canModerate($user))->href($gb->href_gb_approval());
			$bar->addField($linkApproval);
		}
		return $bar;
	}

	public function adminBar(): GDT_Bar
	{
		$bar = GDT_Bar::make()->horizontal();

		$linkApprovals = GDT_Link::make('link_approvals')->href(href('Guestbook', 'ApproveList'));
		$bar->addField($linkApprovals);

		$linkSiteConfig = GDT_Link::make('link_site_guestbook')->href(href('Guestbook', 'Crud', '&id=1'));
		$bar->addField($linkSiteConfig);

		$linkConfig = GDT_Link::make('link_configure_gb_module')->href(href('Admin', 'Configure', '&module=Guestbook'));
		$bar->addField($linkConfig);

		return $bar;
	}

	public function cfgItemsPerPage(): int { return $this->getConfigValue('gb_ipp'); }

	public function cfgAllowGuestView(): bool { return $this->getConfigValue('gb_allow_guest_view'); }

	public function cfgAllowGuestSign(): bool { return $this->getConfigValue('gb_allow_guest_sign'); }

	public function cfgAllowURL(): bool { return $this->getConfigValue('gb_allow_url'); }

	public function cfgAllowEMail(): bool { return $this->getConfigValue('gb_allow_email'); }

	public function cfgAllowgLevel(): bool { return $this->getConfigValue('gb_allow_level'); }

	public function cfgLevel(): int { return $this->getConfigValue('gb_user_gb_level'); }

	public function cfgLeftBar(): bool { return $this->getConfigValue('gb_left_bar'); }

	public function cfgRightBar(): bool { return $this->getConfigValue('gb_right_bar'); }

	public function cfgCaptcha(): bool
	{
		$user = GDO_User::current();
		return $user->isMember() ? $this->cfgMemberCaptcha() : $this->cfgGuestCaptcha();
	}

	public function cfgMemberCaptcha(): bool
	{
		return module_enabled('Captcha') &&
			$this->getConfigValue('gb_member_captcha');
	}

	public function cfgGuestCaptcha(): bool
	{
		return module_enabled('Captcha') &&
			$this->getConfigValue('gb_guest_captcha');
	}

	/**
	 * Add a guestbook link to profiles via a hook
	 */
	public function hookProfileCard(GDO_User $user, GDT_Card $card): void
	{
		if ($gb = GDO_Guestbook::forUser($user))
		{
			$card->addField(GDT_Link::make('link_guestbook')->
				text('view_users_guestbook', [$user->renderUserName()])->
				href($gb->href_gb_view())->icon('book'));
		}
	}

}
