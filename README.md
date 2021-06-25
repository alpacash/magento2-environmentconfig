# Alpaca\EnvironmentConfig

This Magento 2 module aims to be a simple way of applying specific Magento configuration for the current environment, based on YAML or JSON files.

Build upon the [semaio/magento2-configimportexport](https://github.com/semaio/Magento2-ConfigImportExport) module.

## Installation

**Install via composer**

```bash
composer require alpaca-sh/magento2-environmentconfig
```

**Enable and install the module**

```bash
php bin/magento module:enable Alpaca_EnvironmentConfig
php bin/magento setup:upgrade
```

## Usage

**Configuration folder**

Create a new folder in the root of your project (default: `.environment`), and add this folder to your `.gitignore`.
Next, create one or more files (yaml or json) with your desired configuration.

See [File Formats](https://github.com/semaio/Magento2-ConfigImportExport/blob/develop/docs/file-formats.md) for an extensive overview of the supported file formats, supported by the underlying module [semaio/magento2-configimportexport](https://github.com/semaio/Magento2-ConfigImportExport).

#### Example

```
# .environment/web.yaml

web/unsecure/base_url:
  default:
    0: 'http://magento-app.com/'
web/secure/base_url:
  default:
    0: 'https://magento-app.com/'
```

**Encrypted values**

If you need to save encrypted values but prefer to store the decryted value in your yaml or json file you can use the `!encryted` prefix.

```
service/api/key:
  default:
    0: '!encrypted supersecretapikey'
```

**Apply changes (manually)**

Apply changes using the defaults:
* Folder: `.environment`
* Format: `yaml`

```
bin/magento environment:config:process
```

Apply changes with non-default folder name and format

```
bin/magento environment:config:process --folder <folder-path> --format json
```

After the changes have been applied, the cache will be cleared automaticly. To skip that you can use the `--no-cache` option.

**Apply changes (automatically)**

The configuration can also be applied automatically when running `bin/magento setup:upgrade`.

Add the following configuration to your `app/etc/env.php`.

```
'environment_config' => [
    'auto_update' => [
        'enabled' => true
    ]
]
```

You can override the defaults:

```
'environment_config' => [
    'auto_update' => [
        'enabled' => true,
        'directory' => '.environment',
        'format' => 'yaml'
    ]
]
```

## Licence

[Open Software License (OSL 3.0)](http://opensource.org/licenses/osl-3.0.php)
