<?php
namespace GDO\Guestbook\Method;

use GDO\UI\GDT_Page;
use GDO\UI\MethodPage;
use GDO\Admin\MethodAdmin;
use GDO\Guestbook\Module_Guestbook;

final class Admin extends MethodPage
{
	use MethodAdmin;

	public function onRenderTabs(): void
	{
		$this->renderAdminBar();

		$mod = Module_Guestbook::instance();
		GDT_Page::$INSTANCE->topBar()->addField($mod->adminBar());
	}

}
