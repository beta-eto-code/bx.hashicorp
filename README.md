# HashiCorp Vault for Bitrix

**Example:**
```php
use Bx\HashiCorp\Factory;

$vaultOptionHolder = Factory::crateCached('main');  // init option holder from module setting
$vaultOptionHolder->getOptionValue('PRIVATE_KEY', 'my.module'); // read value by key PRIVATE_KEY
$vaultOptionHolder->setOptionValue('PRIVATE_KEY', '...', 'my.module'); // update value by key PRIVATE_KEY
```