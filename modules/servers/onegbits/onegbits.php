<?php
/**
 * 1Gbits Dedicated Server — WHMCS Reseller Provisioning Module
 *
 * @package    onegbits
 * @author     1Gbits
 * @copyright  Copyright (c) 1Gbits
 * @license    MIT
 * @link       https://www.1gbits.com/
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/ApiClient.php';
require_once __DIR__ . '/lib/Helper.php';

use OneGbits\Helper;

/**
 * Module metadata.
 */
function onegbits_MetaData()
{
    return [
        'DisplayName' => '1Gbits Dedicated Server (Reseller)',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '80',
        'DefaultSSLPort' => '443',
    ];
}

/**
 * Product configuration options.
 *
 * API credentials deliberately live on the server record, not here, so that
 * they are shared across products and available to TestConnection.
 */
function onegbits_ConfigOptions()
{
    return [
        'planSku' => [
            'FriendlyName' => 'Plan SKU',
            'Type' => 'text',
            'Size' => '25',
            'Default' => 'DEDI-INTEL-E3',
            'Description' => 'Dedicated server plan identifier supplied by 1Gbits',
        ],
        'location' => [
            'FriendlyName' => 'Location',
            'Type' => 'dropdown',
            'Options' => 'ams,lon,nyc,ist,tyo',
            'Default' => 'ist',
            'Description' => 'Datacenter code',
        ],
        'osTemplate' => [
            'FriendlyName' => 'OS Template',
            'Type' => 'dropdown',
            'Options' => 'ubuntu-22.04,ubuntu-24.04,debian-12,alma-9,win-2022',
            'Default' => 'ubuntu-24.04',
            'Description' => 'Operating system installed at provisioning time',
        ],
        'autoAssignIp' => [
            'FriendlyName' => 'Auto Assign IP',
            'Type' => 'yesno',
            'Default' => 'on',
            'Description' => 'Let the API allocate a primary IP automatically',
        ],
    ];
}

/**
 * Provision a new dedicated server.
 *
 * @return string 'success' or an error message
 */
function onegbits_CreateAccount(array $params)
{
    try {
        $api = Helper::clientFromParams($params);

        $payload = [
            'planSku' => $params['configoption1'],
            'location' => $params['configoption2'],
            'os' => $params['configoption3'],
            'autoIp' => in_array(strtolower((string) $params['configoption4']), ['on', 'yes', '1'], true),
            'hostname' => $params['domain'] ?: Helper::generateHostname($params),
            'termMonths' => Helper::termMonthsFromBillingCycle($params['billingcycle'] ?? ''),
            'user' => [
                'email' => $params['clientsdetails']['email'] ?? '',
                'firstName' => $params['clientsdetails']['firstname'] ?? '',
                'lastName' => $params['clientsdetails']['lastname'] ?? '',
            ],
            'options' => [
                'raid' => Helper::customField($params, 'RAID Level'),
                'ipCount' => (int) Helper::customField($params, 'Additional IPs'),
                'notes' => Helper::customField($params, 'Order Notes'),
            ],
        ];

        $result = $api->post('/reseller/orders', $payload);
        logModuleCall('onegbits', 'CreateAccount', $payload, $result, '', Helper::logMask($params));

        if (!$result['success']) {
            return 'API error: ' . Helper::errorText($result);
        }

        $serverId = Helper::extractServerId($result['data']);

        if ($serverId === '') {
            return 'Provisioning succeeded but the API did not return a server ID. '
                . 'Locate the server in your 1Gbits panel and record its ID against this service.';
        }

        if (!Helper::storeServerId($params, $serverId)) {
            return 'Provisioned server ' . $serverId . ' but the ID could not be saved to this service.';
        }

        return 'success';
    } catch (\Throwable $e) {
        return 'Create failed: ' . $e->getMessage();
    }
}

/**
 * Suspend the server.
 *
 * @return string
 */
function onegbits_SuspendAccount(array $params)
{
    return onegbits_simpleCommand($params, 'SuspendAccount', 'suspend');
}

/**
 * Unsuspend the server.
 *
 * @return string
 */
function onegbits_UnsuspendAccount(array $params)
{
    return onegbits_simpleCommand($params, 'UnsuspendAccount', 'unsuspend');
}

/**
 * Terminate the server.
 *
 * @return string
 */
function onegbits_TerminateAccount(array $params)
{
    try {
        $api = Helper::clientFromParams($params);
        $serverId = Helper::serverId($params);

        if ($serverId === '') {
            return onegbits_missingServerId();
        }

        $result = $api->delete('/reseller/servers/' . rawurlencode($serverId));
        logModuleCall('onegbits', 'TerminateAccount', ['serverId' => $serverId], $result, '', Helper::logMask($params));

        return $result['success'] ? 'success' : 'API error: ' . Helper::errorText($result);
    } catch (\Throwable $e) {
        return 'Terminate failed: ' . $e->getMessage();
    }
}

