<?

IncludeModuleLangFile(__FILE__);
use \Bitrix\Main\ModuleManager;

class bx_hashicorp extends CModule
{
    public $MODULE_ID = "bx.hashicorp";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $errors;

    public function __construct()
    {
        $this->MODULE_VERSION = "1.0.0";
        $this->MODULE_VERSION_DATE = "2022-10-20 10:50:33";
        $this->MODULE_NAME = "Bitrix HashiCorp Vault";
        $this->MODULE_DESCRIPTION = "работа с данными из HashiCorp Vault";
    }

    public function DoInstall()
    {
        ModuleManager::RegisterModule($this->MODULE_ID);
        return true;
    }

    public function DoUninstall()
    {

        ModuleManager::UnRegisterModule($this->MODULE_ID);
        return true;
    }
}
