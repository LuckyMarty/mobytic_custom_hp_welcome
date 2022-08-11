<?php
/**
 * 2007-2022 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL: 
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2022 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Mobytic_custom_hp_welcome extends Module implements WidgetInterface
{
    // protected $config_form = false;

    public function __construct()
    {
        $this->name = 'mobytic_custom_hp_welcome';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Mobytic';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Mobytic - Custom - Home Page - Welcome');
        $this->description = $this->l('Bloc texte de bienvenue sur la page d\'accueil modifiable dans le back-end de PrestaShop');

        $this->confirmUninstall = $this->l('');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        $this->installTab();

        Configuration::updateValue('MOBYTIC_CUSTOM_HP_WELCOME_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayHome');
    }

    public function installTab()
    {
        $response = true;

        // First check for parent tab
        $parentTabID = Tab::getIdFromClassName('AdminMobytic');

        if ($parentTabID) {
            $parentTab = new Tab($parentTabID);
        } else {
            $parentTab = new Tab();
            $parentTab->active = 1;
            $parentTab->name = array();
            $parentTab->class_name = "AdminMobytic";
            foreach (Language::getLanguages() as $lang) {
                $parentTab->name[$lang['id_lang']] = "Mobytic";
            }
            $parentTab->id_parent = 0;
            $parentTab->module = $this->name;
            $response &= $parentTab->add();
        }

        // Check for parent tab2
        $parentTab_2ID = Tab::getIdFromClassName('AdminMobyticThemeCustom');
        if ($parentTab_2ID) {
            $parentTab_2 = new Tab($parentTab_2ID);
        } else {
            $parentTab_2 = new Tab();
            $parentTab_2->active = 1;
            $parentTab_2->name = array();
            $parentTab_2->class_name = "AdminMobyticThemeCustom";
            foreach (Language::getLanguages() as $lang) {
                $parentTab_2->name[$lang['id_lang']] = "Theme Custom";
            }
            $parentTab_2->id_parent = $parentTab->id;
            $parentTab_2->module = $this->name;
            $response &= $parentTab_2->add();
        }

        // Created tab
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'Admin' . $this->name;
        $tab->name = array();
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = "Bienvenue";
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function uninstall()
    {
        $this->uninstallTab();

        Configuration::deleteByName('MOBYTIC_CUSTOM_HP_WELCOME_LIVE_MODE');

        return parent::uninstall();
    }

    public function uninstallTab()
    {
        $id_tab = Tab::getIdFromClassName('Admin' . $this->name);
        $tab = new Tab($id_tab);
        $tab->delete();
        return true;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        $output = null;

        $output .= $this->uploadFileConditions('MOBYTIC_CUSTOM_HP_WELCOME_IMG');

        $this->context->smarty->assign('module_dir', $this->_path);

        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        $output .= $this->renderForm($this->getConfigForm(), $this->getConfigFormValues(), 'submitMobytic_custom_submit_btn');

        return $output;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm($getConfigForm, $getConfigFormValues, $submit_action)
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = $submit_action;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'uri' => $this->getPathUri(),
            'fields_value' => $getConfigFormValues, /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($getConfigForm));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Afficher'),
                        'name' => 'MOBYTIC_CUSTOM_HP_WELCOME_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Oui')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Non')
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'MOBYTIC_CUSTOM_HP_WELCOME_TITLE',
                        'label' => $this->l('Titre'),
                    ),
                    array(
                        'type' => 'textarea',
                        'autoload_rte' => true,
                        'tinymce' => true,
                        'class' => 'rte',
                        'cols' => 60,
                        'rows' => 30,
                        'name' => 'MOBYTIC_CUSTOM_HP_WELCOME_TEXT',
                        'label' => $this->l('Texte'),
                    ),
                    array(
                        'type' => 'file',
                        'name' => 'MOBYTIC_CUSTOM_HP_WELCOME_IMG',
                        'label' => $this->l('Image'),
                        'display_image' => true,
                        'image' => $this->displayImgInForm('MOBYTIC_CUSTOM_HP_WELCOME_IMG'),
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'MOBYTIC_CUSTOM_HP_WELCOME_IMG_ALT',
                        'label' => $this->l('Texte Alternative de l\'IMG'),
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'MOBYTIC_CUSTOM_HP_WELCOME_READ_MORE',
                        'label' => $this->l('Bouton - Titre'),
                        'desc' => $this->l('En savoir plus'),
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'MOBYTIC_CUSTOM_HP_WELCOME_READ_MORE_URL',
                        'label' => $this->l('Bouton - Lien'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'MOBYTIC_CUSTOM_HP_WELCOME_LIVE_MODE' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_LIVE_MODE', true),
            'MOBYTIC_CUSTOM_HP_WELCOME_TITLE' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_TITLE'),
            'MOBYTIC_CUSTOM_HP_WELCOME_TEXT' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_TEXT'),
            'MOBYTIC_CUSTOM_HP_WELCOME_IMG_ALT' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_IMG_ALT'),
            'MOBYTIC_CUSTOM_HP_WELCOME_READ_MORE' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_READ_MORE'),
            'MOBYTIC_CUSTOM_HP_WELCOME_READ_MORE_URL' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_READ_MORE_URL'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key), true);
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        // if (Tools::getValue('module_name') == $this->name) {
        $this->context->controller->addJS($this->_path . 'views/js/back.js');
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        // }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }


    public function getVariables()
    {
        return $this->context->smarty->assign([
            'title' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_TITLE', Tools::getValue('MOBYTIC_CUSTOM_HP_WELCOME_TITLE')),
            'text' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_TEXT', Tools::getValue('MOBYTIC_CUSTOM_HP_WELCOME_TEXT')),
            'img' => $this->getImgURL('MOBYTIC_CUSTOM_HP_WELCOME_IMG'),
            'alt' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_IMG_ALT', Tools::getValue('MOBYTIC_CUSTOM_HP_WELCOME_IMG_ALT')),
            'btn' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_READ_MORE', Tools::getValue('MOBYTIC_CUSTOM_HP_WELCOME_READ_MORE')),
            'btn_url' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_READ_MORE_URL', Tools::getValue('MOBYTIC_CUSTOM_HP_WELCOME_READ_MORE_URL')),
        ]);
    }

    public function hookDisplayHome()
    {
        /* Place your code here. */

        // $this->context->smarty->assign([
        //     'title' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_TITLE', Tools::getValue('MOBYTIC_CUSTOM_HP_WELCOME_TITLE')),
        //     'text' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_TEXT', Tools::getValue('MOBYTIC_CUSTOM_HP_WELCOME_TEXT')),
        //     'img' => $this->getImgURL('MOBYTIC_CUSTOM_HP_WELCOME_IMG'),
        //     'alt' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_IMG_ALT', Tools::getValue('MOBYTIC_CUSTOM_HP_WELCOME_IMG_ALT')),
        //     'btn' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_READ_MORE', Tools::getValue('MOBYTIC_CUSTOM_HP_WELCOME_READ_MORE')),
        //     'btn_url' => Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_READ_MORE_URL', Tools::getValue('MOBYTIC_CUSTOM_HP_WELCOME_READ_MORE_URL')),
        // ]);
        $this->getVariables();


        if (Configuration::get('MOBYTIC_CUSTOM_HP_WELCOME_LIVE_MODE') == true) {
            return $this->display(__FILE__, 'mobytic_hp_welcome.tpl');
        }
    }


    // ************************************************************ 
    // Widget
    // ************************************************************
    public function renderWidget($hookName, array $configuration)
    {
        $this->getWidgetVariables($hookName, $configuration);
        return $this->fetch('module:' . $this->name . '/views/templates/widget/mobytic_custom_hp_welcome.tpl');
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        $this->getVariables();
    }












    // ****************************************************
    // FONCTIONS
    // ****************************************************
    protected function uploadFileConditions($img_uploaded)
    {
        if (((bool)Tools::isSubmit('submitMobytic_custom_submit_btn')) == true) {
            $this->postProcess($this->getConfigFormValues());
            return $this->checkUploadFile($img_uploaded);
        }
    }
    protected function checkUploadFile($img_uploaded)
    {
        if (isset($_FILES[$img_uploaded])) {
            $file = $_FILES[$img_uploaded];

            // File properties
            $file_name = $file['name'];
            $file_tpm = $file['tmp_name'];
            $file_size = $file['size'];
            $file_error = $file['error'];

            // Work out the file extension
            $file_ext = explode('.', $file_name);
            $file_ext = strtolower(end($file_ext));

            $allowed = array('jpg', 'png', 'jpeg');

            if (in_array($file_ext, $allowed)) {
                move_uploaded_file($_FILES[$img_uploaded]['tmp_name'], dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views/img' . DIRECTORY_SEPARATOR . $_FILES[$img_uploaded]["name"]);
                Configuration::updateValue($img_uploaded, Tools::getValue($img_uploaded));
                return $this->displayConfirmation($this->l('Mise à jour réussie'));
            } else {
                return $this->displayError($this->l('Mauvais format / Vous aviez laissé la même photo (ne pas tenir en compte)'));
            }
        }
    }

    protected function displayImgInForm($img_uploaded)
    {
        $img_name = Configuration::get($img_uploaded);
        $img_url = $this->context->link->protocol_content . Tools::getMediaServer($img_name) . $this->_path . 'views/img/' . $img_name;
        return $img = $img_name ? '<div class="col-lg-6"><img src="' . $img_url . '" class="img-thumbnail" width="200"></div>' : "";
    }

    protected function getImgURL($img_uploaded)
    {
        $img_name = Configuration::get($img_uploaded);
        return $img_name ? $this->context->link->protocol_content . Tools::getMediaServer($img_name) . $this->_path . 'views/img/' . $img_name : '';
    }
}