/**
 * Upgrade or downgrade the server plan.
 *
 * @return string
 */
function onegbits_ChangePackage(array $params)
{
    try {
        $api = Helper::clientFromParams($params);
        $serverId = Helper::serverId($params);

        if ($serverId === '') {
            return onegbits_missingServerId();
        }

        $payload = ['planSku' => $params['configoption1']];
        $result = $api->post('/reseller/servers/' . rawurlencode($serverId) . '/change-plan', $payload);
        logModuleCall(
            'onegbits',
            'ChangePackage',
            $payload + ['serverId' => $serverId],
            $result,
            '',
            Helper::logMask($params)
        );

        return $result['success'] ? 'success' : 'API error: ' . Helper::errorText($result);
    } catch (\Throwable $e) {
        return 'ChangePackage failed: ' . $e->getMessage();
    }
}

/**
 * Live server details on the admin Services tab.
 *
 * @return array<string,string>
 */
function onegbits_AdminServicesTabFields(array $params)
{
    try {
        $serverId = Helper::serverId($params);

        if ($serverId === '') {
            return ['1Gbits' => 'Server not yet provisioned (no server ID recorded).'];
        }

        $api = Helper::clientFromParams($params);
        $result = $api->get('/reseller/servers/' . rawurlencode($serverId));
        logModuleCall(
            'onegbits',
            'AdminServicesTabFields',
            ['serverId' => $serverId],
            $result,
            '',
            Helper::logMask($params)
        );

        if (!$result['success']) {
            return ['1Gbits' => Helper::safe(Helper::errorText($result))];
        }

        $details = Helper::normalizeDetails($result['data']);

        return [
            'Server ID' => Helper::safe($details['id'] !== '' ? $details['id'] : $serverId),
            'Status' => Helper::safe($details['status']),
            'Hostname' => Helper::safe($details['hostname']),
            'Location' => Helper::safe($details['location']),
            'IP Address' => Helper::safe($details['primaryIp']),
            'Plan' => Helper::safe($details['planSku']),
            'OS' => Helper::safe($details['os']),
        ];
    } catch (\Throwable $e) {
        return ['1Gbits' => Helper::safe($e->getMessage())];
    }
}

/**
 * Client area overview panel.
 *
 * @return array
 */
function onegbits_ClientArea(array $params)
{
    $vars = [
        'status' => 'Pending',
        'serverId' => '',
        'details' => Helper::normalizeDetails([]),
        'error' => '',
    ];

    try {
        $serverId = Helper::serverId($params);

        if ($serverId !== '') {
            $vars['serverId'] = $serverId;

            $api = Helper::clientFromParams($params);
            $result = $api->get('/reseller/servers/' . rawurlencode($serverId));
            logModuleCall('onegbits', 'ClientArea', ['serverId' => $serverId], $result, '', Helper::logMask($params));

            if ($result['success']) {
                $details = Helper::normalizeDetails($result['data']);
                $vars['details'] = $details;
                $vars['status'] = $details['status'] !== '' ? $details['status'] : 'Unknown';
            } else {
                // Deliberately generic: API diagnostics stay in the module log.
                $vars['error'] = 'Server details are temporarily unavailable. Please try again shortly.';
            }
        }
    } catch (\Throwable $e) {
        $vars['error'] = 'Server details are temporarily unavailable. Please try again shortly.';
    }

    return [
        'tabOverviewReplacementTemplate' => 'templates/clientarea.tpl',
        'templateVariables' => $vars,
    ];
}

/**
 * Verify API connectivity from System Settings > Servers.
 *
 * @return array{success:bool,error:string}
 */
function onegbits_TestConnection(array $params)
{
    try {
        $api = Helper::clientFromParams($params);
        $result = $api->get('/reseller/ping');
        logModuleCall('onegbits', 'TestConnection', [], $result, '', Helper::logMask($params));

        if ($result['success']) {
            return ['success' => true, 'error' => ''];
        }

        return ['success' => false, 'error' => 'Ping failed: ' . Helper::errorText($result)];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Shared implementation for the suspend/unsuspend POST commands.
 *
 * @return string
 */
function onegbits_simpleCommand(array $params, string $action, string $endpoint)
{
    try {
        $api = Helper::clientFromParams($params);
        $serverId = Helper::serverId($params);

        if ($serverId === '') {
            return onegbits_missingServerId();
        }

        $result = $api->post('/reseller/servers/' . rawurlencode($serverId) . '/' . $endpoint);
        logModuleCall('onegbits', $action, ['serverId' => $serverId], $result, '', Helper::logMask($params));

        return $result['success'] ? 'success' : 'API error: ' . Helper::errorText($result);
    } catch (\Throwable $e) {
        return $action . ' failed: ' . $e->getMessage();
    }
}

/**
 * @return string
 */
function onegbits_missingServerId()
{
    return 'No 1Gbits server ID is recorded against this service. Re-run Create, or set the '
        . 'server ID manually before running this command.';
}
