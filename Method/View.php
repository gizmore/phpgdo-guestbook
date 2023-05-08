<?php
declare(strict_types=1);
namespace GDO\Guestbook\Method;

use GDO\Core\GDO;
use GDO\Core\GDO_ArgError;
use GDO\Core\GDT;
use GDO\Core\GDT_Object;
use GDO\DB\Query;
use GDO\Guestbook\GDO_Guestbook;
use GDO\Guestbook\GDO_GuestbookMessage;
use GDO\Guestbook\Module_Guestbook;
use GDO\Table\GDT_Table;
use GDO\Table\MethodQueryList;
use GDO\UI\GDT_Card;
use GDO\User\GDO_User;


/**
 * View a page messages from a guestbook.
 *
 * @version 7.0.3
 */
final class View extends MethodQueryList
{

	private GDO_Guestbook $guestbook;

	public function getDefaultOrder(): ?string { return 'gbm_created DESC'; }

	public function gdoParameters(): array
	{
		return array_merge(parent::gdoParameters(), [
			GDT_Object::make('id')->table(GDO_Guestbook::table())->notNull()->initial('1')->searchable(false)->orderable(false),
		]);
	}

	public function gdoHeaders(): array
	{
		$table = GDO_GuestbookMessage::table();
		return [
			$table->gdoColumn('gbm_message'),
			$table->gdoColumn('gbm_user'),
			$table->gdoColumn('gbm_email'),
			$table->gdoColumn('gbm_website'),
			$table->gdoColumn('gbm_created'),
		];
	}

	public function onMethodInit(): ?GDT
	{
		$this->guestbook = $this->getGuestbook();
		return null;
	}

	public function hasPermission(GDO_User $user, string &$error, array &$args): bool
	{
		if (!isset($this->guestbook))
		{
			$error = 'err_no_gb';
		}
		elseif (!$this->guestbook->canView($user))
		{
			$error = 'err_permission_read';
		}
		return !$error;
	}

	/**
	 * @throws GDO_ArgError
	 */
	public function getGuestbook(): GDO_Guestbook
	{
		return $this->gdoParameterValue('id');
	}


	public function getQuery(): Query
	{
		return $this->gdoTable()->
		select('gdo_guestbookmessage.*')->
		where('gbm_guestbook=' . $this->guestbook->getID())->
		where('gbm_approved IS NOT NULL')->
		where('gbm_deleted IS NULL')->
		joinObject('gbm_user');
	}

	public function gdoTable(): GDO
	{
		return GDO_GuestbookMessage::table();
	}

	protected function setupTitle(GDT_Table $table): void
	{
		$table->title('list_view_guestbook', [$table->countItems()]);
	}

	public function execute(): GDT
	{
		$gb = $this->guestbook;
		$mod = Module_Guestbook::instance();

		$bar = $mod->guestbookViewBar($gb);

		$card = null;
		if ($this->getPage() === '1')
		{
			$card = GDT_Card::make('gbcard')->gdo($gb);
			$card->titleRaw($gb->gdoColumn('gb_title')->render());
			if ($gb->getID() !== '1')
			{
				$card->creatorHeader(null, 'gb_uid');
			}
			$card->addField($gb->gdoColumn('gb_descr'));
		}

		return $bar->addField($card)->addField($this->renderTable());
	}

}
