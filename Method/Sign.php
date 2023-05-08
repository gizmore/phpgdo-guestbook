<?php
namespace GDO\Guestbook\Method;

use GDO\Captcha\GDT_Captcha;
use GDO\Core\GDT;
use GDO\Core\GDT_Object;
use GDO\Date\Time;
use GDO\Form\GDT_AntiCSRF;
use GDO\Form\GDT_Form;
use GDO\Form\GDT_Submit;
use GDO\Form\MethodForm;
use GDO\Guestbook\GDO_Guestbook;
use GDO\Guestbook\GDO_GuestbookMessage;
use GDO\Guestbook\Module_Guestbook;
use GDO\Mail\Mail;
use GDO\UI\GDT_Link;
use GDO\User\GDO_User;

/**
 * Sign a guestbook.
 * Sends notification and moderation mails.
 *
 * @version 6.10.1
 * @since 3.2.0
 *
 * @author gizmore
 * @see GDO_Guestbook
 * @see GDO_GuestbookMessage
 */
final class Sign extends MethodForm
{

	############
	### Init ###
	############
	/**
	 * @var GDO_Guestbook
	 */
	private $guestbook;

	public function onMethodInit(): ?GDT
	{
		parent::onMethodInit();

		if (!($this->guestbook = $this->getGuestbook()))
		{
			return $this->error('err_no_guestbook');
		}

		$errorResponse = null;
		if (!$this->guestbook->canSign(GDO_User::current(), $errorResponse))
		{
			return $errorResponse;
		}

		return null;
	}

	##############
	### Params ###
	##############

	/**
	 * @return GDO_Guestbook
	 */
	public function getGuestbook()
	{
		return $this->gdoParameterValue('id');
	}

	public function gdoParameters(): array
	{
		return [
			GDT_Object::make('id')->table(GDO_Guestbook::table())->notNull(),
		];
	}

	############
	### Form ###
	############

	protected function createForm(GDT_Form $form): void
	{
		$gb = $this->guestbook;
		$mod = Module_Guestbook::instance();
		$table = GDO_GuestbookMessage::table();

		$form->addField($table->gdoColumn('gbm_email'));
		if ($mod->cfgAllowEMail() && $gb->isEMailAllowed())
		{
			$form->addField($table->gdoColumn('gbm_email_public'));
		}

		if ($mod->cfgAllowURL() && $gb->isURLAllowed())
		{
			$form->addField($table->gdoColumn('gbm_website'));
		}

		$form->addField($table->gdoColumn('gbm_message'));

		if ($mod->cfgCaptcha())
		{
			$form->addField(GDT_Captcha::make());
		}

		$form->actions()->addField(GDT_Submit::make());
		$form->addField(GDT_AntiCSRF::make());
	}

	###############
	### Execute ###
	###############
	public function formValidated(GDT_Form $form): GDT
	{
		$gb = $this->guestbook;

		$message = GDO_GuestbookMessage::blank([
			'gbm_guestbook' => $this->guestbook->getID(),
			'gbm_message' => $form->getFormVar('gbm_message'),
			'gbm_email' => $form->getFormVar('gbm_email'),
			'gbm_website' => $form->getFormVar('gbm_website'),
			'gbm_email_public' => $form->getFormVar('gbm_website'),
		]);

		if (!$gb->isModerated())
		{
			$message->setVars([
				'gbm_approver' => GDO_User::system()->getID(),
				'gbm_approved' => Time::getDate(),
			]);
		}

		$message->save();

		if ($gb->isModerated())
		{
			$this->sendModerationMails($gb, $message);
			return $this->message('msg_gb_moderation');
		}
		elseif ($gb->isEMailOnSign())
		{
			$this->sendNotificationMails($gb, $message);
		}

		return $this->message('msg_gb_signed')->addField($this->redirect($gb->href_gb_view(), 12));
	}

	##################
	### Moderation ###
	##################
	private function sendModerationMails(GDO_Guestbook $gb, GDO_GuestbookMessage $msg)
	{
		$users = $gb->getNotifyUsers();
		foreach ($users as $user)
		{
			$this->sendModerationMail($gb, $msg, $user);
		}
	}

	private function sendModerationMail(GDO_Guestbook $gb, GDO_GuestbookMessage $msg, GDO_User $user)
	{
		$mail = Mail::botMail();
		$mail->setSubject(tusr($user, 'mail_subj_gb_moderate', [sitename()]));
		$linkApprove = GDT_Link::make('btn_approve')->href($msg->hrefApprove())->renderHTML();
		$linkDelete = GDT_Link::make('btn_delete')->href($msg->hrefDelete())->renderHTML();
		$args = [
			$user->renderUserName(),
			sitename(),
			$msg->displayEmail(),
			$msg->displayWebsite(),
			$msg->displayMessage(),
			$linkApprove,
			$linkDelete];
		$mail->setBody(tusr($user, 'mail_body_gb_moderate', $args));
		$mail->sendToUser($user);
	}

	##############
	### Notify ###
	##############
	private function sendNotificationMails(GDO_Guestbook $gb, GDO_GuestbookMessage $msg)
	{
		$users = $gb->getNotifyUsers();
		foreach ($users as $user)
		{
			$this->sendNotificationMail($gb, $msg, $user);
		}
	}

	private function sendNotificationMail(GDO_Guestbook $gb, GDO_GuestbookMessage $msg, GDO_User $user)
	{
		$mail = Mail::botMail();
		$mail->setSubject(tusr($user, 'mail_subj_notify_gb', [sitename()]));
		$linkDelete = GDT_Link::make('btn_delete')->href($msg->hrefDelete())->renderHTML();
		$args = [
			$user->renderUserName(),
			sitename(),
			$msg->displayEmail(),
			$msg->displayWebsite(),
			$msg->displayMessage(),
			$linkDelete];
		$mail->setBody(tusr($user, 'mail_body_notify_gb', $args));
		$mail->sendToUser($user);
	}

}
