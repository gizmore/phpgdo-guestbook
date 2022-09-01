<?php
namespace GDO\Guestbook\Method;

use GDO\Form\GDT_Form;
use GDO\Form\MethodCrud;
use GDO\Guestbook\GDO_Guestbook;
use GDO\Guestbook\Module_Guestbook;
use GDO\Core\GDO;
use GDO\Core\Website;
use GDO\User\GDO_User;
use GDO\UI\GDT_Divider;
use GDO\Admin\MethodAdmin;
use GDO\UI\GDT_Page;
use GDO\Core\GDO_Exception;
use GDO\UI\GDT_Redirect;

/**
 * Manage Guestbooks.
 * Every user may have at max 1 guestbook.
 * 
 * @author gizmore
 * @version 6.10
 * @since 6.10
 * 
 * @see GDO_Guestbook
 * @see Module_Guestbook
 */
final class Crud extends MethodCrud
{
    use MethodAdmin;
    
    public function onRenderTabs() : void
    {
        if ($this->getCRUDID() === '1')
        {
            $this->renderAdminBar();
        }
    }
    
    public function isUserRequired() : bool { return false; }
    
    public function getPermission() : ?string
    {
        return $this->getCRUDID() === '1' ? 'staff' : null;
    }
    
    
    public function execute()
    {
        if ($this->gdo && ($this->gdo->getID() === '1'))
        {
            $mod = Module_Guestbook::instance();
            GDT_Page::$INSTANCE->topBar()->addField($mod->adminBar());
        }
        return parent::execute();
    }
    
    public function hrefList() : string
    {
        return hrefDefault();
    }

    public function gdoTable() : GDO
    {
        return GDO_Guestbook::table();
    }

    public function canUpdate(GDO $gdo)
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
    
    public function onInit()
    {
        parent::onInit();
        
        $mod = Module_Guestbook::instance();
        
        if ($this->getCRUDID() !== '1')
        {
            if ($gb = $mod->getUserGuestbook())
            {
                if ($gb->getID() !== $this->getCRUDID())
                {
                    GDT_Redirect::to(href('Guestbook', 'Crud', '&id=' . $gb->getID()));
                }
            }
            else
            {
                if (!$mod->cfgAllowUserGB())
                {
                	throw new GDO_Exception('err_permission_create');
                }
                if ($mod->cfgLevel() > GDO_User::current()->getLevel())
                {
                    $this->error('err_permission_create_level', [$mod->cfgLevel()]);
                }
            }
        }
        else
        {
            if (!$this->canUpdate($this->gdo))
            {
                throw new GDO_Exception('err_permission_update');
            }
        }
    }
    
    public function createForm(GDT_Form $form) : void
    {
        $mod = Module_Guestbook::instance();
        $table = $this->gdo ? $this->gdo->table() : GDO_Guestbook::table();
        
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
        
        $this->createCaptcha($form);
        $this->createFormButtons($form);
    }
    
    public function beforeCreate(GDT_Form $form, GDO $gdo)
    {
        $gdo->setVar('gb_uid', GDO_User::current()->getID());
    }
    
    public function afterCreate(GDT_Form $form, GDO $gdo)
    {
        Module_Guestbook::instance()->saveSetting('user_guestbook', $gdo->getID());
    }
    
}
