<?php

global $USER;
global $APPLICATION;

if(!$USER->IsAdmin()) {
    return;
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Vault\AuthenticationStrategies\AppRoleAuthenticationStrategy;
use Vault\AuthenticationStrategies\LdapAuthenticationStrategy;
use Vault\AuthenticationStrategies\OktaAuthenticationStrategy;
use Vault\AuthenticationStrategies\RadiusAuthenticationStrategy;
use Vault\AuthenticationStrategies\TokenAuthenticationStrategy;
use Vault\AuthenticationStrategies\UserPassAuthenticationStrategy;

$mid = 'bx.hashicorp';

Loc::loadMessages(__FILE__);
Loader::includeModule($mid);
$options = [
    [
        'tab' => "HashiCorp Vault",
        'options' => [
            'URI' => 'Адрес сервера',
            'NAMESPACE' => 'namespace',
            'AUTH_TYPE' => [
                'name' => 'AUTH_TYPE',
                'type' => 'select',
                'label' => 'Тип авторизации',
                'values' => [
                    '' => '-',
                    TokenAuthenticationStrategy::class => 'Token',
                    UserPassAuthenticationStrategy::class => 'Username',
                    LdapAuthenticationStrategy::class => 'LDAP',
                    OktaAuthenticationStrategy::class => 'Okta',
                    AppRoleAuthenticationStrategy::class => 'JWT/AppRole',
                    RadiusAuthenticationStrategy::class => 'RADIUS',
                ],
            ],
            'USERNAME' => 'Логин (username)',
            'PASSWORD' => 'Пароль (password)',
            'TOKEN' => 'Ключ доступа (token)',
            'ROLE_ID' => 'Идентификатор приложения (roleId)',
            'SECRET_ID' => 'Ключ приложения (secretId)',
        ],
    ],
];

$optionJson = [];
$optionNames = [];
foreach ($options as $optionTab) {
    foreach ($optionTab['options'] as $name => $value) {
        if (is_string($value)) {
            $optionNames[] = $name;
        }
        else if (is_array($value)) {
            $name = $value['name'] ?? null;
            if ($name) {
                $optionNames[] = $name;

                $multiple = (bool) ($value['multiple'] ?? false);
                if ($multiple) {
                    $optionJson[] = $name;
                }
            }
        }
    }
}

$isSave = $_POST['save'] ?? $_POST['apply'] ?? false;
if ($isSave && check_bitrix_sessid()) {
    foreach ($optionNames as $name) {
        $value = $_POST[$name] ?? null;
        if (is_array($value)) {
            $value = array_filter($value);
        }
        if (in_array($name, $optionJson)) {
            $value = json_encode($value);
        }
        Option::set($mid, $name, "{$value}");
    }
}


$aTabs = array_map(function($item) {
    static $i = 0;
    return [
        'ICON' => '',
        'DIV' => 'tab'.($i++),
        'TAB' => $item['tab'],
        'TITLE' => $item['tab'],
    ];
}, $options);

$tabControl = new CAdminTabControl('tabControl', $aTabs);
$actionUrl = $APPLICATION->GetCurPage() ."?mid=".urlencode($mid)."&lang=".LANGUAGE_ID;

?>
<form method="post" action="<?= $actionUrl ?>">
    <?
    echo bitrix_sessid_post();

    $tabControl->Begin();
    foreach ($options as $optionTab) {
        $tabControl->BeginNextTab();
        foreach ($optionTab['options'] as $name => $value) {
            if (is_string($value)) {
                $optionName = $name;
                $optionLabel = $value;
                $optionType = "text";
            }
            else if (is_array($value)) {
                $optionGroup = $value['group'] ?? null;
                if ($optionGroup) {
                    echo "<tr class='heading'><td colspan='2'>{$optionGroup}</td></tr>";
                    continue;
                }

                $optionType = $value['type'] ?? 'text';
                $optionName = $value['name'] ?? null;
                if (!$optionName) {
                    continue;
                }

                $optionLabel = $value['label'] ?? $optionName;
            }

            $optionValue = (string) Option::get($mid, $optionName);
            ?>
            <tr>
                <td class="adm-detail-content-cell-l">
                    <?= $optionLabel ?>
                </td>
                <td class="adm-detail-content-cell-r">
                    <?php
                    switch ($optionType) {
                        case 'select':
                            $selectValues = $value['values'];
                            $isAssocSelectValues = !empty(array_diff_assoc(
                                array_keys($selectValues),
                                range(0, count($selectValues)-1)
                            ));

                            $multiple = (bool) ($value['multiple'] ?? false);
                            $size = 1;
                            if ($multiple) {
                                $size = 5;
                                $optionName .= "[]";
                            }

                            echo "<select class='typeselect' name='{$optionName}' size='{$size}' ".($multiple ? 'multiple' : '').">";
                            foreach ($selectValues as $key => $item) {
                                if ($isAssocSelectValues) {
                                    $selectOptionValue = $key;
                                }
                                else {
                                    $selectOptionValue = $item;
                                }

                                if ($multiple) {
                                    $selected = in_array($selectOptionValue, $optionValue) ? "selected" : "";
                                }
                                else {
                                    $selected = "{$selectOptionValue}" === "{$optionValue}" ? "selected" : "";
                                }

                                echo "<option value='{$selectOptionValue}' {$selected}>{$item}</option>";
                            }
                            echo "</select>";
                            break;

                        case 'checkbox':
                            $optionValue = $optionValue ?: 'N';
                            $checked = $optionValue == 'Y' ? "checked" : "";
                            echo "
                            <input class='adm-designed-checkbox' type='checkbox' id='{$optionName}' name='{$optionName}' value='Y' {$checked}>
                            <label class='adm-designed-checkbox-label' for='{$optionName}'></label>
                            ";
                            break;
                        case 'textarea':
                            echo "<textarea name='{$optionName}' cols='".($value['cols'] ?? 30)."' rows='".($value['rows'] ?? 5)."'>{$optionValue}</textarea>";
                            break;
                        default:
                            $multiple = (bool) ($value['multiple'] ?? false);
                            if ($multiple) {
                                $optionName .= "[]";
                                foreach ($optionValue as $item) {
                                    if (empty($item)) {
                                        continue;
                                    }
                                    echo "<div><input type='{$optionType}' name='{$optionName}' value='{$item}'></div>";
                                }
                                echo "<div>
								<input type='button' value='Добавить' onclick='addTemplateRow(this);'>
								<div class='jsTemplateRow' style='display:none;'>
									<input type='{$optionType}' name='{$optionName}' value=''>
								</div>
								</div>";
                            }
                            else {
                                echo "<input type='{$optionType}' name='{$optionName}' value='{$optionValue}'>";
                            }
                            break;
                    }
                    ?>
                </td>
            </tr>
            <?php
        }
    }

    $tabControl->Buttons([]);
    $tabControl->End();

    ?>
</form>
<style media="screen">
    .adm-detail-content-cell-l {
        width: 50%;
    }
    .adm-detail-content-cell-r select {
        width: auto;
        max-width: 100%;
    }
    .adm-detail-content-cell-l,
    .adm-detail-content-cell-r {
        vertical-align: top;
    }
</style>
<script type="text/javascript">
    function addTemplateRow(btn) {
        var templateRow = btn.parentNode.querySelector('.jsTemplateRow')
        if (!templateRow) {
            return;
        }

        var targetElement = btn.parentNode.parentNode;
        if (!targetElement) {
            return;
        }

        var div = document.createElement('div')
        div.innerHTML = templateRow.innerHTML
        targetElement.insertBefore(
            div, targetElement.lastElementChild
        )
    }

    const tokenAuth = '<?= TokenAuthenticationStrategy::class ?>';
    const userPassAuth = '<?= UserPassAuthenticationStrategy::class ?>';
    const ldapAuth = '<?= LdapAuthenticationStrategy::class ?>';
    const oktaAuth = '<?= OktaAuthenticationStrategy::class ?>';
    const appAuth = '<?= AppRoleAuthenticationStrategy::class ?>';
    const radiusAuth = '<?= RadiusAuthenticationStrategy::class ?>';

    function visibleSwitcher(hideList, showList) {
        for (let item of hideList) {
            item.classList.add('hidden');
        }
        for (let item of showList) {
            item.classList.remove('hidden');
        }
    }

    window.addEventListener('DOMContentLoaded', function() {
        let selectAuthType = document.querySelector("input[name='AUTH_TYPE']");
        let usernameInput = document.querySelector("input[name='USERNAME']");
        let passwordInput = document.querySelector("input[name='PASSWORD']");
        let tokenInput = document.querySelector("input[name='TOKEN']");
        let roleIdInput = document.querySelector("input[name='ROLE_ID']");
        let secretIdInput = document.querySelector("input[name='SECRET_ID']");

        function initState(authType) {
            visibleSwitcher([tokenInput, roleIdInput, secretIdInput, usernameInput, passwordInput], []);
            switch (authType) {
                case userPassAuth:
                case ldapAuth:
                case oktaAuth:
                case radiusAuth:
                    visibleSwitcher([tokenInput, roleIdInput, secretIdInput], [usernameInput, passwordInput]);
                    break;
                case appAuth:
                    visibleSwitcher([tokenInput, usernameInput, passwordInput], [roleIdInput, secretIdInput]);
                    break;
                case tokenAuth:
                    visibleSwitcher([roleIdInput, secretIdInput, usernameInput, passwordInput], [tokenInput]);
                    break;
            }
        }

        initState(selectAuthType.value);

        selectAuthType.addEventListener('change', function() {
            initState(this.value);
        });
    });
</script>
<style>
    .hidden {
        display: none;
    }
</style>