<?php
declare(strict_types=1);
namespace GDO\Guestbook\Method;

use GDO\Admin\MethodAdmin;
use GDO\Captcha\GDT_Captcha;
use GDO\Core\GDO;
use GDO\Core\GDO_Exception;
use GDO\Core\GDT;
use GDO\Form\GDT_Form;
use GDO\Form\MethodCrud;
use GDO\Guestbook\GDO_Guestbook;
use GDO\Guestbook\Module_Guestbook;
use GDO\UI\GDT_Divider;
use GDO\UI\GDT_Page;
use GDO\UI\GDT_Redirect;
use GDO\User\GDO_User;

/**
 * Manage Guestbooks.
 * Every user may have at max 1 guestbook.
 *
 * @version 7.0.3
 * @since 6.0.10
 *
 * @author gizmore
 * @see GDO_Guestbook
 * @see Module_Guestbook
 */
final class Crud extends MethodCrud
{

	use MethodAdmin;

	public function onRenderTabs(): void
	{
		if ($this->getCRUDID() === '1')
		{
			$this->renderAdminBar();
		}
	}

	public function getPermission(): ?string
	{
		return $this->getCRUDID() === '1' ? 'staff' : null;
	}

	public function isUserRequired(): bool
	{
		return true;
	}

	public function isGuestAllowed(): bool
	{
		return false;
	}

	public function execute(): GDT
	{
		if (isset($this->gdo) && ($this->gdo->getID() === '1'))
		{
			$mod = Module_Guestbook::instance();
			GDT_Page::$INSTANCE->topBar()->addField($mod->adminBar());
		}
		return parent::execute();
	}

	public function hrefList(): string
	{
		return hrefDefault();
	}

	public function gdoTable(): GDO
	{
		return GDO_Guestbook::table();
	}

	public function canCreate(GDO|GDO_Guestbook $gdo): bool
	{
		return Module_Guestbook::instance()->cfgAllowUserGB();
	}

	public function canRead(GDO|GDO_Guestbook $gdo): bool
	{
		return true;
	}

	public function canUpdate(GDO|GDO_Guestbook $gdo): bool
	{
		if ($gdo->getID() === '1')
		{
			return GDO_User::current()->isStaff();
		}
		if (GDO_User::current()->isStaff())
		{
			return true;
		}
		return $gdo->getUser() === GDO_User::current();
	}

	public function canDelete(GDO|GDO_Guestbook $gdo): bool
	{
		return $this->canUpdate($gdo);
	}

	public function hasPermission(GDO_User $user, string &$error, array &$args): bool
	{
		parent::hasPermission($user, $error, $args);

		$mod = Module_Guestbook::instance();
		if ($this->getCRUDID() !== '1')
		{
			if ($gb = $mod->getUserGuestbook())
			{
				if ($gb->getID() !== $this->getCRUDID())
				{
					$error = 'err_permission_read';
					GDT_Redirect::to($gb->href_gb_edit());
				}
			}
			else
			{
				if (!$mod->cfgAllowUserGB())
				{
					$error = 'err_permission_create';
				}
				if ($mod->cfgLevel() > GDO_User::current()->getLevel())
				{
					$error = 'err_permission_create_level';
					$args = [$mod->cfgLevel()];
				}
			}
		}
		elseif (!$this->canUpdate($this->gdo))
		{
			$error = 'err_permission_update';
		}
		return !$error;
	}

	protected function createForm(GDT_Form $form): void
	{
		$mod = Module_Guestbook::instance();
		$table = $this->gdoTable();

		$form->addField(GDT_Divider::make('div1')->label('div_gb_appearance'));
		$form->addField($table->gdoColumn('gb_title'));
		$form->addField($table->gdoColumn('gb_descr'));

		$form->addField(GDT_Divider::make('div2')->label('div_gb_signing'));
		$form->addField($table->gdoColumn('gb_unlocked'));
		$form->addField($table->gdoColumn('gb_moderated'));
		$form->addField($table->gdoColumn('gb_notify_mail'));

		$form->addField(GDT_Divider::make('div3')->label('div_gb_permissions'));
		if ($mod->cfgAllowGuestView())
		{
			$form->addField($table->gdoColumn('gb_guest_view'));
		}
		if ($mod->cfgAllowGuestSign())
		{
			$form->addField($table->gdoColumn('gb_guest_sign'));
		}
		if ($mod->cfgAllowgLevel())
		{
			$form->addField($table->gdoColumn('gb_level'));
		}

		$form->addField(GDT_Divider::make('div4')->label('div_gb_metadata'));
		if ($mod->cfgAllowEMail())
		{
			$form->addField($table->gdoColumn('gb_allow_email'));
		}
		if ($mod->cfgAllowURL())
		{
			$form->addField($table->gdoColumn('gb_allow_url'));
		}

		if ($this->isCaptchaRequired())
		{
			if (module_enabled('Captcha'))
			{
				$form->addField(GDT_Captcha::make());
			}
		}

		$this->createFormButtons($form);
	}

	public function beforeCreate(GDT_Form $form, GDO $gdo): void
	{
		$gdo->setVar('gb_uid', GDO_User::current()->getID());
	}

	public function afterCreate(GDT_Form $form, GDO $gdo): void
	{
		Module_Guestbook::instance()->saveSetting('user_guestbook', $gdo->getID());
	}

}
